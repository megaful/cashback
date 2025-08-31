<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/system_message.php';
require_login();

$user = current_user();
if (($user['role'] ?? '') !== 'BUYER') {
  http_response_code(403);
  echo 'Отклик доступен только пользователям со статусом «Покупатель».';
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

$listing_id = (int)($_POST['listing_id'] ?? 0);
if ($listing_id <= 0) { http_response_code(400); echo 'Некорректный запрос.'; exit; }
if (function_exists('check_csrf')) { try { check_csrf(); } catch(Throwable $e) { http_response_code(400); echo 'CSRF error'; exit; } }

// тянем объявление + продавца
$st = $pdo->prepare("SELECT l.*, u.id AS seller_id, u.login AS seller_login
                     FROM listings l JOIN users u ON u.id=l.seller_id
                     WHERE l.id=?");
$st->execute([$listing_id]);
$l = $st->fetch();
if (!$l) { http_response_code(404); echo 'Объявление не найдено.'; exit; }

// проверки доступности
if ($l['status'] !== 'ACTIVE') { http_response_code(400); echo 'Объявление не активно.'; exit; }

// слоты
$st = $pdo->prepare("SELECT COUNT(*) c FROM deals WHERE listing_id=? AND status NOT IN ('REJECTED','RESOLVED_REJECTED')");
$st->execute([$listing_id]);
$used = (int)($st->fetch()['c'] ?? 0);
$left = max(0, (int)$l['quantity_limit'] - $used);
if ($left <= 0) { http_response_code(400); echo 'Слоты по объявлению закончились.'; exit; }

// уже есть живая сделка этого покупателя по этой объяве?
$st = $pdo->prepare("SELECT COUNT(*) c FROM deals WHERE listing_id=? AND buyer_id=? AND status NOT IN ('REJECTED','RESOLVED_REJECTED')");
$st->execute([$listing_id, $user['id']]);
if ((int)($st->fetch()['c'] ?? 0) > 0) { http_response_code(400); echo 'У вас уже есть сделка по этому объявлению.'; exit; }

// создаём сделку: продавец из объявления, покупатель — текущий, сумма и ссылка из объявления
$seller_id   = (int)$l['seller_id'];
$buyer_id    = (int)$user['id'];
$title       = (string)$l['title'];
$product_url = (string)$l['product_url'];   // у тебя столбец уже переименован
$cashback    = (int)$l['cashback_rub'];
$commission  = 100;

// Особенность: по сделкам из витрины «Принять условия» нажимает ИМЕННО ПРОДАВЕЦ.
// Мы реализуем это правилом в deals/view.php: если listing_id IS NOT NULL — кнопку видит только продавец.
try {
  $pdo->beginTransaction();

  $ins = $pdo->prepare('INSERT INTO deals
      (number, seller_id, buyer_id, created_by, title, product_url, cashback, commission, terms_text, listing_id, status)
      VALUES ("TMP", ?, ?, ?, ?, ?, ?, ?, ?, ?, "PENDING_ACCEPTANCE")');
  $terms = trim((string)$l['description']);
  if ($terms === '') {
    $terms = "Сделка создана по объявлению #{$l['id']} (Витрина). Следуйте условиям, описанным в карточке объявления.";
  }
  $ins->execute([$seller_id, $buyer_id, $buyer_id, $title, $product_url, $cashback, $commission, $terms, $listing_id]);
  $dealId = (int)$pdo->lastInsertId();

  $number = 'СДЕЛКА-'.str_pad((string)$dealId, 6, '0', STR_PAD_LEFT);
  $pdo->prepare('UPDATE deals SET number=? WHERE id=?')->execute([$number, $dealId]);

  // системное сообщение + уведомление продавцу
  if (function_exists('safe_system_message')) {
    safe_system_message($pdo, $dealId, "Сделка {$number} создана по объявлению #{$listing_id}. Продавец должен подтвердить условия.", $buyer_id);
  }
  if (function_exists('notify')) {
    notify($pdo, $seller_id, "Новый отклик по объявлению: {$number}", "/deals/view.php?id=".$dealId);
  }

  $pdo->commit();

  // редирект на страницу успеха
  if (function_exists('safe_redirect')) {
    safe_redirect('/deals/success.php?id='.$dealId);
  } else {
    header('Location: /deals/success.php?id='.$dealId);
  }
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('[create_from_listing] '.$e->getMessage());
  http_response_code(500);
  echo 'Не удалось создать сделку: '.h($e->getMessage());
  exit;
}
