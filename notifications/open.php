<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM notifications WHERE id=? AND user_id=?');
$st->execute([$id, $user['id']]);
$n = $st->fetch();
if ($n) {
  $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=?')->execute([$id]);
  redirect($n['link']);
}
redirect('/notifications/index.php');
