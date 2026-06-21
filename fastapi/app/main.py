from fastapi import FastAPI, HTTPException, WebSocket, WebSocketDisconnect
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List
from app.worker import booking_worker
from app.redis_client import redis_client
from app.routers import movies_router, sessions_router
from app.database import init_db_pool, close_db_pool
import asyncio
import json

# Инициализация приложения
app = FastAPI(
    title="Cinema Booking API",
    description="Микросервис для блокировки мест в кинотеатре с WebSocket",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(movies_router)
app.include_router(sessions_router)

# --- Схемы данных ---
class LockRequest(BaseModel):
    session_id: int
    seat_ids: List[int]
    user_id: int

class LockResponse(BaseModel):
    status: str
    message: str
    ttl_seconds: int | None = None

# --- Менеджер WebSocket соединений ---
class ConnectionManager:
    def __init__(self):
        # Храним соединения по session_id: {session_id: [websocket1, websocket2, ...]}
        self.active_connections: dict[int, list[WebSocket]] = {}

    async def connect(self, websocket: WebSocket, session_id: int):
        await websocket.accept()
        if session_id not in self.active_connections:
            self.active_connections[session_id] = []
        self.active_connections[session_id].append(websocket)

    def disconnect(self, websocket: WebSocket, session_id: int):
        if session_id in self.active_connections:
            self.active_connections[session_id].remove(websocket)
            if not self.active_connections[session_id]:
                del self.active_connections[session_id]

    async def broadcast_to_session(self, session_id: int, message: dict):
        """Отправляет сообщение всем подключенным к конкретному сеансу"""
        if session_id in self.active_connections:
            for connection in self.active_connections[session_id]:
                try:
                    await connection.send_json(message)
                except:
                    pass  # Игнорируем ошибки отключенных соединений

manager = ConnectionManager()

# --- Эндпоинты ---
@app.get("/")
async def root():
    return {"message": "Cinema Booking API is running", "status": "healthy"}

@app.post("/api/v1/bookings/lock", response_model=LockResponse)
async def lock_seats(request: LockRequest):
    """Блокирует места и публикует событие в Redis для WebSocket"""
    LOCK_TTL = 600
    
    lock_keys = [f"lock:session:{request.session_id}:seat:{seat_id}" for seat_id in request.seat_ids]
    
    pipe = redis_client.pipeline()
    for key in lock_keys:
        pipe.setnx(key, str(request.user_id))
    
    results = await pipe.execute()
    
    if not all(results):
        rollback_pipe = redis_client.pipeline()
        for i, key in enumerate(lock_keys):
            if results[i]:
                rollback_pipe.delete(key)
        await rollback_pipe.execute()
        
        raise HTTPException(
            status_code=409, 
            detail="Одно или несколько выбранных мест уже забронированы другим пользователем"
        )
    
    for key in lock_keys:
        await redis_client.expire(key, LOCK_TTL)
    
    # 🆕 ПУБЛИКУЕМ СОБЫТИЕ В REDIS ДЛЯ WEBSOCKET
    event = {
        "type": "seats_locked",
        "session_id": request.session_id,
        "seat_ids": request.seat_ids,
        "locked_by": request.user_id,
        "locked_until": LOCK_TTL
    }
    await redis_client.publish(f"session:{request.session_id}:events", json.dumps(event))
    
    return {
        "status": "success",
        "message": f"Места успешно заблокированы на {LOCK_TTL} секунд",
        "ttl_seconds": LOCK_TTL
    }

@app.get("/api/v1/bookings/check/{session_id}/{seat_id}")
async def check_seat(session_id: int, seat_id: int):
    """Проверка статуса конкретного места"""
    key = f"lock:session:{session_id}:seat:{seat_id}"
    is_locked = await redis_client.exists(key)
    ttl = await redis_client.ttl(key) if is_locked else 0
    
    return {
        "session_id": session_id,
        "seat_id": seat_id,
        "is_locked": bool(is_locked),
        "ttl_seconds": ttl if ttl > 0 else 0
    }

# WEBSOCKET ЭНДПОИНТ
@app.websocket("/ws/session/{session_id}")
async def websocket_endpoint(websocket: WebSocket, session_id: int):
    """WebSocket для real-time обновлений схемы зала"""
    await manager.connect(websocket, session_id)
    
    # Подписываемся на Redis канал для этого сеанса
    pubsub = redis_client.pubsub()
    await pubsub.subscribe(f"session:{session_id}:events")
    
    try:
        while True:
            # Слушаем сообщения из Redis
            message = await pubsub.get_message(ignore_subscribe_messages=True, timeout=1.0)
            
            if message and message['type'] == 'message':
                # Получили событие из Redis → отправляем всем подключенным
                event_data = json.loads(message['data'])
                await manager.broadcast_to_session(session_id, event_data)
            
            # Небольшая задержка, чтобы не нагружать CPU
            await asyncio.sleep(0.1)
    
    except WebSocketDisconnect:
        manager.disconnect(websocket, session_id)
    finally:
        await pubsub.unsubscribe(f"session:{session_id}:events")
        await pubsub.close()
        
@app.on_event("startup")
async def startup_event():
    """Инициализация при старте"""
    await init_db_pool()
    print("✅ MySQL connection pool initialized")
    
    asyncio.create_task(booking_worker.start())
    
@app.on_event("shutdown")
async def shutdown_event():
    """Очистка при остановке"""
    await close_db_pool()
    print("👋 MySQL connection pool closed")