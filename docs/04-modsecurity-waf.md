# 3단계 — ModSecurity v3 WAF + 세션 하드닝

게이트웨이의 Nginx 리버스 프록시 앞단에 ModSecurity를 올려 XSS 페이로드를
응용 계층에서 차단하고, 세션 쿠키를 하드닝합니다.

## 3-1. 설치 (libmodsecurity + Nginx 커넥터)

```bash
sudo apt install -y nginx libmodsecurity3 \
  libmodsecurity-dev git g++ make automake \
  libtool pkg-config
# Nginx 커넥터는 배포판 모듈(libnginx-mod-http-modsecurity)이 있으면 사용,
# 없으면 ModSecurity-nginx 커넥터를 소스 빌드
```

## 3-2. OWASP CRS v4 적용

```bash
cd /etc/nginx
sudo git clone https://github.com/coreruleset/coreruleset.git crs
sudo cp crs/crs-setup.conf.example crs/crs-setup.conf
# modsecurity.conf에서 탐지 모드를 On으로
sudo sed -i 's/SecRuleEngine .*/SecRuleEngine On/' /etc/nginx/modsec/modsecurity.conf
```

`modsec_includes.conf`에서 기본 설정 → CRS setup → CRS 룰 순서로 include합니다.
CRS의 **941xxx 규칙이 XSS를 담당**하므로 Guestbook에 들어온 `<script>`
페이로드는 403으로 차단됩니다.

## 3-3. PPT 룰의 문제와 올바른 커스텀 룰

원 PPT의 예시 룰은 두 가지가 틀렸습니다.

- `id:941100`은 **CRS가 이미 쓰는 XSS 규칙 번호**라 충돌합니다.
  커스텀은 다른 대역(예: 100000번대)을 써야 합니다.
- `REQUEST_COOKIES` 안에 `PHPSESSID`가 있다고 무조건 deny하면 **정상 세션까지
  전부 403**으로 막힙니다. 쿠키는 차단 대상이 아니라 보호 대상입니다.

올바른 접근은 ① XSS 페이로드 차단은 CRS 941xxx에 맡기고, ② 필요시 쿠키 탈취
패턴(응답 본문의 document.cookie)만 추가로 탐지하는 것입니다. 전체 룰은
[`modsecurity/custom-rules.conf`](../modsecurity/custom-rules.conf) 참고.

## 3-4. 세션 하드닝 (HttpOnly / CSP)

세션 쿠키에 **HttpOnly**를 설정하면 `document.cookie`로 자바스크립트가 쿠키를
읽지 못해, XSS가 성공해도 세션 탈취를 원천 차단합니다.

Nginx(게이트웨이)에서 쿠키를 다시 쓰고 CSP 헤더를 추가합니다.

```nginx
proxy_cookie_flags ~ httponly samesite=strict;
add_header Content-Security-Policy "default-src 'self'; script-src 'self'" always;
```

PHP 쪽에서 직접 두려면 `php.ini`:

```ini
session.cookie_httponly = 1
session.cookie_samesite = Strict
```

> 검증 포인트: HttpOnly **적용 전**에는 XSS로 쿠키가 유출되고, **적용 후**에는
> `document.cookie`가 빈 값이 되어 탈취가 실패하는 것을 비교합니다.
