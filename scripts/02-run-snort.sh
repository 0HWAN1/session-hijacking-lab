#!/usr/bin/env bash
# Snort 3 NIDS 실행 (게이트웨이). 사용법: sudo bash 02-run-snort.sh <interface>
# 예: sudo bash 02-run-snort.sh ens33
set -euo pipefail

IFACE="${1:-ens33}"
CONF="/etc/snort/snort.lua"
RULES="$(dirname "$0")/../snort/local.rules"

if [ ! -f "${CONF}" ]; then
  echo "[!] ${CONF} 없음. Snort 3 설치/설정을 먼저 확인하세요." >&2
  exit 1
fi

echo "[*] 인터페이스 ${IFACE} 에서 Snort 실행 (Ctrl+C로 종료)"
exec snort -c "${CONF}" -R "${RULES}" -i "${IFACE}" -A alert_fast -s 65535 -k none
