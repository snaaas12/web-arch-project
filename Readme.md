# Cinema Booking — Микросервисная система бронирования билетов в кинотеатр

## 1. Описание

**Cinema Booking** — это веб-приложение для онлайн-бронирования билетов в кинотеатр, реализованное по микросервисной архитектуре. Система состоит из нескольких независимых сервисов: **Laravel** (основное приложение и CRUD фильмов), **FastAPI** (высоконагруженный API для работы с местами и WebSocket), **React** (интерактивная схема зала в реальном времени), **MySQL** (основное хранилище данных) и **Redis** (брокер событий и кэш блокировок). Пользователи могут регистрироваться, добавлять фильмы, выбирать места на интерактивной схеме зала, бронировать их и получать электронные билеты с QR-кодами. Обновление статусов мест происходит в реальном времени через WebSocket, что позволяет нескольким пользователям видеть изменения мгновенно.

---

## 2. Схема архитектуры

Проект построен по микросервисной архитектуре и состоит из 6 независимых сервисов, взаимодействующих через HTTPS и WebSocket.

### Компоненты системы

**Браузер (Клиент)**
- **Blade Templates (Laravel)** — серверно-рендеринговые страницы: список фильмов, CRUD фильмов, профиль, билеты
- **React Component (SeatMap)** — интерактивная схема зала с real-time обновлениями через WebSocket, использует JWT для авторизации в FastAPI

**Nginx (Reverse Proxy)**
- Принимает все HTTPS-запросы и маршрутизирует их по доменам:
  - `cinema.local:8443` → перенаправляет на Laravel (PHP-FPM :9000)
  - `api.cinema.local:8443` → перенаправляет на FastAPI (Uvicorn :8000)
- Обрабатывает WebSocket-соединения для real-time обновлений
- Обеспечивает HTTPS через самоподписанный SSL-сертификат

**Laravel (PHP 8.x) — основной бэкенд**
- CRUD операции с фильмами (создание, чтение, обновление, удаление)
- Аутентификация пользователей (регистрация, вход, GitHub OAuth mock)
- Генерация JWT токенов для React-компонента (endpoint `/api/me`)
- Создание бронирований и генерация QR-кодов для билетов
- Хранит данные в MySQL через Eloquent ORM

**FastAPI (Python 3.11) — API для мест и WebSocket**
- REST API для получения списка фильмов, сеансов и статусов мест (read-only доступ к MySQL)
- WebSocket сервер для real-time обновлений схемы зала
- Блокировка мест в Redis с TTL (10 минут)
- Pub/Sub через Redis для рассылки событий всем подключённым клиентам
- Проверка JWT токенов для защиты критичных endpoint'ов

**MySQL 8.0 — основное хранилище данных**
- Хранит все бизнес-данные: пользователи, фильмы, залы, сеансы, бронирования
- Доступна одновременно Laravel (чтение/запись) и FastAPI (только чтение)
- Пользователь `cinema_user` с правами на `cinema_db`

**Redis 8.x — кэш и брокер событий**
- Хранит временные блокировки мест с ключами `lock:session:{id}:seat:{id}`
- Работает как Pub/Sub брокер: канал `session:{id}:events` для real-time обновлений
- Автоматически освобождает места по истечении TTL (10 минут)

### Поток данных при бронировании

1. Пользователь открывает страницу фильма → Laravel рендерит Blade-шаблон с React-компонентом схемы зала
2. React-компонент запрашивает JWT у Laravel через `/api/me` (сессионная авторизация)
3. React подключается к WebSocket FastAPI с JWT в query-параметре
4. React загружает начальное состояние мест через `GET /api/v1/sessions/{id}/seats` в FastAPI
5. Пользователь выбирает места и нажимает "Забронировать"
6. React отправляет `POST /api/v1/bookings/lock` в FastAPI с JWT в заголовке
7. FastAPI проверяет JWT, блокирует места в Redis и публикует событие в канал Redis
8. WebSocket рассылает обновление всем подключённым клиентам → места становятся жёлтыми у всех пользователей
9. React отправляет `POST /bookings` в Laravel с CSRF-токеном
10. Laravel создаёт запись `Booking` в MySQL, генерирует QR-код через внешний API
11. Laravel удаляет блокировки из Redis → места становятся купленными (красными)
12. Пользователь перенаправляется на страницу билета с QR-кодом


---

## 3. Запуск проекта

### Предварительные требования
- Docker & Docker Compose
- Git

### Шаги установки

