#!/bin/sh
set -eu

seed_dir=/var/www/html/deploy/seed-uploads
upload_dir=/var/www/html/uploads

if [ -d "$seed_dir" ]; then
    mkdir -p "$upload_dir"
    cp -an "$seed_dir"/. "$upload_dir"/
    chown -R www-data:www-data "$upload_dir"
fi

exec docker-php-entrypoint "$@"
