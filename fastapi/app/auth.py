import jwt
from fastapi import HTTPException, Query, Header
from typing import Optional

# Общий секрет (должен совпадать с Laravel)
SECRET_KEY = 'cinema-booking-secret-key-change-in-production'

async def get_current_user_from_query(token: Optional[str] = Query(None)):
    """
    Проверяет JWT токен из query параметра ?token=...
    
    Для WebSocket используем query параметр, потому что заголовки
    не поддерживаются при подключении WebSocket из браузера.
    """
    if not token:
        raise HTTPException(status_code=401, detail='Token required')
    
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=['HS256'])
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail='Token expired')
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail='Invalid token')


async def get_current_user_from_header(authorization: Optional[str] = Header(None)):
    """
    Проверяет JWT токен из заголовка Authorization: Bearer ...
    
    Для REST API используем заголовок.
    """
    if not authorization or not authorization.startswith('Bearer '):
        raise HTTPException(status_code=401, detail='Token required')
    
    token = authorization.split(' ')[1]
    
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=['HS256'])
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail='Token expired')
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail='Invalid token')