from fastapi import APIRouter, HTTPException, Query
from app.database import get_db
from typing import Optional
from datetime import datetime

router = APIRouter(prefix="/api/v1/movies", tags=["movies"])


@router.get("/")
async def get_movies(
    page: int = Query(1, ge=1),
    per_page: int = Query(12, ge=1, le=100),
    genre: Optional[str] = None,
    search: Optional[str] = None,
):
    """
    GET /api/v1/movies — список фильмов с пагинацией и фильтрацией
    
    Параметры:
    - page: номер страницы (по умолчанию 1)
    - per_page: количество на странице (по умолчанию 12)
    - genre: фильтр по жанру
    - search: поиск по названию
    """
    offset = (page - 1) * per_page
    
    # Базовый запрос
    query = "SELECT m.*, u.name as author_name FROM movies m LEFT JOIN users u ON m.user_id = u.id WHERE 1=1"
    params = []
    
    # Фильтр по жанру
    if genre:
        query += " AND m.genre = %s"
        params.append(genre)
    
    # Поиск по названию
    if search:
        query += " AND m.title LIKE %s"
        params.append(f"%{search}%")
    
    # Сортировка и лимит
    query += " ORDER BY m.created_at DESC LIMIT %s OFFSET %s"
    params.extend([per_page, offset])
    
    async with get_db() as cur:
        # Получаем фильмы
        await cur.execute(query, params)
        movies = await cur.fetchall()
        
        # Получаем общее количество
        count_query = "SELECT COUNT(*) as total FROM movies m WHERE 1=1"
        count_params = []
        if genre:
            count_query += " AND m.genre = %s"
            count_params.append(genre)
        if search:
            count_query += " AND m.title LIKE %s"
            count_params.append(f"%{search}%")
        
        await cur.execute(count_query, count_params)
        total = (await cur.fetchone())['total']
    
    # Преобразуем datetime в строки
    for movie in movies:
        if movie['created_at']:
            movie['created_at'] = movie['created_at'].isoformat()
        if movie['rating']:
            movie['rating'] = float(movie['rating'])
    
    return {
        "success": True,
        "data": movies,
        "pagination": {
            "page": page,
            "per_page": per_page,
            "total": total,
            "total_pages": (total + per_page - 1) // per_page,
        }
    }


@router.get("/{movie_id}")
async def get_movie(movie_id: int):
    """
    GET /api/v1/movies/{id} — один фильм с сеансами
    """
    async with get_db() as cur:
        # Получаем фильм
        await cur.execute(
            "SELECT m.*, u.name as author_name FROM movies m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = %s",
            (movie_id,)
        )
        movie = await cur.fetchone()
        
        if not movie:
            raise HTTPException(status_code=404, detail="Фильм не найден")
        
        # Получаем сеансы
        await cur.execute(
            """
            SELECT s.*, h.name as hall_name 
            FROM sessions s 
            JOIN halls h ON s.hall_id = h.id 
            WHERE s.movie_id = %s AND s.start_time > NOW()
            ORDER BY s.start_time ASC
            """,
            (movie_id,)
        )
        sessions = await cur.fetchall()
    
    # Преобразуем данные
    if movie['created_at']:
        movie['created_at'] = movie['created_at'].isoformat()
    if movie['rating']:
        movie['rating'] = float(movie['rating'])
    
    for session in sessions:
        session['start_time'] = session['start_time'].isoformat()
        session['end_time'] = session['end_time'].isoformat()
        session['base_price'] = float(session['base_price'])
    
    return {
        "success": True,
        "data": {
            **movie,
            "sessions": sessions,
        }
    }


@router.get("/{movie_id}/sessions")
async def get_movie_sessions(movie_id: int):
    """
    GET /api/v1/movies/{id}/sessions — сеансы конкретного фильма
    """
    async with get_db() as cur:
        # Проверяем, что фильм существует
        await cur.execute("SELECT id FROM movies WHERE id = %s", (movie_id,))
        if not await cur.fetchone():
            raise HTTPException(status_code=404, detail="Фильм не найден")
        
        # Получаем сеансы
        await cur.execute(
            """
            SELECT s.*, h.name as hall_name, h.rows_count, h.seats_per_row
            FROM sessions s 
            JOIN halls h ON s.hall_id = h.id 
            WHERE s.movie_id = %s AND s.start_time > NOW()
            ORDER BY s.start_time ASC
            """,
            (movie_id,)
        )
        sessions = await cur.fetchall()
    
    for session in sessions:
        session['start_time'] = session['start_time'].isoformat()
        session['end_time'] = session['end_time'].isoformat()
        session['base_price'] = float(session['base_price'])
    
    return {
        "success": True,
        "data": sessions,
    }