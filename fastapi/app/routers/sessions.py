from fastapi import APIRouter, HTTPException
from app.database import get_db
from app.redis_client import redis_client

router = APIRouter(prefix="/api/v1/sessions", tags=["sessions"])


@router.get("/{session_id}")
async def get_session(session_id: int):
    """
    GET /api/v1/sessions/{id} — информация о сеансе
    """
    async with get_db() as cur:
        await cur.execute(
            """
            SELECT s.*, h.name as hall_name, h.rows_count, h.seats_per_row, h.type as hall_type,
                   m.title as movie_title, m.duration as movie_duration, m.poster_url
            FROM sessions s 
            JOIN halls h ON s.hall_id = h.id 
            JOIN movies m ON s.movie_id = m.id
            WHERE s.id = %s
            """,
            (session_id,)
        )
        session = await cur.fetchone()
    
    if not session:
        raise HTTPException(status_code=404, detail="Сеанс не найден")
    
    session['start_time'] = session['start_time'].isoformat()
    session['end_time'] = session['end_time'].isoformat()
    session['base_price'] = float(session['base_price'])
    
    return {
        "success": True,
        "data": session,
    }


@router.get("/{session_id}/seats")
async def get_session_seats(session_id: int):
    """
    GET /api/v1/sessions/{id}/seats — статус всех мест на сеансе
    
    Возвращает:
    - Матрицу мест (rows × seats_per_row)
    - Статус каждого места: free, locked, booked
    """
    async with get_db() as cur:
        # Получаем информацию о сеансе и зале
        await cur.execute(
            """
            SELECT s.*, h.rows_count, h.seats_per_row
            FROM sessions s 
            JOIN halls h ON s.hall_id = h.id 
            WHERE s.id = %s
            """,
            (session_id,)
        )
        session = await cur.fetchone()
    
    if not session:
        raise HTTPException(status_code=404, detail="Сеанс не найден")
    
    rows_count = session['rows_count']
    seats_per_row = session['seats_per_row']
    
    # Получаем занятые места из БД (уже купленные билеты)
    async with get_db() as cur:
        await cur.execute(
            """
            SELECT seats FROM bookings 
            WHERE session_id = %s AND status = 'confirmed'
            """,
            (session_id,)
        )
        bookings = await cur.fetchall()
    
    booked_seats = set()
    for booking in bookings:
        # seats хранится как JSON массив
        import json
        seats = json.loads(booking['seats']) if isinstance(booking['seats'], str) else booking['seats']
        booked_seats.update(seats)
    
    # Получаем заблокированные места из Redis
    locked_seats = {}
    lock_keys = await redis_client.keys(f"lock:session:{session_id}:seat:*")
    for key in lock_keys:
        seat_id = int(key.split(':')[-1])
        user_id = await redis_client.get(key)
        ttl = await redis_client.ttl(key)
        locked_seats[seat_id] = {
            "user_id": int(user_id) if user_id else None,
            "ttl_seconds": ttl if ttl > 0 else 0,
        }
    
    # Формируем матрицу мест
    seats_matrix = []
    for row in range(1, rows_count + 1):
        row_seats = []
        for seat in range(1, seats_per_row + 1):
            seat_id = (row - 1) * seats_per_row + seat
            
            if seat_id in booked_seats:
                status = "booked"
            elif seat_id in locked_seats:
                status = "locked"
            else:
                status = "free"
            
            row_seats.append({
                "id": seat_id,
                "row": row,
                "number": seat,
                "status": status,
                "locked_by": locked_seats.get(seat_id, {}).get("user_id"),
                "locked_ttl": locked_seats.get(seat_id, {}).get("ttl_seconds"),
            })
        seats_matrix.append(row_seats)
    
    return {
        "success": True,
        "data": {
            "session_id": session_id,
            "rows_count": rows_count,
            "seats_per_row": seats_per_row,
            "seats": seats_matrix,
            "stats": {
                "total": rows_count * seats_per_row,
                "free": sum(1 for row in seats_matrix for s in row if s['status'] == 'free'),
                "locked": sum(1 for row in seats_matrix for s in row if s['status'] == 'locked'),
                "booked": sum(1 for row in seats_matrix for s in row if s['status'] == 'booked'),
            }
        }
    }