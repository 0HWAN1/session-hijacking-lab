# L7 Web Attack Detection & Mitigation Lab

> 사내망 내부자 위협 모델 기반 **Stored XSS · 세션 하이재킹** 분석과
> **Snort NIDS · ModSecurity WAF** 다층 방어 실습 환경.
> 경기대학교 AI컴퓨터공학부 네트워크 보안 프로젝트 (2026 Fall).

⚠️ **교육용 격리 실습 전용.** 모든 공격 재현은 외부와 분리된 VMware VMnet8
격리망 안에서만 수행합니다. 실제 사내망·공용망에서는 절대 실행하지 않습니다.

---

## 구성 (Testbed)

| 노드 | OS | IP | 역할 | 주요 구성 |
| --- | --- | --- | --- | --- |
| 공격자 | Kali Linux 2024.x | 192.168.10.5 | 내부자 / 침투자 | Scapy, Ettercap, Wireshark, Burp Suite |
| 게이트웨이 | Ubuntu 22.04 LTS | 192.168.10.10 | NIDS + WAF 방어 | Snort 3, ModSecurity v3, Nginx |
| 타겟 | Ubuntu 22.04 LTS | 192.168.10.20 | 사내 그룹웨어(DVWA) | Apache, PHP, MySQL |
| 피해자 | Windows 10 | 192.168.10.30 | 관리자 PC(세션 생성) | 웹 브라우저 |

**트래픽 경로:** 피해자 → 게이트웨이(Nginx + ModSecurity, Snort 미러링 탐지) → DVWA.
공격자는 같은 L2 세그먼트에서 ARP 스푸핑으로 중간자(MitM) 위치를 잡습니다.

```
          ┌──────────── VMnet8 · 격리망 (192.168.10.0/24) ────────────┐
          │                                                            │
  피해자(Win10) ──▶ 게이트웨이 방어 ──▶ DVWA 타겟                       │
  .30              [Snort+ModSec] .10     .20                          │
                       ▲                                               │
                       ┊ ARP MitM                                      │
                   공격자(Kali) .5                                     │
          └────────────────────────────────────────────────────────────┘
```

## 실습 순서

> 📘 처음이라면 단계별 문서를 손으로 따라 할 수 있게 재배열한
> **[통합 실습 가이드 (docs/00-walkthrough.md)](docs/00-walkthrough.md)** 부터 보세요.

| 단계 | 내용 | 문서 |
| --- | --- | --- |
| 0 | VMnet8 격리망 구성 | [docs/01-network-vmnet8.md](docs/01-network-vmnet8.md) |
| 1 | DVWA 타겟 서버 | [docs/02-dvwa-target.md](docs/02-dvwa-target.md) |
| 2 | Snort 3 NIDS 게이트웨이 | [docs/03-snort-nids.md](docs/03-snort-nids.md) |
| 3 | ModSecurity WAF + 세션 하드닝 | [docs/04-modsecurity-waf.md](docs/04-modsecurity-waf.md) |
| 4 | 공격자 · 피해자 노드 | [docs/02-dvwa-target.md](docs/02-dvwa-target.md) |
| 5 | 공격 재현 및 탐지 검증 | [docs/05-attack-verification.md](docs/05-attack-verification.md) |
| 6 | 검증 지표 및 로그 수집 | [docs/06-metrics-logs.md](docs/06-metrics-logs.md) |

## 디렉터리 구조

```
.
├── docs/            # 단계별 상세 가이드
├── snort/           # Snort 3 커스텀 시그니처 (local.rules)
├── modsecurity/     # ModSecurity 커스텀 룰
└── scripts/         # 설치·실행 보조 스크립트
```

## 빠른 시작

```bash
# 타겟 서버(Ubuntu)에서
sudo bash scripts/01-setup-dvwa.sh

# 게이트웨이(Ubuntu)에서 Snort 실행
sudo bash scripts/02-run-snort.sh ens33
```

## 평가 지표

- **Precision** = TP / (TP + FP) — 오탐 최소화
- **Recall** = TP / (TP + FN) — 미탐 최소화
- **F1** = 2·P·R / (P + R) — 종합 지표

정상 트래픽 10,000건으로 오탐(FP)을 측정하고, ISO/IEC 25010의 보안성·신뢰성
관점으로 방어 전/후를 정량 비교합니다.

## 팀

| 이름 | 역할 |
| --- | --- |
| 김태환 | 팀장 · 총괄 / WAF |
| 백성준 | IDS / 네트워크 |
| 김건우 | XSS / 패킷 분석 |

지도교수 김도훈

## 라이선스 / 주의

이 저장소는 교육 목적의 격리 실습 자료입니다. DVWA 등 의도적으로 취약하게 만든
구성 요소를 포함하므로 인터넷에 직접 노출하지 마십시오.
