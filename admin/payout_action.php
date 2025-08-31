<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();

if ($_SERVER['REQUEST_METHOD']!=='POST') { redirect('/admin/index.php'); }
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$do = $_POST['do'] ?? '';
$reason = trim($_POST['reason'] ?? '');

$st = $pdo->prepare('SELECT * FROM payout_requests WHERE id=?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) die('Заявка не найдена');

if ($r['status'] !== 'PENDING') {
  redirect('/admin/index.php');
}

if ($do==='approve') {
  $pdo->prepare('UPDATE payout_requests SET status="APPROVED", processed_at=NOW(), admin_comment=NULL WHERE id=?')->execute([$id]);
  if (function_exists('notify')) notify($pdo, (int)$r['user_id'], 'Выплата одобрена', '/payouts/history.php');
}

if ($do==='reject') {
  if ($reason==='') { flash('error','Нужен комментарий при отклонении'); redirect('/admin/index.php'); }
  $pdo->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')->execute([$r['amount'], $r['user_id']]);
  $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo) VALUES (?, ?, "CREDIT", "Возврат по отклоненной заявке на вывод")')
      ->execute([$r['user_id'], $r['amount']]);
  $pdo->prepare('UPDATE payout_requests SET status="REJECTED", processed_at=NOW(), admin_comment=? WHERE id=?')->execute([$reason,$id]);
  if (function_exists('notify')) notify($pdo, (int)$r['user_id'], 'Выплата отклонена', '/payouts/history.php');
}
redirect('/admin/index.php');
