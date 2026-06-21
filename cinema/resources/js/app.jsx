import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import SeatMap from './components/SeatMap';

// Инициализация React-компонента на странице сеанса
document.addEventListener('DOMContentLoaded', () => {
    // Находим все div'ы с id начинающимся на "seat-map-"
    const seatMapContainers = document.querySelectorAll('[id^="seat-map-"]');
    
    seatMapContainers.forEach(container => {
        const sessionId = container.dataset.sessionId;
        const root = createRoot(container);
        root.render(<SeatMap sessionId={parseInt(sessionId)} />);
    });
});