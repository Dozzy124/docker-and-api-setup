# Ветка: `имя/docker-and-api-setup`

Инфраструктура для локальной разработки + эндпоинт `GET /api/health`.

## Что внутри

```
docker-compose.yml
docker/php/Dockerfile
docker/php/local.ini
docker/nginx/default.conf
.env.example
config/horizon.php
app/Http/Controllers/Api/HealthController.php
routes/api.php
```

## Как применить к проекту

Если Laravel-приложение ещё не создано:

```bash
composer create-project laravel/laravel medai-backend
cd medai-backend
```

Скопировать файлы из этого набора в корень проекта (они не перезатирают
ничего специфичного для конкретного проекта, кроме `routes/api.php` —
если там уже что-то есть, смёржить вручную, добавив только строку с
`/health`).

Перевести приложение в API-only режим (убрать web-специфичные провайдеры,
Blade и т.д.) — начиная с Laravel 11 это делается флагом при создании
проекта (`--no-interaction` + правка `bootstrap/app.php`, убрать
`withRouting(web: ...)` если веб-часть не нужна) либо вручную отключить
`SESSION`/`web` middleware group там, где не требуется.

## Запуск

```bash
cp .env.example .env

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# опционально — Horizon-конфиг уже лежит в config/horizon.php,
# но если пакет ещё не установлен:
docker compose exec app composer require laravel/horizon
docker compose exec app php artisan horizon:install
```

Сервисы после `up -d`:

| Сервис    | Назначение                          | Порт (host)         |
|-----------|--------------------------------------|----------------------|
| nginx     | точка входа API                      | `8000` → `/api/...`  |
| app       | php-fpm (Laravel)                    | внутренний, `9000`   |
| horizon   | воркер очередей (тот же образ, `php artisan horizon`) | — |
| postgres  | БД                                    | `5432`                |
| redis     | очереди / кэш / Horizon              | `6379`                |
| mailhog   | перехват писем (регистрация, сброс пароля) | UI: `8025`, SMTP: `1025` |

## Проверка

```bash
curl http://localhost:8000/api/health
```

Ожидаемый ответ:

```json
{
  "status": "ok",
  "timestamp": "2026-09-23T12:00:00+00:00",
  "services": {
    "database": { "connected": true, "driver": "pgsql", "latency_ms": 2.1 },
    "redis": { "connected": true, "ping": "PONG", "latency_ms": 0.8 }
  }
}
```

Если `database.connected` или `redis.connected` — `false`, поле `error`
содержит сообщение исключения — фронтенд-команда сразу видит, что именно
не отвечает.

Horizon-дашборд (после установки пакета и настройки gate `viewHorizon` в
`HorizonServiceProvider`): `http://localhost:8000/horizon`.

## На заметку перед деплоем

- `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` в `.env.example` — заглушки
  (`app.project.md` / `.project.md`). Для прод-домена требуется
  зафиксировать реальные поддомены до деплоя (см. ТЗ §3.2) — иначе
  cookie-based SPA-аутентификация для Next.js не заработает.
- `FILESYSTEM_DISK=s3` настроен под MinIO по умолчанию (`AWS_ENDPOINT`
  указывает на контейнер `minio`, которого пока нет в `docker-compose.yml` —
  добавляется вместе с задачей загрузки документов).
- В образе `app`/`horizon` пока нет `composer install` на этапе `build` —
  это сделано намеренно, чтобы `vendor/` монтировался с хоста и кэшировался
  между пересборками. Для CI/production-образа стоит вынести это в
  отдельный multi-stage Dockerfile.
