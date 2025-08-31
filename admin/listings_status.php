<?php
// /admin/listings_status.php — смена статуса объявления + запись причины (если есть колонка)
require_once __DIR__ . '/../includes/config.php';
require_login();

// Безопасная проверка прав админа
function _is_admin_safe() {
  if (function_exists('is_admin')) return is_admin();
  $u = current_user();
  return strtoupper($u['role'] ?? '') === 'ADMIN';
}
if (!_is_admin_safe()) { http_response_code(403); exit('Доступ запрещён'); }

// Утилиты
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare(
    "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
  );
  $q->execute([$table, $col]);
  return (int)($q->fetch()['c'] ?? 0) > 0;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Method Not Allowed');
  }

  if (function_exists('check_csrf')) {
    try { check_csrf(); } catch (Throwable $e) { throw new Exception('CSRF'); }
  }

  $id     = (int)($_POST['id'] ?? 0);
  $status = strtoupper(trim($_POST['status'] ?? ''));
  $reason = trim($_POST['reason'] ?? '');

  $allowed = ['PENDING','ACTIVE','REJECTED','ARCHIVED'];
  if ($id <= 0 || !in_array($status, $allowed, true)) {
    throw new Exception('Некорректные данные запроса');
  }

  // Загружаем объявление
  $st = $pdo->prepare(
    "SELECT l.*, u.login AS seller_login, u.id AS seller_user_id
     FROM listings l
     JOIN users u ON u.id = l.seller_id
     WHERE l.id = ? LIMIT 1"
  );
  $st->execute([$id]);
  $listing = $st->fetch();
  if (!$listing) throw new Exception('Объявление не найдено');

  // Какие колонки реально есть в таблице?
  $hasReason   = col_exists($pdo, 'listings', 'reason');
  $hasUpdated  = col_exists($pdo, 'listings', 'updated_at');

  // Формируем динамический UPDATE
  $set = ['status = :status'];
  $params = [':status' => $status, ':id' => $id];

  if ($hasUpdated) $set[] = 'updated_at = NOW()';

  if ($hasReason) {
    if ($status === 'ACTIVE') {
      // При активации обнуляем причину
      $set[] = 'reason = NULL';
    } elseif ($status === 'REJECTED' || $status === 'ARCHIVED') {
      // При отклонении/архиве записываем причину (или «Без комментария»)
      $set[] = 'reason = :reason';
      $params[':reason'] = ($reason !== '') ? $reason : 'Без комментария';
    }
  }

  $sql = "UPDATE listings SET ".implode(', ', $set)." WHERE id = :id";
  $upd = $pdo->prepare($sql);
  $upd->execute($params);

  // Уведомление продавцу (если есть notify)
  if (function_exists('notify')) {
    $ru = [
      'PENDING'  => 'отправлено на модерацию',
      'ACTIVE'   => 'одобрено',
      'REJECTED' => 'отклонено',
      'ARCHIVED' => 'переведено в архив',
    ];
    $title = 'Обновление статуса объявления';
    $msg   = 'Ваше объявление "'.(($listing['title'] ?? '') ?: 'без названия').'" '.$ru[$status].'.';
    if (($status === 'REJECTED' || $status === 'ARCHIVED') && $hasReason) {
      $msg .= ' Причина: '.(($reason !== '') ? $reason : 'Без комментария');
    }
    notify($pdo, (int)$listing['seller_user_id'], $title, '/seller/listings/index.php');
  }

  // Назад в админку
  $back = $_SERVER['HTTP_REFERER'] ?? '/admin/listings.php';
  header('Location: '.$back);
  exit;

} catch (Throwable $e) {
  // Показать понятную ошибку вместо HTTP 500
  http_response_code(200);
  ?>
  <!doctype html>
  <meta charset="utf-8">
  <link rel="stylesheet" href="https://cdn.tailwindcss.com">
  <div class="max-w-xl mx-auto mt-10 p-6 rounded-2xl border bg-red-50 text-red-800">
    <h1 class="text-xl font-semibold">Не удалось обновить статус</h1>
    <p class="mt-2"><?php echo h($e->getMessage()); ?></p>
    <details class="mt-3 text-sm opacity-80">
      <summary>Тех. детали</summary>
      <pre class="whitespace-pre-wrap mt-2"><?php echo h((string)$e); ?></pre>
    </details>
    <a class="inline-block mt-4 px-4 py-2 rounded-xl border" href="/admin/listings.php">Вернуться</a>
  </div>
  <?php
}
