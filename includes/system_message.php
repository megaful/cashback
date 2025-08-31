<?php
// Безопасная системная запись в чат сделки, не ломающаяся из‑за FOREIGN KEY по sender_id.
if (!function_exists('safe_system_message')) {
  function safe_system_message(PDO $pdo, int $dealId, string $text, ?int $fallbackUserId = null) : void {
    try {
      // Попытка записать как системное сообщение (sender_id = NULL)
      $stmt = $pdo->prepare('INSERT INTO deal_messages (deal_id, sender_id, text) VALUES (?, NULL, ?)');
      $stmt->execute([$dealId, $text]);
    } catch (Throwable $e) {
      // Если колонка NOT NULL или FK не допускает NULL — пишем от имени fallback-пользователя
      if ($fallbackUserId) {
        try {
          $stmt = $pdo->prepare('INSERT INTO deal_messages (deal_id, sender_id, text) VALUES (?, ?, ?)');
          $stmt->execute([$dealId, $fallbackUserId, $text]);
        } catch (Throwable $e2) {
          error_log('[safe_system_message:fallback] '.$e2->getMessage());
        }
      } else {
        error_log('[safe_system_message] '.$e->getMessage());
      }
    }
  }
}
