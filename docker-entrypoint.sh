#!/bin/sh
set -eu

PORT="${PORT:-10000}"

echo "Configurando Apache na porta ${PORT}..."

# Garante que somente o MPM Prefork esteja carregado.
rm -f \
  /etc/apache2/mods-enabled/mpm_event.load \
  /etc/apache2/mods-enabled/mpm_event.conf \
  /etc/apache2/mods-enabled/mpm_worker.load \
  /etc/apache2/mods-enabled/mpm_worker.conf

if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.load ]; then
  ln -s /etc/apache2/mods-available/mpm_prefork.load \
    /etc/apache2/mods-enabled/mpm_prefork.load
fi

if [ -f /etc/apache2/mods-available/mpm_prefork.conf ] &&
   [ ! -e /etc/apache2/mods-enabled/mpm_prefork.conf ]; then
  ln -s /etc/apache2/mods-available/mpm_prefork.conf \
    /etc/apache2/mods-enabled/mpm_prefork.conf
fi

# Railway encaminha o domínio para esta porta interna.
printf 'Listen %s\n' "${PORT}" > /etc/apache2/ports.conf

sed -i -E \
  "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#g" \
  /etc/apache2/sites-available/000-default.conf

echo "MPMs habilitados:"
ls -la /etc/apache2/mods-enabled/mpm_* || true

apache2ctl configtest

echo "Iniciando Apache..."
exec apache2-foreground