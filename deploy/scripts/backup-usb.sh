#!/usr/bin/env bash
#
# Respaldo nocturno del Sistema Veterinario a un disco USB externo.
#
# Requisitos en el servidor:
#   - Disco ext4 montado por UUID en /mnt/veterinaria-backup.
#   - BACKUP_ARCHIVE_PASSWORD definido en .env (cifra el ZIP completo).
#   - Archivo con la frase de paso para cifrar .env, legible solo por el usuario
#     que ejecuta el respaldo: /etc/veterinaria/backup.passphrase (chmod 400).
#
# Uso manual:
#   sudo -u www-data env APP_DIR=/var/www/veterinaria \
#     BACKUP_MOUNT=/mnt/veterinaria-backup \
#     BACKUP_PASSPHRASE_FILE=/etc/veterinaria/backup.passphrase \
#     bash deploy/scripts/backup-usb.sh
#
# Rotación física: intercambiar los discos A y B cada semana. El script siempre
# escribe en el disco montado; el disco retirado queda fuera de la clínica.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/veterinaria}"
BACKUP_MOUNT="${BACKUP_MOUNT:-/mnt/veterinaria-backup}"
PASSPHRASE_FILE="${BACKUP_PASSPHRASE_FILE:-/etc/veterinaria/backup.passphrase}"
MIN_FREE_MB="${BACKUP_MIN_FREE_MB:-2048}"
STAMP="$(date +%Y%m%d-%H%M%S)"

log() {
    printf '[backup %s] %s\n' "$STAMP" "$*"
}

fail() {
    printf '[backup %s] ERROR: %s\n' "$STAMP" "$*" >&2
    exit 1
}

cd "$APP_DIR" || fail "no existe APP_DIR: $APP_DIR"
[ -f artisan ] || fail "APP_DIR no contiene artisan: $APP_DIR"
[ -f .env ] || fail "falta .env en $APP_DIR"

archive_password_line="$(grep -E '^BACKUP_ARCHIVE_PASSWORD=' .env | tail -n 1 || true)"
archive_password="${archive_password_line#BACKUP_ARCHIVE_PASSWORD=}"
archive_password="${archive_password%\"}"
archive_password="${archive_password#\"}"
[ -n "$archive_password" ] || fail "defina BACKUP_ARCHIVE_PASSWORD en .env para cifrar el respaldo"

[ -d "$BACKUP_MOUNT" ] || fail "no existe el punto de montaje: $BACKUP_MOUNT"
mountpoint -q "$BACKUP_MOUNT" || fail "el disco USB no está montado en $BACKUP_MOUNT"

free_mb="$(df -Pm "$BACKUP_MOUNT" | awk 'NR==2 {print $4}')"
[ -n "$free_mb" ] || fail "no se pudo leer el espacio libre de $BACKUP_MOUNT"
[ "$free_mb" -ge "$MIN_FREE_MB" ] || fail "espacio libre insuficiente: ${free_mb} MB (mínimo ${MIN_FREE_MB} MB)"

touch "$BACKUP_MOUNT/.write-test" 2>/dev/null || fail "el disco USB no admite escritura"
rm -f "$BACKUP_MOUNT/.write-test"

label="$(findmnt -n -o LABEL --target "$BACKUP_MOUNT" 2>/dev/null || true)"
log "disco: ${label:-sin etiqueta} · libre: ${free_mb} MB"

php artisan backup:run --disable-notifications
php artisan backup:clean --disable-notifications

[ -r "$PASSPHRASE_FILE" ] || fail "no se puede leer la frase de paso: $PASSPHRASE_FILE"
install -d -m 700 "$BACKUP_MOUNT/env"
gpg --batch --yes --quiet --symmetric --cipher-algo AES256 \
    --passphrase-file "$PASSPHRASE_FILE" \
    --output "$BACKUP_MOUNT/env/env-$STAMP.gpg" .env
chmod 600 "$BACKUP_MOUNT/env/env-$STAMP.gpg"

recent="$(find "$BACKUP_MOUNT" -maxdepth 4 -type f -name '*.zip' -newermt '-2 hours' -print -quit)"
[ -n "$recent" ] || fail "no se generó ningún ZIP en las últimas 2 horas"

log "respaldo completado: $(basename "$recent")"
