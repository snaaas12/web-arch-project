import React, { useState, useEffect } from 'react';

const SeatMap = ({ sessionId }) => {
    const [seats, setSeats] = useState([]);
    const [stats, setStats] = useState({ total: 0, free: 0, locked: 0, booked: 0 });
    const [jwt, setJwt] = useState(null);
    const [selectedSeats, setSelectedSeats] = useState([]);
    const [loading, setLoading] = useState(true);
    const [userId, setUserId] = useState(null);
    const [isLocked, setIsLocked] = useState(false); // Флаг: места заблокированы?
    const [lockTimer, setLockTimer] = useState(null); // Таймер обратного отсчёта

    // 1. Получаем JWT и user_id при загрузке
    useEffect(() => {
        fetch('/api/me', { credentials: 'include' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (data?.token) {
                    setJwt(data.token);
                    setUserId(data.user?.id || 2);
                }
            })
            .catch(err => console.error('Error getting JWT:', err));
    }, []);

    // 2. Загружаем начальное состояние мест
    useEffect(() => {
        if (!jwt) return;

        const loadSeats = async () => {
            try {
                const response = await fetch(`https://api.cinema.local:8443/api/v1/sessions/${sessionId}/seats`, {
                    headers: { 'Authorization': `Bearer ${jwt}` }
                });
                
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                if (data.success) {
                    setSeats(data.data.seats);
                    setStats(data.data.stats);
                    setLoading(false);
                }
            } catch (err) {
                console.error('Error loading seats:', err);
                setLoading(false);
            }
        };

        loadSeats();
    }, [sessionId, jwt]);

    // 3. WebSocket для real-time обновлений
    useEffect(() => {
        if (!jwt) return;

        const wsUrl = `wss://api.cinema.local:8443/ws/session/${sessionId}?token=${jwt}`;
        const websocket = new WebSocket(wsUrl);

        websocket.onopen = () => console.log('✅ WebSocket connected');

        websocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log('📩 WebSocket message:', data);
                
                if (data.type === 'seats_locked') {
                    // Обновляем статус мест (только если заблокировал ДРУГОЙ пользователь)
                    setSeats(prevSeats => {
                        const newSeats = prevSeats.map(row => 
                            row.map(seat => {
                                if (data.seat_ids.includes(seat.id) && data.locked_by !== userId) {
                                    return { ...seat, status: 'locked', locked_by: data.locked_by };
                                }
                                return seat;
                            })
                        );
                        return newSeats;
                    });

                    // Пересчитываем статистику
                    let newFree = 0, newLocked = 0, newBooked = 0;
                    seats.forEach(row => row.forEach(seat => {
                        if (seat.status === 'free') newFree++;
                        else if (seat.status === 'locked') newLocked++;
                        else if (seat.status === 'booked') newBooked++;
                    }));
                    
                    setStats({ total: newFree + newLocked + newBooked, free: newFree, locked: newLocked, booked: newBooked });
                }
            } catch (err) {
                console.error('Error processing WebSocket message:', err);
            }
        };

        websocket.onclose = () => console.log('👋 WebSocket disconnected');

        return () => {
            if (websocket.readyState === WebSocket.OPEN) websocket.close();
        };
    }, [sessionId, jwt, userId]);

    // 4. Обработчик выбора мест
    const handleSeatClick = (seat) => {
        if (seat.status !== 'free' || isLocked) return;
        
        setSelectedSeats(prev => {
            const isSelected = prev.find(s => s.id === seat.id);
            return isSelected 
                ? prev.filter(s => s.id !== seat.id)
                : [...prev, seat];
        });
    };

    // 5. БЛОКИРОВКА мест (первый этап)
    const handleLockSeats = async () => {
        if (selectedSeats.length === 0 || !jwt || !userId) {
            alert('Выберите места для бронирования');
            return;
        }

        const seatIds = selectedSeats.map(s => s.id);
        console.log('🔒 Блокируем места:', seatIds);

        try {
            const response = await fetch('https://api.cinema.local:8443/api/v1/bookings/lock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${jwt}`
                },
                body: JSON.stringify({ 
                    session_id: sessionId, 
                    seat_ids: seatIds, 
                    user_id: userId 
                })
            });

            if (response.ok) {
                const result = await response.json();
                console.log('✅ Места заблокированы:', result);
                alert(`Места заблокированы на ${result.ttl_seconds} секунд!`);
                
                // Обновляем статус мест локально
                setSeats(prevSeats => {
                    const newSeats = prevSeats.map(row => 
                        row.map(seat => {
                            if (seatIds.includes(seat.id)) {
                                return { ...seat, status: 'locked', locked_by: userId };
                            }
                            return seat;
                        })
                    );
                    return newSeats;
                });

                // Устанавливаем флаг блокировки
                setIsLocked(true);
                
                // Запускаем таймер (10 минут = 600 секунд)
                let timeLeft = 600;
                const timer = setInterval(() => {
                    timeLeft--;
                    setLockTimer(timeLeft);
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        setIsLocked(false);
                        setSelectedSeats([]);
                        alert('Время бронирования истекло. Места освобождены.');
                    }
                }, 1000);

                // Сохраняем timer для очистки
                window.lockTimer = timer;
            } else {
                const error = await response.json();
                alert('Ошибка: ' + (error.detail || 'Не удалось заблокировать места'));
            }
        } catch (err) {
            console.error('🚨 Lock error:', err);
            alert('Ошибка при блокировке: ' + err.message);
        }
    };

    // 6. ПОКУПКА билетов (второй этап)
    const handlePurchaseTickets = async () => {
        if (!jwt || !userId) return;

        // Получаем все заблокированные нами места
        const myLockedSeats = seats.flat().filter(seat => 
            seat.status === 'locked' && seat.locked_by === userId
        );

        const seatIds = myLockedSeats.map(s => s.id);
        console.log('🎫 Покупаем билеты:', seatIds);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (!csrfToken) {
                alert('CSRF токен не найден. Обновите страницу.');
                return;
            }

            const response = await fetch('/bookings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    session_id: sessionId, 
                    seat_ids: seatIds 
                })
            });

            if (response.ok) {
                const result = await response.json();
                console.log('✅ Билеты куплены:', result);
                alert(`Билеты успешно получены! Заказ #${result.booking.id}`);
                
                // Обновляем статус мест
                setSeats(prevSeats => {
                    const newSeats = prevSeats.map(row => 
                        row.map(seat => {
                            if (seatIds.includes(seat.id)) {
                                return { ...seat, status: 'booked' };
                            }
                            return seat;
                        })
                    );
                    return newSeats;
                });

                // Сбрасываем состояние
                setIsLocked(false);
                setSelectedSeats([]);
                if (window.lockTimer) clearInterval(window.lockTimer);
                
                // Перенаправляем на страницу билета
                window.location.href = `/my-bookings/${result.booking.id}`;
            } else {
                const error = await response.json();
                alert('Ошибка: ' + (error.message || 'Не удалось купить билеты'));
            }
        } catch (err) {
            console.error('🚨 Purchase error:', err);
            alert('Ошибка при покупке: ' + err.message);
        }
    };

    // 7. Определение цвета места
    const getSeatColor = (seat) => {
        const isSelected = selectedSeats.find(s => s.id === seat.id);
        
        if (isSelected) {
            return 'bg-blue-600 hover:bg-blue-700 ring-2 ring-blue-300 ring-offset-1 transform scale-110 shadow-xl';
        }
        
        switch (seat.status) {
            case 'free':
                return 'bg-gray-300 text-gray-700 hover:bg-black hover:text-white cursor-pointer transform hover:scale-110 shadow-md hover:shadow-xl transition-all duration-200';
            case 'locked':
                // Если заблокировано нами — жёлтое, если другим — тоже жёлтое (но с opacity)
                if (seat.locked_by === userId) {
                    return 'bg-yellow-500 text-white cursor-not-allowed shadow-lg';
                } else {
                    return 'bg-yellow-500 text-white cursor-not-allowed opacity-75 shadow-lg';
                }
            case 'booked':
                return 'bg-red-500 text-white cursor-not-allowed opacity-75';
            default:
                return 'bg-gray-100 text-gray-400';
        }
    };

    if (loading) return <div className="text-center p-8">Загрузка схемы зала...</div>;
    if (!jwt) return <div className="text-center p-8 text-red-500 font-semibold">Требуется авторизация</div>;

    return (
        <div className="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <h3 className="text-xl font-bold mb-4 text-gray-800">Схема зала</h3>
            
            {/* Статистика */}
            <div className="flex flex-wrap gap-4 mb-6 text-sm">
                <div className="flex items-center bg-gray-100 px-3 py-1 rounded">
                    <div className="w-4 h-4 bg-gray-300 border border-gray-400 mr-2 rounded"></div>
                    <span className="font-medium">Свободно: <strong>{stats.free}</strong></span>
                </div>
                <div className="flex items-center bg-yellow-50 px-3 py-1 rounded">
                    <div className="w-4 h-4 bg-yellow-500 mr-2 rounded"></div>
                    <span className="font-medium">Заблокировано: <strong>{stats.locked}</strong></span>
                </div>
                <div className="flex items-center bg-red-50 px-3 py-1 rounded">
                    <div className="w-4 h-4 bg-red-500 mr-2 rounded"></div>
                    <span className="font-medium">Куплено: <strong>{stats.booked}</strong></span>
                </div>
            </div>

            {/* Блок управления */}
            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                {!isLocked ? (
                    <>
                        {selectedSeats.length > 0 ? (
                            <>
                                <p className="font-semibold text-blue-900 mb-2">
                                    Выбрано мест: <strong>{selectedSeats.length}</strong>
                                </p>
                                <p className="text-sm text-blue-700 mb-3">
                                    Ряды: {selectedSeats.map(s => `${s.row}-${s.number}`).join(', ')}
                                </p>
                                <button
                                    onClick={handleLockSeats}
                                    className="bg-yellow-600 hover:bg-yellow-700 text-black font-bold py-2 px-6 rounded-lg transition duration-200 shadow"
                                >
                                    Забронировать
                                </button>
                                <button
                                    onClick={() => setSelectedSeats([])}
                                    className="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200"
                                >
                                    Отмена
                                </button>
                            </>
                        ) : (
                            <p className="text-gray-600">Выберите места и нажмите "Забронировать"</p>
                        )}
                    </>
                ) : (
                    <>
                        <p className="font-semibold text-blue-900 mb-2">
                            ✅ Места заблокированы!
                        </p>
                        {lockTimer && (
                            <p className="text-sm text-blue-700 mb-3">
                                Осталось времени: <strong>{Math.floor(lockTimer / 60)}:{(lockTimer % 60).toString().padStart(2, '0')}</strong>
                            </p>
                        )}
                        <button
                            onClick={handlePurchaseTickets}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200 shadow"
                        >
                            Получить билеты
                        </button>
                        <button
                            onClick={() => {
                                setIsLocked(false);
                                setSelectedSeats([]);
                                if (window.lockTimer) clearInterval(window.lockTimer);
                                alert('Бронирование отменено');
                            }}
                            className="ml-2 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200"
                        >
                            Отменить бронирование
                        </button>
                    </>
                )}
            </div>

            {/* Схема зала */}
            <div className="mb-6">
                <div className="flex justify-center mb-4">
                    <div className="bg-gradient-to-b from-gray-300 to-gray-400 w-3/4 px-8 py-2 rounded-t-lg text-sm font-semibold text-gray-700 shadow text-center">
                        ЭКРАН
                    </div>
                </div>
                <div className="bg-gray-50 p-6 rounded-lg border border-gray-300">
                    <div className="space-y-3">
                        {seats.map((row, rowIndex) => (
                            <div key={rowIndex} className="flex items-center gap-3">
                                <div className="w-8 text-center font-bold text-gray-600 text-sm">
                                    {rowIndex + 1}
                                </div>
                                <div className="flex gap-3 justify-center flex-1">
                                    {row.map((seat) => (
                                        <button
                                            key={seat.id}
                                            onClick={() => handleSeatClick(seat)}
                                            disabled={seat.status !== 'free' || isLocked}
                                            className={`
                                                w-10 h-10 rounded-lg 
                                                ${getSeatColor(seat)}
                                                flex items-center justify-center 
                                                text-sm font-bold 
                                                transition-all duration-200
                                                shadow-sm
                                            `}
                                            title={`Ряд ${seat.row}, Место ${seat.number}\nСтатус: ${seat.status}`}
                                        >
                                            {seat.number}
                                        </button>
                                    ))}
                                </div>
                                <div className="w-8 text-center font-bold text-gray-600 text-sm">
                                    {rowIndex + 1}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Легенда */}
            <div className="mt-6 text-sm text-gray-600 space-y-2 p-4 bg-gray-50 rounded-lg">
                <p className="font-semibold mb-2">💡 Как пользоваться:</p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div className="flex items-center">
                        <div className="w-4 h-4 bg-gray-300 border border-gray-400 mr-2 rounded"></div>
                        <span>Свободно (чернеет при наведении)</span>
                    </div>
                    <div className="flex items-center">
                        <div className="w-4 h-4 bg-blue-600 mr-2 rounded"></div>
                        <span>Выбрано вами</span>
                    </div>
                    <div className="flex items-center">
                        <div className="w-4 h-4 bg-yellow-500 mr-2 rounded"></div>
                        <span>Заблокировано (ждёт оплаты)</span>
                    </div>
                    <div className="flex items-center">
                        <div className="w-4 h-4 bg-red-500 mr-2 rounded opacity-75"></div>
                        <span>Куплено</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SeatMap;