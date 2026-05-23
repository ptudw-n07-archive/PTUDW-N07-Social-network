#!/usr/bin/env sh
set -eu

PORT="${PORT:-8080}"
UPLOADS_ROOT="${UPLOADS_ROOT:-storage/uploads}"

mkdir -p "${UPLOADS_ROOT}/posts" "${UPLOADS_ROOT}/avatars"

if [ -L "Public/uploads" ] || [ -d "Public/uploads" ]; then
  rm -rf "Public/uploads"
fi

ln -s "$(cd "$(dirname "${UPLOADS_ROOT}")" && pwd)/$(basename "${UPLOADS_ROOT}")" "Public/uploads"

exec php -S "0.0.0.0:${PORT}" -t . Public/index.php
