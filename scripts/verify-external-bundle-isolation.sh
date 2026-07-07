#!/usr/bin/env bash
#
# Verify that the external Vite bundle (app-external-*.js) does not contain
# internal-only modules. Run AFTER `npm run build`.
#
# Exits 1 if any forbidden string is present, 0 otherwise.

set -euo pipefail

BUILD_DIR="${BUILD_DIR:-public/build/assets}"

if [ ! -d "$BUILD_DIR" ]; then
    echo "ERROR: build directory '$BUILD_DIR' not found. Did you run 'npm run build'?" >&2
    exit 1
fi

EXTERNAL_BUNDLE=$(find "$BUILD_DIR" -maxdepth 1 -name 'app-external-*.js' | head -n 1)
if [ -z "$EXTERNAL_BUNDLE" ]; then
    echo "ERROR: external bundle not found in '$BUILD_DIR'. Did you run 'npm run build'?" >&2
    exit 1
fi

echo "Scanning $EXTERNAL_BUNDLE for forbidden internal-only references..."

FORBIDDEN_STRINGS=(
    "laravel-permission-to-vuejs"
    "reloadRolesAndPermissions"
    "jsPermissions"
    "PopupChat"
)

FAILED=0
for needle in "${FORBIDDEN_STRINGS[@]}"; do
    if grep -q -- "$needle" "$EXTERNAL_BUNDLE"; then
        echo "FAIL: external bundle contains forbidden string '$needle'" >&2
        FAILED=1
    fi
done

if [ "$FAILED" -eq 1 ]; then
    echo "External bundle isolation check FAILED." >&2
    exit 1
fi

echo "External bundle isolation check passed."
