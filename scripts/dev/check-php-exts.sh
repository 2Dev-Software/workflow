#!/usr/bin/env bash
set -euo pipefail

PHP_BIN="${PHP:-php}"
REQUIRED_EXTS=(gd iconv)

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "ERROR: PHP command not found: $PHP_BIN" >&2
  exit 1
fi

php_modules="$("$PHP_BIN" -m | tr '[:upper:]' '[:lower:]')"
missing_exts=()

for ext in "${REQUIRED_EXTS[@]}"; do
  if ! grep -qx "$ext" <<<"$php_modules"; then
    missing_exts+=("$ext")
  fi
done

if ((${#missing_exts[@]} == 0)); then
  exit 0
fi

echo "ERROR: Missing required PHP extension(s): ${missing_exts[*]}" >&2
echo "Composer cannot install project dependencies until these extensions are available." >&2
echo >&2

os_id=""
os_like=""
if [[ -f /etc/os-release ]]; then
  # shellcheck disable=SC1091
  source /etc/os-release
  os_id="${ID:-}"
  os_like="${ID_LIKE:-}"
fi

php_mm="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"

print_debian_help() {
  echo "Install for Debian/Ubuntu:" >&2
  echo "  sudo apt update" >&2
  echo "  sudo apt install -y php-cli php-common php-mysql php-mbstring php-xml php-curl php-zip php-gd" >&2
  echo "If iconv is still missing, ensure PHP common package for your version is installed:" >&2
  echo "  sudo apt install -y php${php_mm}-common" >&2
}

print_rhel_help() {
  echo "Install for RHEL/CentOS/Rocky/Alma/Fedora:" >&2
  echo "  sudo dnf install -y php-cli php-common php-mysqlnd php-mbstring php-xml php-curl php-zip php-gd" >&2
}

print_arch_help() {
  echo "Install for Arch Linux:" >&2
  echo "  sudo pacman -Sy --needed php php-gd" >&2
}

case "${os_id,,}" in
  ubuntu|debian)
    print_debian_help
    ;;
  fedora|rhel|centos|rocky|almalinux)
    print_rhel_help
    ;;
  arch|manjaro)
    print_arch_help
    ;;
  *)
    if [[ "${os_like,,}" == *debian* ]]; then
      print_debian_help
    elif [[ "${os_like,,}" == *rhel* ]] || [[ "${os_like,,}" == *fedora* ]]; then
      print_rhel_help
    elif [[ "${os_like,,}" == *arch* ]]; then
      print_arch_help
    else
      echo "Please install PHP extensions manually, then retry: make dev" >&2
      echo "Required: ${REQUIRED_EXTS[*]}" >&2
    fi
    ;;
esac

exit 1
