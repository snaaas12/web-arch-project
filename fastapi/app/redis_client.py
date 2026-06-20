import redis.asyncio as redis

# Подключение к Redis (имя сервиса 'redis' из docker-compose.yml)
redis_client = redis.from_url("redis://redis:6379/0", decode_responses=True)
