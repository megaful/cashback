<?php
require_once __DIR__.'/../includes/config.php';
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Базовая валидация
if (!$data || !isset($data['event']) || !isset($data['object'])) {
  http_response_code(400); echo 'bad payload'; exit;
}

$event = $data['event'];
$object= $data['object'];

if ($event !== 'payment.succeeded') { http_response_code(200); echo 'ignored'; exit; }

$ykId = $object['id'] ?? null;
if (!$ykId) { http_response_code(400); echo 'no id'; exit; }

// Дополнительная проверка — подтянуть объект платежа с сервера ЮKassa
$ch = curl_init('https://api.yookassa.ru/v3/payments/'.$ykId);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
  CURLOPT_USERPWD        => YOO_SHOP_ID.':'.YOO_SECRET_KEY,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) { http_response_code(403); echo 'verify failed'; exit; }

$pay = json_decode($res, true);
if (($pay['status'] ?? '') !== 'succeeded') { http_response_code(200); echo 'not succeeded'; exit; }

$dealId   = (int)($pay['metadata']['deal_id']   ?? 0);
$sellerId = (int)($pay['metadata']['seller_id'] ?? 0);
$amount   = (int)round((float)$pay['amount']['value']);

// Обновим нашу запись платежа
$pdo->prepare("UPDATE yk_payments SET status='succeeded', updated_at=NOW() WHERE yk_payment_id=?")
    ->execute([$ykId]);

// Подтянем сделку и убедимся, что она ещё ждёт оплаты
$st = $pdo->prepare("SELECT * FROM deals WHERE id=? FOR UPDATE");
$pdo->beginTransaction();
$st->execute([$dealId]);
$deal = $st->fetch();

if ($deal && $deal['status']==='AWAITING_FUNDING') {
  // Переводим в FUNDED
  $pdo->prepare("UPDATE deals SET status='FUNDED' WHERE id=?")->execute([$dealId]);

  // Сообщение в чат
  if (function_exists('safe_system_message')) {
    $txt = "Оплата в гарант успешно проведена через ЮKassa. Сумма: ₽ {$amount}.";
    safe_system_message($pdo, $dealId, $txt, (int)$sellerId);
  }
  // Уведомления
  if (function_exists('notify')) {
    notify($pdo, (int)$deal['buyer_id'], "Сделка {$deal['number']} оплачена", "/deals/view.php?id=".$dealId);
    notify($pdo, (int)$deal['seller_id'], "Оплата по {$deal['number']} прошла успешно", "/deals/view.php?id=".$dealId);
  }
}
$pdo->commit();

http_response_code(200);
echo 'ok';
