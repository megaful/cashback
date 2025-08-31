<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain; charset=utf-8');
echo "poll_debug start\n";
require_once __DIR__.'/../includes/config.php';
echo "config included\n";

try {
  if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
  echo "session status=".session_status()." (2 active)\n";
  $u = function_exists('current_user') ? current_user() : null;
  echo "user=".json_encode($u, JSON_UNESCAPED_UNICODE)."\n";
  if (!$u) { echo "no user -> exit\n"; exit; }

  // quick SQL
  $st = $pdo->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND (is_read=0 OR is_read IS NULL)');
  $st->execute([$u['id']]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  echo "count=". (int)($row['cnt'] ?? 0) ."\n";
  echo "OK\n";
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n".$e->getFile().":".$e->getLine()."\n";
}
