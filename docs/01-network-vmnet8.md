# 0단계 — VMnet8 격리망 구성

모든 노드를 동일한 L2 세그먼트에 두어야 ARP MitM이 성립합니다.
VMware Virtual Network Editor에서 설정합니다.

1. **네트워크 편집기 열기** — VMware Workstation → Edit → Virtual Network Editor (관리자 권한).
2. **VMnet8 선택 후 모드 지정** — 외부와 완전 분리하려면 **Host-only**를 권장합니다.
   패키지 설치 동안만 NAT를 썼다가 끝나면 Host-only로 되돌립니다.
3. **서브넷 고정** — Subnet IP `192.168.10.0`, Mask `255.255.255.0`.
4. **DHCP 끄기** — "Use local DHCP service" 체크 해제. 노드마다 IP를 고정해야
   탐지 룰·ARP 표가 일관됩니다.
5. **각 VM의 NIC를 VMnet8에 연결** — 각 VM → Settings → Network Adapter → Custom: VMnet8.

## 정적 IP 할당

리눅스 노드는 Netplan(Ubuntu), Windows는 어댑터 속성에서 고정합니다.
Ubuntu 예시(`/etc/netplan/01-netcfg.yaml`):

```yaml
network:
  version: 2
  ethernets:
    ens33:
      dhcp4: no
      addresses: [192.168.10.10/24]   # 노드별로 변경
      routes:
        - to: default
          via: 192.168.10.2      # NAT 게이트웨이(인터넷 필요 시에만)
      nameservers:
        addresses: [8.8.8.8]
```

```bash
sudo netplan apply
ip -4 addr show ens33        # IP 확인
```

## 격리 확인

```bash
# 내부 노드끼리는 통신되고
ping -c2 192.168.10.20
# 외부는 차단되어야 정상(Host-only 기준, 응답 없어야 함)
ping -c2 8.8.8.8
```

> 모든 노드가 서로 ping되고 외부는 닿지 않으면 격리망 준비 완료입니다.
