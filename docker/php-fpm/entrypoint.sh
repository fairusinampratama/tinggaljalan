#!/bin/sh
set -e

prepare_laravel_writable_directories() {
    mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
}

prepare_laravel_writable_directories

if [ "${RUN_PREVIEW_MIGRATIONS:-false}" = "true" ]; then
    su -s /bin/sh www-data -c "php artisan optimize:clear"
    su -s /bin/sh www-data -c "php artisan migrate --force"
    su -s /bin/sh www-data -c "php artisan db:seed --class=RouteFilterSeeder --force"
    su -s /bin/sh www-data -c "php artisan storage:link" || true
fi

prepare_laravel_writable_directories

exec docker-php-entrypoint "$@"