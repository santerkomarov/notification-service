# Notification Service

Микросервис реализует массовую отправку Email/SMS-уведомлений через RabbitMQ с поддержкой приоритетов, idempotency, retry-механизма и отслеживания статусов доставки.

## Стек

- PHP 8.3
- Laravel 11
- PostgreSQL
- RabbitMQ
- Redis
- Nginx
- Docker Compose
- PHPUnit

## Функционал

- создание массовой рассылки уведомлений;
- методы отправки: `email`, `sms`;
- приоритеты: `high`, `low`;
- защита от дублей через `Idempotency-Key`;
- асинхронная обработка через RabbitMQ;
- mock-провайдеры Email/SMS;
- retry при временной ошибке;
- история попыток отправки в `delivery_attempts`;
- feature tests.

## Запуск

```
git clone <url>
cd <project folder>
cp (copy) .env.example .env (при необходимости)
docker compose up --build
```

Поднимаются:

- Laravel app;
- Nginx;
- PostgreSQL;
- Redis;
- RabbitMQ;
- worker для обработки уведомлений;
- Seeders.

Для Docker-запуска основные переменные окружения уже заданы в `docker-compose.yml`.

RabbitMQ UI доступен:

```
http://localhost:15672


Username: notification_user
Password: notification_pass
```


## Консольные команды

Проект рассчитан на запуск внутри Docker.

Пример команд:

```
docker compose exec app php artisan test
docker compose exec app php artisan route:list
```

## Запуск тестов

```
docker compose exec app php artisan test
```

Тесты проверяют:

- создание batch-рассылки;
- создание notifications;
- idempotency;
- валидацию запроса;
- успешную доставку email;
- drop при некорректном email;
- retry при временной ошибке;
- обработку RabbitMQ consumer;
- приоритет `high` перед `low`.


## API документация

Документация:

```
docs/openapi.yaml
```
Для просмотра можно воспользуйтся сервисом:  https://editor.swagger.io/

Далее: File → Import file

## Дополнительно

Остановка:

```
docker compose down
```

Полная пересборка:

```
docker compose down -v
docker compose up --build
```

## Замечания
Реальные Email/SMS не передаются )
