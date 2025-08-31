<?php
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect($url) { header('Location: '.$url); exit; }
function now() { return date('Y-m-d H:i:s'); }

// ОТКЛЮЧЕН текстовый цензор
function banned_terms_found($text) {
  return false;
}

// Переводы статусов и ролей
function status_ru($s) {
  $map = [
    'PENDING_ACCEPTANCE' => 'Ожидает подтверждения',
    'AWAITING_FUNDING'   => 'Ожидает оплаты продавцом',
    'FUNDED'             => 'Средства зарезервированы',
    'IN_PROGRESS'        => 'В работе',
    'SUBMITTED'          => 'Отправлено на проверку',
    'ACCEPTED'           => 'Успешно завершена',
    'REJECTED'           => 'Отклонена',
    'DISPUTE_OPENED'     => 'Арбитраж',
    'RESOLVED_ACCEPTED'  => 'Успешно завершена (арбитраж)',
    'RESOLVED_REJECTED'  => 'Отклонена (арбитраж)',
  ];
  return $map[$s] ?? $s;
}
function payout_status_ru($s) {
  $map = [
    'PENDING'  => 'На проверке',
    'APPROVED' => 'Одобрена',
    'REJECTED' => 'Отклонена',
  ];
  return $map[$s] ?? $s;
}
function role_ru($r) {
  $map = [
    'SELLER' => 'Продавец',
    'BUYER'  => 'Покупатель',
    'ADMIN'  => 'Администратор',
  ];
  return $map[$r] ?? $r;
}

// Уведомления
function notify(PDO $pdo, int $userId, string $title, string $link) {
  $st = $pdo->prepare('INSERT INTO notifications (user_id, title, link) VALUES (?,?,?)');
  $st->execute([$userId, $title, $link]);
}
function unread_count(PDO $pdo, int $userId) {
  $st = $pdo->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0');
  $st->execute([$userId]);
  $r = $st->fetch();
  return (int)($r['c'] ?? 0);
}

// Системное сообщение в чат
function post_system_message(PDO $pdo, int $dealId, string $text) {
  $stmt = $pdo->prepare('INSERT INTO deal_messages (deal_id, sender_id, text) VALUES (?,?,?)');
  $stmt->execute([$dealId, 0, $text]);
}
