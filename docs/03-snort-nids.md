# 2단계 — Snort 3 NIDS 게이트웨이 (192.168.10.10)

게이트웨이에서 네트워크 트래픽을 감시해 쿠키 유출·비정상 RST·ACK 튀기를 탐지합니다.

## 2-1. 설치

```bash
sudo apt update
sudo apt install -y snort3 || sudo apt install -y snort
snort -V      # 버전 확인
```

> 배포판에 Snort 3 패키지가 없으면 소스 빌드(libdaq, snort3)를 사용합니다.
> 아래 룰·변수는 Snort 2와 Snort 3 문법이 거의 동일합니다.

## 2-2. 네트워크 변수 — 내부자 시나리오에 맞게 수정

원 PPT의 룰은 `$EXTERNAL_NET -> $HOME_NET`으로 되어 있어 **내부에서 나오는
공격 트래픽을 놓칠 수 있습니다.** 이 실습은 내부자 모델이므로 공격자도
HOME_NET에 포함하거나, 방향을 양방향(`<>`)으로 둡니다.

```
# snort.lua 또는 snort.conf
HOME_NET = '192.168.10.0/24'
EXTERNAL_NET = 'any'   # 내부자 감안: HOME_NET 내부 트래픽도 검사되도록 any 유지
```

## 2-3. 커스텀 시그니처 ([../snort/local.rules](../snort/local.rules))

sid는 로컬 룰 권장 대역인 **1000000 이상**을 씁니다(CRS/기본 룰과 충돌 방지).
전체 룰은 [`snort/local.rules`](../snort/local.rules) 참고.

## 2-4. 실행 및 로그

```bash
# 인터페이스 이름은 ip a 로 확인(예: ens33)
sudo snort -c /etc/snort/snort.lua -R /etc/snort/rules/local.rules \
  -i ens33 -A alert_fast -s 65535 -k none
```

경보는 `-A alert_fast` 출력이나 `/var/log/snort/`에 쌓입니다.
5단계에서 공격을 재현하며 이 경보가 뜨는지 확인합니다.
