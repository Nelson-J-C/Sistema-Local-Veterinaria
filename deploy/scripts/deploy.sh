#!/usr/bin/env bash
#
# Despliegue del Sistema Veterinario en Ubuntu Server.
# Ejecutar desde el servidor, como el usuario dueño de la aplicación:
#
#   APP_DIR=/var/www/veterinaria bash deploy/scripts/deploy.sh
#
# El script no instala paquetes del sistema ni toca la base de datos más allá de
# aplicar migraciones. Los pasos de instalación están en el manual técnico.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/veterinaria}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] ERROR: %s\n' "$*" >&2
    exit 1
}

cd "$APP_DIR" || fail "no existe APP_DIR: $APP_DIR"
[ -f artisan ] || fail "APP_DIR no contiene artisan: $APP_DIR"
[ -f .env ] || fail "falta .env en $APP_DIR; créelo a partir de .env.example"

restore_app() {
    "$PHP_BIN" artisan up >/dev/null 2>&1 || true
}

trap restore_app EXIT

log "poniendo la aplicación en mantenimiento"
"$PHP_BIN" artisan down --retry=60 || log "no se pudo activar el modo mantenimiento"

log "instalando dependencias PHP de producción"
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "instalando dependencias JS y compilando assets"
"$NPM_BIN" ci --ignore-scripts
"$NPM_BIN" run build

log "aplicando migraciones"
"$PHP_BIN" artisan migrate --force

log "enlazando almacenamiento público"
"$PHP_BIN" artisan storage:link || log "el enlace público ya existe"

log "regenerando cachés de producción"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

log "reiniciando la cola de trabajos"
"$PHP_BIN" artisan queue:restart || log "no hay worker de cola activo"

log "despliegue completado"
