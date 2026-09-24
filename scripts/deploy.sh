#!/usr/bin/env bash
# make deploy [TAG=<full commit sha>] (docs/deployment.md §4): deploys the
# images CI built for one commit to the server, over the tailnet.
#   TAG          defaults to origin/main
#   DEPLOY_HOST  the SSH host (default: the jobtrail alias in ~/.ssh/config)
set -euo pipefail

cd "$(dirname "$0")/.."

host="${DEPLOY_HOST:-jobtrail}"
dir=/opt/jobtrail
registry=ghcr.io/zoranstankovic
tag="${TAG:-}"

if [ -z "$tag" ]; then
    git fetch --quiet origin main
    tag="$(git rev-parse origin/main)"
fi

if ! printf '%s' "$tag" | grep -Ex '[0-9a-f]{40}' > /dev/null; then
    echo "TAG must be a full 40-character commit sha, not '$tag'." >&2
    exit 1
fi

# CI pushes images only for commits whose checks passed, so a missing image
# means CI failed, is still running, or the package is not public.
for image in jobtrail-app jobtrail-web; do
    if ! docker manifest inspect "$registry/$image:$tag" > /dev/null 2>&1; then
        echo "$registry/$image:$tag is not in the registry; check the CI run for $tag." >&2
        exit 1
    fi
done

echo "Deploying $tag to $host"
scp -q compose.prod.yaml deploy/backup.sh "$host:$dir/"
ssh "$host" bash -s -- "$tag" < deploy/deploy-remote.sh

# The public address is APP_URL in the server's app.env, so the tailnet name is
# written down in one place only. $dir is meant to expand here, on the Mac.
# shellcheck disable=SC2029
url="$(ssh "$host" "grep '^APP_URL=' $dir/app.env | cut -d= -f2-")"
curl -fsS -o /dev/null "$url/up"
echo "Deployed $tag: $url"
