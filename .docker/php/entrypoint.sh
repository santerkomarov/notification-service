#!/usr/bin/env sh

set -e

echo "Waiting for PostgreSQL..."

until nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done

echo "PostgreSQL is ready."

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  cp .env.example .env
fi

php artisan optimize:clear

if [ "$APP_RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force
  php artisan db:seed --force
fi

exec "$@"