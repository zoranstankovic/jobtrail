#!/usr/bin/env bash
# Nightly backup (docs/deployment.md §5), run as root by
# jobtrail-backup.service: pg_dump, check the dump, restic to object storage,
# prune old snapshots. healthchecks.io gets /start and the exit code.
#   JOBTRAIL_BACKUP_ENV  settings (default /etc/jobtrail/backup.env)
#   JOBTRAIL_DIR         the stack (default /opt/jobtrail)
#   JOBTRAIL_BACKUP_DIR  local dumps (default /var/backups/jobtrail)
set -euo pipefail
umask 077

config="${JOBTRAIL_BACKUP_ENV:-/etc/jobtrail/backup.env}"
dir="${JOBTRAIL_DIR:-/opt/jobtrail}"
backups="${JOBTRAIL_BACKUP_DIR:-/var/backups/jobtrail}"

# shellcheck source=/dev/null
. "$config"
export RESTIC_REPOSITORY RESTIC_PASSWORD_FILE
export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION

ping() {
    if [ -n "${HC_PING_URL:-}" ]; then
        curl -fsS -m 10 --retry 5 -o /dev/null "$HC_PING_URL$1" || true
    fi
}

ping /start
# healthchecks.io reads 0 as success and anything else as failure.
trap 'ping "/$?"' EXIT

mkdir -p "$backups"
dump="$backups/jobtrail-$(date -u +%Y%m%dT%H%M%SZ).dump"

cd "$dir"
docker compose exec -T db pg_dump -U jobtrail -Fc jobtrail > "$dump.partial"
# Reads the archive's table of contents: an empty or unreadable dump fails
# here, not on the day it is needed. Only a restore proves it is complete
# (make restore-test).
docker compose exec -T db pg_restore --list < "$dump.partial" > /dev/null
mv "$dump.partial" "$dump"

find "$backups" -name 'jobtrail-*.dump' | sort -r | tail -n +8 | while read -r old; do
    rm -- "$old"
done

restic backup --host jobtrail --tag nightly \
    "$dump" "$dir/app.env" "$dir/.env" "$dir/secrets/db_password"
# restic groups snapshots by host and paths by default, and the dump's name
# changes every night, so each snapshot would be a group of one and never be
# forgotten. All nightly snapshots share the tag; group by it instead.
restic forget --host jobtrail --group-by host,tags \
    --keep-daily 7 --keep-weekly 4 --keep-monthly 6 --prune
