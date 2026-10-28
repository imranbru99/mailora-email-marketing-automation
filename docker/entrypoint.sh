#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    printf '%s\n' 'APP_KEY is required. Run sh docker/configure.sh before starting the stack.' >&2
    exit 1
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs
php artisan config:cache --quiet
exec "$@"
