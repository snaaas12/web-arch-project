import redis
import json
import asyncio
import sys
import logging
from typing import Dict
from app.redis_client import redis_client

# Настраиваем логирование
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(message)s')
logger = logging.getLogger(__name__)

class BookingWorker:
    """
    Background worker для обработки запросов на блокировку мест
    """
    
    def __init__(self):
        self.request_queue = "booking:lock:requests"
        self.running = False
    
    async def start(self):
        """Запускает worker в бесконечном цикле"""
        self.running = True
        logger.info("🚀 Booking Worker started, listening for requests...")
        sys.stdout.flush()
        
        while self.running:
            try:
                # Используем lpop вместо blpop для простоты
                request_data = await redis_client.lpop(self.request_queue)
                
                if request_data:
                    logger.info(f"📥 Received request from queue")
                    request = json.loads(request_data)
                    
                    # Обрабатываем запрос
                    await self.process_request(request)
                else:
                    # Нет запросов — ждём 100ms
                    await asyncio.sleep(0.1)
                    
            except Exception as e:
                logger.error(f"❌ Worker error: {e}")
                sys.stdout.flush()
                await asyncio.sleep(1)
    
    async def process_request(self, request: Dict):
        """Обрабатывает один запрос на блокировку"""
        request_id = request['request_id']
        session_id = request['session_id']
        seat_ids = request['seat_ids']
        user_id = request['user_id']
        response_key = f"booking:lock:response:{request_id}"
        
        logger.info(f"🔒 Processing lock request: session={session_id}, seats={seat_ids}, user={user_id}")
        sys.stdout.flush()
        
        try:
            # Логика блокировки
            LOCK_TTL = 600
            lock_keys = [f"lock:session:{session_id}:seat:{seat_id}" for seat_id in seat_ids]
            
            # Проверяем и блокируем места атомарно
            pipe = redis_client.pipeline()
            for key in lock_keys:
                pipe.setnx(key, str(user_id))
            
            results = await pipe.execute()
            
            logger.info(f"🔍 SETNX results: {results}")
            sys.stdout.flush()
            
            if not all(results):
                # Откатываем успешные блокировки
                rollback_pipe = redis_client.pipeline()
                for i, key in enumerate(lock_keys):
                    if results[i]:
                        rollback_pipe.delete(key)
                await rollback_pipe.execute()
                
                response = {
                    'status': 'error',
                    'message': 'Одно или несколько мест уже забронированы',
                    'status_code': 409,
                }
                logger.info(f"❌ Lock failed: some seats already locked")
            else:
                # Устанавливаем TTL
                for key in lock_keys:
                    await redis_client.expire(key, LOCK_TTL)
                
                # Публикуем событие для WebSocket
                event = {
                    "type": "seats_locked",
                    "session_id": session_id,
                    "seat_ids": seat_ids,
                    "locked_by": user_id,
                    "locked_until": LOCK_TTL
                }
                await redis_client.publish(f"session:{session_id}:events", json.dumps(event))
                
                response = {
                    'status': 'success',
                    'message': f'Места успешно заблокированы на {LOCK_TTL} секунд',
                    'ttl_seconds': LOCK_TTL,
                }
                logger.info(f"✅ Lock successful: {seat_ids}")
            
            # Сохраняем ответ в Redis
            await redis_client.set(response_key, json.dumps(response), ex=10)
            logger.info(f"📤 Response saved to {response_key}")
            sys.stdout.flush()
            
        except Exception as e:
            logger.error(f"❌ Process request error: {e}")
            sys.stdout.flush()
            response = {
                'status': 'error',
                'message': 'Внутренняя ошибка сервиса',
                'status_code': 500,
            }
            await redis_client.set(response_key, json.dumps(response), ex=10)

# Глобальный экземпляр worker
booking_worker = BookingWorker()