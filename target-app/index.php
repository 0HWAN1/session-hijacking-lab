<?php
// 대시보드 홈 — 로그인 필요.
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$db    = gw_db();
$count = (int) ($db->query('SELECT COUNT(*) AS c FROM guestbook')->fetch_assoc()['c'] ?? 0);

$pageTitle = '홈';
$activeNav = 'home';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h1>안녕하세요, <?= htmlspecialchars($_SESSION['user'], ENT_QUOTES) ?> 님</h1>
  <p class="sub">사내 그룹웨어 포털에 로그인되었습니다. 현재 세션으로 방명록을 이용할 수 있습니다.</p>
</div>

<div class="card">
  <h2>빠른 메뉴</h2>
  <p class="sub">📝 <a href="guestbook.php">방명록</a> — 등록된 글 <?= $count ?>건</p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
