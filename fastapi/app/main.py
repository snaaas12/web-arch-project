from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI(
    title="Cinema Booking API",
    description="Микросервис для бронирования мест в кинотеатре",
    version="1.0.0",
    docs_url=None,  # Отключаем стандартный Swagger
    redoc_url="/redoc"  # Оставляем ReDoc
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
async def root():
    return {
        "message": "Cinema Booking API is running",
        "status": "healthy",
        "version": "1.0.0",
        "docs": "/redoc"
    }

@app.get("/health")
async def health_check():
    return {"status": "ok"}

@app.get("/api/v1/info")
async def info():
    return {
        "service": "FastAPI Booking Service",
        "endpoints": [
            "GET /",
            "GET /health",
            "GET /api/v1/info",
            "POST /api/v1/bookings/lock"
        ]
    }