#!/usr/bin/env bash
# The server side of make deploy (docs/deployment.md §4). scripts/deploy.sh
# streams it over SSH: bash -s -- <full commit sha>.
#   JOBTRAIL_DIR   the stack's directory (default /opt/jobtrail)
#   JOBTRAIL_PULL  0 skips the pull; only for rehearsals with local images
set -euo pipefail

tag="${1:?usage: deploy-remote.sh <full commit sha>}"
dir="${JOBTRAIL_DIR:-/opt/jobtrail}"
cd "$dir"

# Every step below uses the new tag; it replaces the tag in .env only once the
# new version runs. compose.prod.yaml refuses to start without TAG.
export COMPOSE_FILE=compose.prod.yaml
export TAG="$tag"

# 1. Dump the current data if the database already runs. It is the way back
#    when a migration damages data (docs/deployment.md §7.1). Keeps 5.
if docker compose ps --status running --services | grep -x db > /dev/null; then
    mkdir -p backups
    dump="backups/pre-deploy-$(date -u +%Y%m%dT%H%M%SZ).dump"
    (umask 077 && docker compose exec -T db pg_dump -U jobtrail -Fc jobtrail > "$dump.partial")
    mv "$dump.partial" "$dump"
    echo "Saved $dir/$dump"
    find backups -name 'pre-deploy-*.dump' | sort -r | tail -n +6 | while read -r old; do
        rm -- "$old"
    done
fi

# 2. Fetch the new images while the old version keeps running.
if [ "${JOBTRAIL_PULL:-1}" != 0 ]; then
    docker compose pull --quiet
fi

# 3. Migrate with the new image. A failing migration stops the script here:
#    the old containers keep running, and PostgreSQL rolled the migration back.
docker compose up -d --wait db
docker compose run --rm app php artisan migrate --force

# 4. Replace app and web. The app is unavailable for a few seconds.
docker compose up -d --wait

printf 'COMPOSE_FILE=compose.prod.yaml\nTAG=%s\n' "$tag" > .env

# 5. Remove images of earlier versions. Only JobTrail's images carry this
#    label; GHCR keeps every tag for a rollback.
docker image prune --all --force \
    --filter 'label=org.opencontainers.image.source=https://github.com/zoranstankovic/jobtrail' > /dev/null

curl -fsS -o /dev/null http://127.0.0.1:8080/up
echo "Running $tag"
