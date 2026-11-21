<?php
// 공통 레이아웃 헤더. $pageTitle, $activeNav 를 세팅한 뒤 include.
$pageTitle = $pageTitle ?? '사내 그룹웨어';
$activeNav = $activeNav ?? '';
$user      = $_SESSION['user'] ?? null;
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> · GW Portal</title>
<style>
  :root { --bg:#f4f6fb; --card:#fff; --line:#e2e8f0; --ink:#1e293b; --sub:#64748b; --accent:#2563eb; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:"Segoe UI","Malgun Gothic",sans-serif; background:var(--bg); color:var(--ink); }
  header.topbar { background:#0f172a; color:#fff; padding:0 24px; height:56px;
    display:flex; align-items:center; justify-content:space-between; }
  header.topbar .brand { font-weight:700; letter-spacing:.3px; }
  header.topbar .brand span { color:#60a5fa; }
  header.topbar nav a { color:#cbd5e1; text-decoration:none; margin-left:18px; font-size:14px; }
  header.topbar nav a.active, header.topbar nav a:hover { color:#fff; }
  .userbox { font-size:13px; color:#94a3b8; }
  .userbox a { color:#f87171; text-decoration:none; margin-left:12px; }
  main { max-width:860px; margin:28px auto; padding:0 20px; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:12px;
    padding:22px 24px; margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  h1 { font-size:20px; margin:0 0 4px; }
  h2 { font-size:16px; margin:0 0 14px; }
  .sub { color:var(--sub); font-size:14px; margin-top:0; }
  label { display:block; font-size:13px; color:var(--sub); margin:12px 0 4px; }
  input[type=text], input[type=password], textarea { width:100%; padding:10px 12px;
    border:1px solid var(--line); border-radius:8px; font-size:14px; font-family:inherit; }
  textarea { min-height:80px; resize:vertical; }
  button { margin-top:16px; background:var(--accent); color:#fff; border:0; border-radius:8px;
    padding:10px 18px; font-size:14px; cursor:pointer; }
  button:hover { background:#1d4ed8; }
  .entry { border-top:1px solid var(--line); padding:12px 0; font-size:14px; }
  .entry:first-of-type { border-top:0; }
  .entry .meta { color:var(--sub); font-size:12px; margin-top:4px; }
  .banner { background:#fef3c7; border:1px solid #fde68a; color:#92400e; font-size:13px;
    border-radius:8px; padding:10px 14px; margin-bottom:18px; }
  .err { background:#fee2e2; border:1px solid #fecaca; color:#991b1b; font-size:13px;
    border-radius:8px; padding:10px 14px; margin-bottom:14px; }
</style>
</head>
<body>
<header class="topbar">
  <div class="brand">GW <span>Portal</span> · 사내 그룹웨어</div>
  <?php if ($user): ?>
  <nav>
    <a href="index.php"     class="<?= $activeNav==='home'?'active':'' ?>">홈</a>
    <a href="guestbook.php" class="<?= $activeNav==='guestbook'?'active':'' ?>">방명록</a>
    <span class="userbox"><?= htmlspecialchars($user, ENT_QUOTES) ?> 님
      <a href="logout.php">로그아웃</a></span>
  </nav>
  <?php endif; ?>
</header>
<main>
