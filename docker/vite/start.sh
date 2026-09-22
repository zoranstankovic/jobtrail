#!/bin/sh
# Starts the Vite dev server in the vite container. Like the app entrypoint,
# it runs as the owner of the checkout, so node_modules/ and public/hot belong
# to the host user on Linux (on macOS the owner shows up as root).
set -e

exec setpriv --reuid="$(stat -c %u .)" --regid="$(stat -c %g .)" --clear-groups \
    sh -c 'if [ ! -d node_modules ]; then npm install; fi; exec npm run dev'
