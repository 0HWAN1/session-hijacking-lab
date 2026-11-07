# 1단계 — DVWA 타겟 서버 (192.168.10.20) + 공격자·피해자 노드

DVWA(Damn Vulnerable Web Application)는 교육용으로 설계된 취약 웹앱입니다.
사내 그룹웨어 역할로 쓰고, Guestbook 기능으로 Stored XSS를 재현합니다.

## 1-1. LAMP 스택 설치

```bash
sudo apt update
sudo apt install -y apache2 mariadb-server \
  php php-mysqli php-gd libapache2-mod-php git
sudo systemctl enable --now apache2 mariadb
```

## 1-2. DB 생성

```bash
sudo mysql <<'SQL'
CREATE DATABASE dvwa;
CREATE USER 'dvwa'@'localhost' IDENTIFIED BY 'p@ssw0rd';
GRANT ALL ON dvwa.* TO 'dvwa'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 1-3. DVWA 배포 및 설정

```bash
cd /var/www/html
sudo git clone https://github.com/digininja/DVWA.git dvwa
sudo cp dvwa/config/config.inc.php.dist dvwa/config/config.inc.php
sudo chown -R www-data:www-data dvwa
```

`config.inc.php`에서 DB 계정을 위에서 만든 값과 맞춥니다.

```php
$_DVWA[ 'db_user' ] = 'dvwa';
$_DVWA[ 'db_password' ] = 'p@ssw0rd';
$_DVWA[ 'db_database' ] = 'dvwa';
```

PHP 설정(`/etc/php/*/apache2/php.ini`)에서 `allow_url_include = On`,
`allow_url_fopen = On`으로 두고 Apache를 재시작합니다.

```bash
sudo systemctl restart apache2
```

## 1-4. 초기화 및 보안 레벨

1. 브라우저에서 `http://192.168.10.20/dvwa/setup.php` 접속 → **Create / Reset Database** 클릭.
2. 기본 계정 `admin / password`로 로그인.
3. **DVWA Security → Low**로 설정(서버 측 검증 없는 상태 재현).
4. **XSS (Stored)** 메뉴의 Guestbook 페이지가 뜨는지 확인.

> 방어 전후를 비교할 때는 타겟을 DVWA에 직접 노출하는 것이 아니라
> 게이트웨이(Nginx 리버스 프록시)를 거쳐 접근하도록 합니다.
> 피해자·공격자는 `http://192.168.10.10/`(게이트웨이)로 접속합니다.

---

## 4단계 — 공격자(Kali) 및 피해자(Win10) 노드

### 공격자 — Kali Linux (192.168.10.5)

Kali에는 실습에 필요한 도구가 기본 탑재되어 있습니다. 버전만 확인합니다.

```bash
ettercap --version       # ARP 스푸핑
wireshark --version      # 패킷 분석
python3 -c "import scapy; print(scapy.__version__)"   # 패킷 조작
```

역할:

1. **Ettercap** — 피해자와 게이트웨이 사이의 ARP 테이블을 오염해 중간자 위치 확보.
2. **Wireshark / tshark** — 가로챈 패킷에서 세션 쿠키·SEQ/ACK 추적.
3. **Scapy** — TCP SEQ/ACK 분석 및 검증용 패킷 구성.
4. **간단한 C2 수신기** — 유출된 쿠키를 받을 로컬 HTTP 서버(`python3 -m http.server 8000`).

### 피해자 — Windows 10 (192.168.10.30)

1. 브라우저로 게이트웨이(`http://192.168.10.10/dvwa/`)에 접속해 **admin 계정으로
   로그인** → 관리자 세션 생성.
2. 세션을 유지한 채 Guestbook 페이지를 열어두면, 5단계에서 저장된 XSS가
   관리자 브라우저에서 실행됩니다.
3. 방화벽 프로파일은 사설망으로 두어 ARP 응답을 받도록 합니다(격리망 전용).

> 피해자 역할은 "실제 관리자가 사내 그룹웨어에 로그인한 상태"를 만드는 것이 핵심입니다.
> 세션이 없으면 탈취할 쿠키도 없습니다.
