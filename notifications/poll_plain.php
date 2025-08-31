<?php
// ultra-simple endpoint to ensure PHP runs and session resolves user
require_once __DIR__.'/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$user = function_exists('current_user') ? current_user() : null;
$user_id = (int)($user['id'] ?? 0);
@session_write_close();

try {
  if (!$user_id) { echo json_encode(['ok'=>true,'count'=>0,'note'=>'no user']); exit; }
  $st = $pdo->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND (is_read=0 OR is_read IS NULL)');
  $st->execute([$user_id]);
  $count = (int)($st->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
  echo json_encode(['ok'=>true,'count'=>$count]);
} catch (Throwable $e) {
  http_response_code(200);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'count'=>0]);
}
