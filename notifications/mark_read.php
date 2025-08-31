<?php
// /notifications/mark_read.php
require_once __DIR__.'/../includes/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

try { check_csrf(); } catch (Throwable $e) {
  http_response_code(400);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$user = current_user();

if ($id > 0 && $user) {
  $st = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
  $st->execute([$id, $user['id']]);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
