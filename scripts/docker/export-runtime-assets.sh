#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUTPUT_DIR="${ROOT_DIR}/docker/runtime-assets"
ARCHIVE_PATH="${OUTPUT_DIR}/workflow-runtime-assets.tar.gz"
CHECKSUM_PATH="${ARCHIVE_PATH}.sha256"

INCLUDE_PATHS=(
  "storage/uploads"
  "assets/img/profile"
)

cd "${ROOT_DIR}"

mkdir -p "${OUTPUT_DIR}"

existing_paths=()

for path in "${INCLUDE_PATHS[@]}"; do
  if [[ -e "${path}" ]]; then
    existing_paths+=("${path}")
  fi
done

if [[ ${#existing_paths[@]} -eq 0 ]]; then
  echo "No runtime asset directories were found to export." >&2
  exit 1
fi

tar -czf "${ARCHIVE_PATH}" "${existing_paths[@]}"

if command -v shasum >/dev/null 2>&1; then
  shasum -a 256 "${ARCHIVE_PATH}" > "${CHECKSUM_PATH}"
elif command -v sha256sum >/dev/null 2>&1; then
  sha256sum "${ARCHIVE_PATH}" > "${CHECKSUM_PATH}"
fi

echo "Runtime asset archive created:"
echo "  ${ARCHIVE_PATH}"
if [[ -f "${CHECKSUM_PATH}" ]]; then
  echo "Checksum file created:"
  echo "  ${CHECKSUM_PATH}"
fi
echo "Included paths:"
for path in "${existing_paths[@]}"; do
  echo "  - ${path}"
done
