<?php
require_once __DIR__.'/../includes/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
  if (function_exists('check_csrf')) { check_csrf(); }
  $u = current_user();
  $st = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
  $st->execute([$u['id']]);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(200);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
