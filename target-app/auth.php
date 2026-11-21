<?php
// 로그인 가드 — 세션이 없으면 로그인 페이지로 보낸다.
// session_start() 가 PHPSESSID 쿠키를 발급한다 = 탈취 대상 세션.
//
// 참고(세션 하드닝 검증용):
//   아래 session_set_cookie_params 를 켜면 HttpOnly/SameSite 가 적용되어
//   XSS 로 document.cookie 를 읽어도 PHPSESSID 가 빠져나가지 않는다.
//   베이스라인(방어 OFF) 실습에서는 주석 상태로 둔다.
//
// session_set_cookie_params([
//     'httponly' => true,
//     'samesite' => 'Strict',
// ]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
