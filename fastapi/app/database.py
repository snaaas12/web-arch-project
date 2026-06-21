import aiomysql
import os
from contextlib import asynccontextmanager

# Настройки подключения к MySQL (read-only)
MYSQL_CONFIG = {
    "host": os.getenv("MYSQL_HOST", "mysql"),
    "port": int(os.getenv("MYSQL_PORT", 3306)),
    "user": os.getenv("MYSQL_USER", "cinema_user"),
    "password": os.getenv("MYSQL_PASSWORD", "cinema_password"),
    "db": os.getenv("MYSQL_DATABASE", "cinema_db"),
    "autocommit": True,
}

pool = None


async def init_db_pool():
    """Инициализация пула соединений при старте"""
    global pool
    pool = await aiomysql.create_pool(**MYSQL_CONFIG, minsize=1, maxsize=10)


async def close_db_pool():
    """Закрытие пула при остановке"""
    global pool
    if pool:
        pool.close()
        await pool.wait_closed()


@asynccontextmanager
async def get_db():
    """Получить соединение из пула"""
    global pool
    if pool is None:
        await init_db_pool()
    
    async with pool.acquire() as conn:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            yield cur