#!/bin/sh
set -e

if [ "${RUN_PREVIEW_MIGRATIONS:-false}" = "true" ]; then
    php artisan optimize:clear
    php artisan migrate --force
    php artisan db:seed --class=RouteFilterSeeder --force
    php artisan storage:link || true
fi

exec docker-php-entrypoint "$@"