```bash
# 1. Клонировать репозиторий
git clone <your-repo-url>
cd web-arch-project

# 2. Добавить домены в hosts
sudo nano /etc/hosts
# Добавить строки:
# 127.0.0.1   cinema.local
# 127.0.0.1   api.cinema.local

# 3. Скопировать переменные окружения
cp .env.example .env
# Отредактировать при необходимости

# 4. Запустить контейнеры
docker compose up -d

# 5. Дождаться инициализации MySQL (~30 сек), затем:
# Установить зависимости Laravel
docker compose exec laravel composer install
docker compose exec laravel cp .env.example .env
docker compose exec laravel php artisan key:generate

# 6. Выполнить миграции
docker compose exec laravel php artisan migrate

# 7. Запустить сидеры (тестовые данные)
docker compose exec laravel php artisan db:seed

# 8. Установить npm-зависимости и собрать ассеты
docker compose exec laravel npm install
docker compose exec laravel npm run build

# 9. Создать symlink для storage
docker compose exec laravel php artisan storage:link

# 10. Настроить права MySQL для FastAPI
docker compose exec mysql mysql -u root -proot_password -e "
CREATE USER IF NOT EXISTS 'cinema_user'@'%' IDENTIFIED BY 'secret_password';
GRANT ALL PRIVILEGES ON cinema_db.* TO 'cinema_user'@'%';
FLUSH PRIVILEGES;
"

# 11. Установить PyJWT в FastAPI
docker compose exec fastapi pip install PyJWT aiomysql

# 12. Перезапустить FastAPI
docker compose restart fastapi
```

## 4.  Основные сценарии использования
### Регистрация и вход
Перейти на https://cinema.local:8443/register
Создать аккаунт (или войти через GitHub OAuth mock)
После входа автоматически генерируется JWT для работы с FastAPI
### Работа с фильмами
Список фильмов — главная страница /movies с карточками фильмов
Добавить фильм — кнопка "+ Добавить фильм" на странице списка
Мои фильмы — страница /my-movies с возможностью редактирования и удаления своих фильмов
Подробнее — страница фильма с описанием, рейтингом и списком сеансов
### Бронирование и покупка билетов
Открыть фильм → выбрать сеанс
На интерактивной схеме зала выбрать места (клик по чёрным местам)
Нажать "Забронировать" — места становятся жёлтыми (заблокированы на 10 минут)
Нажать "Получить билеты" — создаётся бронирование, генерируется QR-код
Автоматический переход на страницу билета с QR-кодом
### Мои билеты
Страница /my-bookings со списком всех купленных билетов
Каждый билет содержит: фильм, дату, зал, места, QR-код
Возможность скачать QR-код
### Real-time обновления
Открыть один и тот же сеанс в двух браузерах
В одном браузере заблокировать места → во втором они мгновенно станут жёлтыми
Работает через WebSocket + Redis Pub/Sub
### Профиль
Страница /profile с возможностью изменить имя, email, пароль
Выход из аккаунта через выпадающее меню

## 5.  Структура базы данных

### Таблицы

**users** — пользователи системы
- `id` (PK), `name`, `email` (unique), `password`, `github_id` (nullable), `avatar` (nullable), `created_at`, `updated_at`

**movies** — фильмы, добавленные пользователями
- `id` (PK), `user_id` (FK → users), `title`, `description`, `duration`, `genre`, `rating`, `poster_url` (nullable), `age_restriction`, `created_at`, `updated_at`

**halls** — кинозалы (создаются через сидеры)
- `id` (PK), `name`, `rows_count`, `seats_per_row`, `type`, `created_at`, `updated_at`

**sessions** — сеансы фильмов в залах
- `id` (PK), `movie_id` (FK → movies), `hall_id` (FK → halls), `start_time`, `end_time`, `format`, `base_price`, `created_at`, `updated_at`

**bookings** — бронирования и билеты пользователей
- `id` (PK), `user_id` (FK → users), `session_id` (FK → sessions), `seats` (JSON массив ID мест), `status` (enum: pending/confirmed/cancelled/expired), `total_price`, `qr_code` (путь к файлу), `locked_until`, `created_at`, `updated_at`

### Связи между таблицами

- **users → movies** (1:N) — один пользователь может добавить много фильмов
- **users → bookings** (1:N) — у пользователя может быть много бронирований
- **movies → sessions** (1:N) — у фильма может быть много сеансов
- **halls → sessions** (1:N) — в зале может проходить много сеансов
- **sessions → bookings** (1:N) — на сеансе может быть много бронирований

### Redis (дополнительное хранилище)

- Ключ `lock:session:{session_id}:seat:{seat_id}` — хранит `user_id` заблокировавшего, TTL 10 минут
- Канал `session:{session_id}:events` — Pub/Sub для real-time обновлений через WebSocket