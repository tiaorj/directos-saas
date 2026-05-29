#!/bin/sh
set -e

export PORT="${PORT:-10000}"
export UPLOAD_DIR="${UPLOAD_DIR:-/var/www/storage/uploads}"
export LOG_DIR="${LOG_DIR:-/var/www/storage/logs}"

mkdir -p "$UPLOAD_DIR" "$LOG_DIR"
chown -R www-data:www-data "$UPLOAD_DIR" "$LOG_DIR" 2>/dev/null || true

exec apache2-foreground
