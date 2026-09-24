#!/usr/bin/env bash
# make restore-test (docs/deployment.md §6): restores the newest backup into a
# throwaway PostgreSQL container and prints what it holds. The dump is kept in
# ~/JobTrailBackups (JOBTRAIL_RESTORE_DIR), where Time Machine picks it up.
set -euo pipefail

cd "$(dirname "$0")/.."

keep="${JOBTRAIL_RESTORE_DIR:-$HOME/JobTrailBackups}"
container=jobtrail-restore-test
work="$(mktemp -d)"

cleanup() {
    docker rm --force "$container" > /dev/null 2>&1 || true
    rm -rf "$work"
}
trap cleanup EXIT

# Grouped by tag, like the retention in deploy/backup.sh: restic groups by host
# and paths by default, and the dump's name changes every night.
bash scripts/restic-mac.sh snapshots --host jobtrail --group-by host,tags --latest 1
bash scripts/restic-mac.sh restore latest --host jobtrail --target "$work"

dump="$(find "$work" -name 'jobtrail-*.dump' | sort | tail -n 1)"
if [ -z "$dump" ]; then
    echo "The newest snapshot holds no dump." >&2
    exit 1
fi

docker run --detach --name "$container" --env POSTGRES_PASSWORD=restore-test postgres:18 > /dev/null
# On its first start the image runs a temporary server on the Unix socket only;
# TCP answers once the real server is up.
tries=0
until docker exec "$container" pg_isready --host 127.0.0.1 --username postgres > /dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "PostgreSQL did not start within a minute." >&2
        exit 1
    fi
    sleep 1
done

docker exec "$container" createdb --username postgres restore
docker exec --interactive "$container" \
    pg_restore --username postgres --dbname restore --no-owner --no-acl --exit-on-error < "$dump"

docker exec "$container" psql --username postgres --dbname restore --expanded --command "
    select (select count(*) from companies) as companies,
           (select count(*) from job_postings) as postings,
           (select count(*) from job_applications) as applications,
           (select count(*) from job_application_events) as events,
           (select max(occurred_at) from job_application_events) as latest_event"

mkdir -p "$keep"
cp "$dump" "$keep/"
echo "Restored $(basename "$dump"); a copy is in $keep"
