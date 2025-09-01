<?php
/**
 * /pay/yookassa_create.php
 * Создание платежа в ЮKassa для оплаты гаранта по сделке (с чеком 54-ФЗ).
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/config.php';
if (function_exists('require_login')) require_login();
$user = function_exists('current_user') ? current_user() : null;
if (!$user || empty($user['id'])) {
  http_response_code(403);
  exit('Auth required');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function fail($msg, $code = 400){
  http_response_code($code);
  echo '<!doctype html><meta charset="utf-8"><div style="font-family:system-ui;padding:16px;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px;max-width:720px;margin:24px auto">'
     . '<div style="font-weight:600;color:#9f1239">Ошибка</div>'
     . '<div style="margin-top:6px;color:#7f1d1d">'.h($msg).'</div>'
     . '<div style="margin-top:10px"><a href="javascript:history.back()">← Назад</a></div>'
     . '</div>';
  exit;
}
function has_table(PDO $pdo, string $table): bool {
  $q = $pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
  $q->execute([$table]); return (int)($q->fetch()['c'] ?? 0) > 0;
}

/* ------------ входные данные ------------ */
$dealId = isset($_POST['deal_id']) ? (int)$_POST['deal_id']
        : (isset($_GET['deal_id']) ? (int)$_GET['deal_id'] : 0);
if ($dealId <= 0) fail('Bad deal id');

/* ------------ читаем сделку ------------ */
$st = $pdo->prepare("SELECT * FROM deals WHERE id=?");
$st->execute([$dealId]);
$deal = $st->fetch(PDO::FETCH_ASSOC);
if (!$deal) fail('Deal not found', 404);

if ((int)$deal['seller_id'] !== (int)$user['id']) {
  fail('Только продавец этой сделки может оплатить гарантию', 403);
}
if ((string)$deal['status'] !== 'AWAITING_FUNDING') {
  fail('Эта сделка сейчас не ожидает оплаты', 409);
}

/* ------------ сумма платежа ------------ */
$cashback   = (int)($deal['cashback'] ?? 0);
$commission = (int)($deal['commission'] ?? 100);
if ($cashback <= 0) fail('Некорректная сумма кэшбэка в сделке');

$amountInt = $cashback + $commission;
$amountStr = number_format($amountInt, 2, '.', ''); // "1234.00"

/* ------------ конфиг ЮKassa (из defines) ------------ */
$shopId    = defined('YOO_SHOP_ID')    ? YOO_SHOP_ID    : null;
$secretKey = defined('YOO_SECRET_KEY') ? YOO_SECRET_KEY : null;
if (!$shopId || !$secretKey) {
  fail('ЮKassa: не заданы константы YOO_SHOP_ID / YOO_SECRET_KEY в includes/config.php', 500);
}

/* 54-ФЗ: СНО и НДС */
$taxSystem = defined('YOO_TAX_SYSTEM') ? (int)YOO_TAX_SYSTEM : 2; // дефолт: УСН доход
$vatCode   = defined('YOO_VAT_CODE')   ? (int)YOO_VAT_CODE   : 6; // дефолт: без НДС

/* ------------ контакт покупателя для чека ------------ */
$customerEmail = '';
$customerPhone = '';
if (!empty($user['email'])) $customerEmail = (string)$user['email'];
if (empty($customerEmail)) {
  // попробуем вытянуть email покупателя из deals.buyer_id
  try {
    $u = $pdo->prepare("SELECT email, phone FROM users WHERE id=?");
    $u->execute([(int)$deal['buyer_id']]);
    if ($row = $u->fetch(PDO::FETCH_ASSOC)) {
      $customerEmail = (string)($row['email'] ?? '');
      $customerPhone = (string)($row['phone'] ?? '');
    }
  } catch (Throwable $e) { /* ignore */ }
}

/* ------------ return_url ------------ */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$returnUrl = $scheme . '://' . $host . '/deals/view.php?id=' . $dealId;

/* ------------ формируем чек (receipt) ------------ */
$dealNumber = $deal['number'] ?? ('DEAL#'.$deal['id']);
$itemName = "Оплата гаранта по сделке {$dealNumber}";

$receipt = [
  'customer' => array_filter([
    'email' => $customerEmail ?: null,
    'phone' => $customerPhone ?: null,
  ]),
  'items' => [[
    'description'    => mb_substr($itemName, 0, 128),
    'quantity'       => '1.00',
    'amount'         => ['value' => $amountStr, 'currency' => 'RUB'],
    'vat_code'       => $vatCode,           // 1=20%, 2=10%, 3=20/120, 4=10/110, 5=0%, 6=без НДС
    'payment_subject'=> 'service',          // услуга
    'payment_mode'   => 'full_payment',     // полный расчёт
  ]],
  'tax_system_code' => $taxSystem,          // 1..6
];

/* ------------ формируем платёж ------------ */
$payload = [
  'amount' => [
    'value'    => $amountStr,
    'currency' => 'RUB',
  ],
  'capture'      => true,
  'description'  => $itemName,
  'confirmation' => [
    'type'       => 'redirect',
    'return_url' => $returnUrl,
  ],
  'metadata' => [
    'deal_id'   => (int)$dealId,
    'seller_id' => (int)$user['id'],
    'cashback'  => (int)$cashback,
    'commission'=> (int)$commission,
  ],
  'receipt' => $receipt,
];

/* ------------ запрос в ЮKassa ------------ */
$ch = curl_init('https://api.yookassa.ru/v3/payments');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    'Content-Type: application/json',
    'Idempotence-Key: ' . bin2hex(random_bytes(16)),
  ],
  CURLOPT_USERPWD        => $shopId . ':' . $secretKey, // HTTP Basic Auth
  CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  CURLOPT_CONNECTTIMEOUT => 15,
  CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
  fail('ЮKassa: нет ответа: ' . $curlErr, 502);
}

$data = json_decode($response, true);
if ($httpCode < 200 || $httpCode >= 300) {
  $errTxt = $data['description'] ?? $response;
  fail('ЮKassa: ошибка создания платежа (' . $httpCode . '): ' . $errTxt, 502);
}

$paymentId  = $data['id'] ?? null;
$confirmUrl = $data['confirmation']['confirmation_url'] ?? null;
if (!$paymentId || !$confirmUrl) {
  fail('ЮKassa: неполный ответ, нет payment id или confirmation_url', 502);
}

/* ------------ логируем платёж (если таблица есть) ------------ */
if (has_table($pdo, 'yookassa_payments')) {
  try {
    $pdo->prepare("INSERT INTO yookassa_payments (payment_id, deal_id, seller_id, amount, status, created_at)
                   VALUES (?,?,?,?,?,NOW())")
        ->execute([$paymentId, (int)$dealId, (int)$user['id'], $amountInt, $data['status'] ?? 'pending']);
  } catch (Throwable $e) {
    // не критично
  }
}

/* ------------ редирект на оплату ------------ */
header('Location: ' . $confirmUrl, true, 302);
exit;
