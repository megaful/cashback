<?php
require_once __DIR__.'/../../includes/config.php';
require_login();
$user = current_user();

if (strtoupper($user['role']) !== 'SELLER' && !is_admin()) {
  http_response_code(403); exit('Доступ запрещён');
}

try {
  check_csrf();
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new Exception('Некорректный ID');

  $st = $pdo->prepare("SELECT id, seller_id, status FROM listings WHERE id=? LIMIT 1");
  $st->execute([$id]); $row = $st->fetch();
  if (!$row) throw new Exception('Объявление не найдено');
  if (!is_admin() && (int)$row['seller_id'] !== (int)$user['id']) throw new Exception('Недостаточно прав');

  if ($row['status'] !== 'ARCHIVED') {
    $pdo->prepare("UPDATE listings SET status='ARCHIVED', updated_at=NOW() WHERE id=?")->execute([$id]);
  }

  if (function_exists('notify')) {
    notify($pdo, (int)$user['id'], 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived');
  }

  header('Location: /seller/listings/index.php?tab=archived'); exit;

} catch (Throwable $e) {
  http_response_code(200);
  echo '<!doctype html><meta charset="utf-8"><div style="font-family:system-ui;padding:24px">';
  echo '<h1 style="font-size:18px;margin:0 0 8px">Не удалось деактивировать объявление</h1>';
  echo '<div style="color:#7f1d1d;background:#fee2e2;border:1px solid #fecaca;border-radius:12px;padding:12px">';
  echo htmlspecialchars($e->getMessage(), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
  echo '</div><p><a href="/seller/listings/index.php">← Вернуться</a></p></div>';
}
