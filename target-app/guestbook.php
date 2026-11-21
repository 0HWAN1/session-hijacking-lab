<?php
// =============================================================
//  방명록 — 저장형 XSS(Stored XSS) 취약점이 "의도적으로" 존재하는 페이지.
//  ⚠️ 교육용 격리 실습 전용. 실제 서비스에 이렇게 작성하면 절대 안 된다.
//
//  취약 포인트:
//    저장(INSERT)은 prepared statement 로 SQLi 를 막지만,
//    출력(echo) 시 htmlspecialchars 로 이스케이프하지 않아
//    저장된 <script> 가 피해자 브라우저에서 그대로 실행된다.
//
//  방어 ON(안전한 버전) 비교용: 아래 "안전한 출력" 주석을 참고.
// =============================================================
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$db = gw_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name']    ?? '';
    $message = $_POST['message'] ?? '';

    $stmt = $db->prepare('INSERT INTO guestbook (name, message) VALUES (?, ?)');
    $stmt->bind_param('ss', $name, $message);
    $stmt->execute();
    $stmt->close();

    header('Location: guestbook.php');   // PRG 패턴 — 새로고침 중복 저장 방지
    exit;
}

$rows = $db->query('SELECT name, message, created_at FROM guestbook ORDER BY id DESC');

$pageTitle = '방명록';
$activeNav = 'guestbook';
require __DIR__ . '/header.php';
?>
<div class="banner">
  ⚠️ 이 페이지는 저장형 XSS 실습용으로 입력값을 필터링하지 않습니다 (격리망 전용).
</div>

<div class="card">
  <h2>방명록 작성</h2>
  <form method="post" action="guestbook.php">
    <label>이름</label>
    <input type="text" name="name" maxlength="100">
    <label>메시지</label>
    <textarea name="message"></textarea>
    <button type="submit">등록</button>
  </form>
</div>

<div class="card">
  <h2>등록된 글</h2>
  <?php if ($rows->num_rows === 0): ?>
    <p class="sub">아직 등록된 글이 없습니다.</p>
  <?php else: ?>
    <?php while ($row = $rows->fetch_assoc()): ?>
      <div class="entry">
        <?php
          // ★ 취약점(의도적): 저장된 값을 이스케이프 없이 그대로 출력 → Stored XSS 실행
          echo '<b>' . $row['name'] . '</b>: ' . $row['message'];

          // ── 안전한 버전(방어 ON 비교용). 위 한 줄 대신 아래를 쓰면 XSS 가 막힌다. ──
          // echo '<b>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</b>: '
          //            . htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="meta"><?= htmlspecialchars($row['created_at'], ENT_QUOTES) ?></div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
