#!/bin/sh
set -e

export PORT="${PORT:-10000}"
export UPLOAD_DIR="${UPLOAD_DIR:-/var/www/storage/uploads}"
export LOG_DIR="${LOG_DIR:-/var/www/storage/logs}"

echo "Listen ${PORT}" > /etc/apache2/ports.conf

sed -i -E "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#" \
    /etc/apache2/sites-available/000-default.conf

mkdir -p "$UPLOAD_DIR" "$LOG_DIR"
chown -R www-data:www-data "$UPLOAD_DIR" "$LOG_DIR" 2>/dev/null || true

apache2ctl configtest
exec apache2-foreground
