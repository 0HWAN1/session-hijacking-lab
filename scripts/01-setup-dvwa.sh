#!/usr/bin/env bash
# DVWA 타겟 서버 설치 (Ubuntu 22.04). 격리망 전용. root로 실행.
set -euo pipefail

DB_USER="dvwa"
DB_PASS="p@ssw0rd"
DB_NAME="dvwa"

echo "[*] LAMP 스택 설치"
apt update
apt install -y apache2 mariadb-server php php-mysqli php-gd libapache2-mod-php git
systemctl enable --now apache2 mariadb

echo "[*] DB 생성"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "[*] DVWA 배포"
cd /var/www/html
[ -d dvwa ] || git clone https://github.com/digininja/DVWA.git dvwa
cp -n dvwa/config/config.inc.php.dist dvwa/config/config.inc.php
sed -i "s/\$_DVWA\[ 'db_user' \].*/\$_DVWA[ 'db_user' ] = '${DB_USER}';/"     dvwa/config/config.inc.php
sed -i "s/\$_DVWA\[ 'db_password' \].*/\$_DVWA[ 'db_password' ] = '${DB_PASS}';/" dvwa/config/config.inc.php
chown -R www-data:www-data dvwa

echo "[*] PHP 설정"
PHP_INI=$(php -r 'echo php_ini_loaded_file();')
sed -i 's/^allow_url_include.*/allow_url_include = On/' "${PHP_INI}" || true
sed -i 's/^allow_url_fopen.*/allow_url_fopen = On/'     "${PHP_INI}" || true
systemctl restart apache2

echo "[+] 완료. 브라우저에서 http://<타겟IP>/dvwa/setup.php 접속 후"
echo "    'Create / Reset Database' 클릭 → admin/password 로그인 → Security=Low 설정."
