#!/usr/bin/env bash
# restic with the backup repository's credentials from the macOS Keychain
# (docs/deployment.md §5.2), e.g. make restic cmd="snapshots". Variables that
# are already set win, so a rehearsal can point it at a local repository.
set -euo pipefail

keychain() {
    security find-generic-password -s "$1" -w
}

if [ -z "${RESTIC_REPOSITORY:-}" ]; then
    RESTIC_REPOSITORY="$(keychain jobtrail-restic-repository)"
    AWS_ACCESS_KEY_ID="$(keychain jobtrail-s3-access-key)"
    AWS_SECRET_ACCESS_KEY="$(keychain jobtrail-s3-secret-key)"
    AWS_DEFAULT_REGION="$(keychain jobtrail-s3-region)"
    export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION
fi
export RESTIC_REPOSITORY

if [ -z "${RESTIC_PASSWORD_FILE:-}" ] && [ -z "${RESTIC_PASSWORD_COMMAND:-}" ]; then
    export RESTIC_PASSWORD_COMMAND="security find-generic-password -s jobtrail-restic-password -w"
fi

exec restic "$@"
