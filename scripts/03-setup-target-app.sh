#!/usr/bin/env bash
# 자체 취약 타겟 웹앱(사내 그룹웨어) 설치 — Ubuntu 22.04. 격리망 전용. root로 실행.
# DVWA 대신(또는 함께) 쓰는 Stored XSS / 세션 하이재킹 타겟.
# 사용: sudo bash scripts/03-setup-target-app.sh
set -euo pipefail

DB_USER="groupware"
DB_PASS="p@ssw0rd"
DB_NAME="groupware"
SRC_DIR="$(cd "$(dirname "$0")/../target-app" && pwd)"
WEB_DIR="/var/www/html/groupware"

echo "[*] LAMP 구성 요소 확인/설치"
apt update
apt install -y apache2 mariadb-server php php-mysqli libapache2-mod-php
systemctl enable --now apache2 mariadb

echo "[*] DB/계정 생성 및 스키마 적용"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
mysql "${DB_NAME}" < "${SRC_DIR}/schema.sql"

echo "[*] 앱 배포: ${SRC_DIR} -> ${WEB_DIR}"
mkdir -p "${WEB_DIR}"
cp -r "${SRC_DIR}/." "${WEB_DIR}/"
chown -R www-data:www-data "${WEB_DIR}"

systemctl restart apache2

echo "[+] 완료."
echo "    타겟 직접 접속:   http://<타겟IP>/groupware/login.php"
echo "    게이트웨이 경유:  http://192.168.10.10/groupware/login.php"
echo "    실습 계정: admin / password"
