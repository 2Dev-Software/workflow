#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PACKAGE_DIR="${ROOT_DIR}/docker/package"
PACKAGE_NAME="${PACKAGE_NAME:-workflow-docker-package}"
PACKAGE_ARCHIVE="${PACKAGE_DIR}/${PACKAGE_NAME}.tar.gz"
PACKAGE_CHECKSUM="${PACKAGE_ARCHIVE}.sha256"
TEMP_DIR="$(mktemp -d)"
STAGE_DIR="${TEMP_DIR}/${PACKAGE_NAME}"

cleanup() {
  rm -rf "${TEMP_DIR}"
}
trap cleanup EXIT

cd "${ROOT_DIR}"

mkdir -p "${PACKAGE_DIR}"

bash scripts/docker/export-current-db.sh
bash scripts/docker/export-runtime-assets.sh

mkdir -p "${STAGE_DIR}/docker/mysql/initdb"
mkdir -p "${STAGE_DIR}/docker/runtime-assets"
mkdir -p "${STAGE_DIR}/docs"

cp docker-compose.yml "${STAGE_DIR}/docker-compose.yml"
cp .env.docker.example "${STAGE_DIR}/.env.docker.example"
cp docs/docker-local.md "${STAGE_DIR}/docs/docker-local.md"
cp docker/mysql/initdb/001_deebuk_platform.sql "${STAGE_DIR}/docker/mysql/initdb/001_deebuk_platform.sql"
cp docker/runtime-assets/workflow-runtime-assets.tar.gz "${STAGE_DIR}/docker/runtime-assets/workflow-runtime-assets.tar.gz"

if [[ -f docker/runtime-assets/workflow-runtime-assets.tar.gz.sha256 ]]; then
  cp docker/runtime-assets/workflow-runtime-assets.tar.gz.sha256 "${STAGE_DIR}/docker/runtime-assets/workflow-runtime-assets.tar.gz.sha256"
fi

cat > "${STAGE_DIR}/README.txt" <<'EOF'
Workflow Docker handoff package

1. Copy .env.docker.example to .env.docker
2. Extract docker/runtime-assets/workflow-runtime-assets.tar.gz at the project root
3. Run: docker compose --env-file .env.docker up -d --build

Notes:
- Database seed comes from docker/mysql/initdb/001_deebuk_platform.sql
- Runtime files are required if you want uploads/profile images to match the source machine
EOF

rm -f "${PACKAGE_ARCHIVE}" "${PACKAGE_CHECKSUM}"
tar -czf "${PACKAGE_ARCHIVE}" -C "${TEMP_DIR}" "${PACKAGE_NAME}"

if command -v shasum >/dev/null 2>&1; then
  shasum -a 256 "${PACKAGE_ARCHIVE}" > "${PACKAGE_CHECKSUM}"
elif command -v sha256sum >/dev/null 2>&1; then
  sha256sum "${PACKAGE_ARCHIVE}" > "${PACKAGE_CHECKSUM}"
fi

echo "Docker handoff package created:"
echo "  ${PACKAGE_ARCHIVE}"
if [[ -f "${PACKAGE_CHECKSUM}" ]]; then
  echo "Checksum file created:"
  echo "  ${PACKAGE_CHECKSUM}"
fi
