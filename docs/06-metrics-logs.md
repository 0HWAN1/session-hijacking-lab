# 검증 지표 및 로그 수집

단일 공격 성공 여부가 아니라, 방어 계층을 쌓을수록 지표가 어떻게 변하는지를
정량 비교합니다.

## 측정 지표

- **Precision** = TP / (TP + FP) — 오탐 최소화
- **Recall** = TP / (TP + FN) — 미탐 최소화
- **F1** = 2·P·R / (P + R) — 종합 지표

TP는 공격을 정확히 탐지, FP는 정상 트래픽을 공격으로 오탐, FN은 실제 공격을
놓친 경우입니다. 정상 사용자 트래픽 10,000건을 통과시켜 FP를 측정하고,
예외 구문으로 룰을 튜닝합니다.

## 방어 전후 비교표 (실측값 기록용)

| 측정 지표 | 방어 미적용 | 단일 방어(WAF) | 다층 방어(NIDS+WAF+하드닝) |
| --- | --- | --- | --- |
| 쿠키 탈취 성공률 |  |  |  |
| 세션 재사용 차단율 |  |  |  |
| XSS 탐지율(Recall) | — |  |  |
| 오탐률(FP) | — |  |  |
| 평균 탐지 지연 | — | — |  |

> 칸은 실제 실험값으로 채워 넣으세요. PPT의 예상치(쿠키 탈취 100→40→0%,
> Recall 96.5% 목표)는 목표일 뿐, 최종발표에서 실측값으로 확정해야 합니다.

## 로그 수집

```bash
# 유출 패킷 실시간 캐처(게이트웨이)
sudo tshark -i ens33 -Y 'http contains "document.cookie"' -w /tmp/xss.pcap
# Snort 경보
cat /var/log/snort/alert_fast.txt
# ModSecurity 감사 로그
sudo tail -f /var/log/modsec_audit.log
```

ISO/IEC 25010 품질 특성 중 **보안성(Security)**, **신뢰성(Reliability)**
관점으로 위 지표를 매핑해 보고서에 정리합니다.
