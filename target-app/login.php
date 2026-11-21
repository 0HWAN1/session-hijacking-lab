<?php
// 로그인 — 성공 시 세션 생성(PHPSESSID 발급).
require __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 자격증명 조회는 prepared statement 사용 (이 랩의 초점은 SQLi 가 아니라 XSS).
    $db   = gw_db();
    $stmt = $db->prepare('SELECT password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($stored);

    if ($stmt->fetch() && hash_equals($stored, $password)) {
        $stmt->close();
        session_regenerate_id(true);       // 로그인 시 세션 고정 방지
        $_SESSION['user'] = $username;
        header('Location: index.php');
        exit;
    }
    $stmt->close();
    $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
}

$pageTitle = '로그인';
require __DIR__ . '/header.php';
?>
<div class="card" style="max-width:380px;margin:40px auto">
  <h1>로그인</h1>
  <p class="sub">사내 그룹웨어 포털</p>
  <?php if ($error): ?><div class="err"><?= $error /* 고정 메시지, 사용자 입력 미반영 */ ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <label>아이디</label>
    <input type="text" name="username" autofocus autocomplete="username">
    <label>비밀번호</label>
    <input type="password" name="password" autocomplete="current-password">
    <button type="submit">로그인</button>
  </form>
  <p class="sub" style="margin-top:18px">실습 계정: <b>admin</b> / <b>password</b></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
