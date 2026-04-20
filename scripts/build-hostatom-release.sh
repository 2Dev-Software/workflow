#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:-1.0.0}"
APP_NAME="db-sarabun"
RELEASE_NAME="${APP_NAME}-v${VERSION}-hostatom"
BUILD_DIR="${ROOT_DIR}/build"
RELEASE_DIR="${BUILD_DIR}/${RELEASE_NAME}"
ZIP_PATH="${BUILD_DIR}/${RELEASE_NAME}.zip"

mkdir -p "${BUILD_DIR}"
rm -rf "${RELEASE_DIR}" "${ZIP_PATH}"
mkdir -p "${RELEASE_DIR}"

find "${ROOT_DIR}" -maxdepth 1 -type f -name '*.php' ! -name 'php-cs-fixer.dist.php' -exec cp {} "${RELEASE_DIR}/" \;
cp "${ROOT_DIR}/.htaccess" "${RELEASE_DIR}/.htaccess"
cp "${ROOT_DIR}/.user.ini" "${RELEASE_DIR}/.user.ini"
cp "${ROOT_DIR}/composer.json" "${RELEASE_DIR}/composer.json"
cp "${ROOT_DIR}/composer.lock" "${RELEASE_DIR}/composer.lock"

cp "${ROOT_DIR}/.env.production.example" "${RELEASE_DIR}/.env"

for runtime_dir in app assets config public src storage; do
  rsync -a "${ROOT_DIR}/${runtime_dir}/" "${RELEASE_DIR}/${runtime_dir}/"
done

mkdir -p \
  "${RELEASE_DIR}/storage/uploads" \
  "${RELEASE_DIR}/public/uploads" \
  "${RELEASE_DIR}/assets/img/profile" \
  "${RELEASE_DIR}/assets/img/signature"

chmod -R 775 \
  "${RELEASE_DIR}/storage" \
  "${RELEASE_DIR}/public/uploads" \
  "${RELEASE_DIR}/assets/img/profile" \
  "${RELEASE_DIR}/assets/img/signature"

if command -v composer >/dev/null 2>&1; then
  composer install \
    --working-dir="${RELEASE_DIR}" \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress
else
  echo "Composer not found. The release folder was created without rebuilding vendor." >&2
fi

cat > "${RELEASE_DIR}/HOSTATOM_DEPLOY.md" <<'EOF'
# HostAtom Deploy

1. เปิดไฟล์ `.env` แล้วแก้ค่า `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` ให้ตรงกับฐานข้อมูลบน HostAtom
2. อัปโหลดไฟล์ทั้งหมดในโฟลเดอร์นี้ไปยัง Document Root ของเว็บไซต์
3. ตรวจสอบสิทธิ์เขียนของโฟลเดอร์ต่อไปนี้:
   - `storage/uploads`
   - `public/uploads`
   - `assets/img/profile`
   - `assets/img/signature`
4. ตรวจสอบว่าเซิร์ฟเวอร์ใช้ PHP 8.2 ขึ้นไป และเปิด extension `mysqli`, `openssl`, `mbstring`, `json`, `fileinfo`
5. ทดสอบหน้า `/health` ด้วยบัญชีผู้ดูแลระบบหลัง deploy

ไฟล์ `.user.ini` ถูกจัดเตรียมไว้แล้วสำหรับ production shared hosting
EOF

(
  cd "${BUILD_DIR}"
  zip -qr "${ZIP_PATH}" "${RELEASE_NAME}"
)

echo "Release folder: ${RELEASE_DIR}"
echo "Release zip: ${ZIP_PATH}"
