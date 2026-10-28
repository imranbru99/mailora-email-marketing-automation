#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ -e docker/.env ]; then
    printf '%s\n' 'docker/.env already exists; edit it directly to preserve the application key.' >&2
    exit 1
fi
command -v openssl >/dev/null 2>&1 || { printf '%s\n' 'Install openssl first.' >&2; exit 1; }
umask 077
awk -v app_key="base64:$(openssl rand -base64 32)" \
    -v db_password="$(openssl rand -hex 32)" \
    -v db_root_password="$(openssl rand -hex 32)" \
    -v redis_password="$(openssl rand -hex 32)" '
    /^APP_KEY=/ { print "APP_KEY=" app_key; next }
    /^DB_PASSWORD=/ { print "DB_PASSWORD=" db_password; next }
    /^MYSQL_ROOT_PASSWORD=/ { print "MYSQL_ROOT_PASSWORD=" db_root_password; next }
    /^REDIS_PASSWORD=/ { print "REDIS_PASSWORD=" redis_password; next }
    { print }
' docker/.env.example > docker/.env
printf '%s\n' 'Created docker/.env with unique secrets. Set APP_URL and review it before starting.'
