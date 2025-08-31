<?php
// /store/respond.php — отклик покупателя на объявление: создание сделки из объявления
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/system_message.php'; // если есть
require_login();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Проверка колонок (динамическая схема)
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("SELECT COUNT(*) c
                      FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $q->execute([$table, $col]);
  return (int)($q->fetch()['c'] ?? 0) > 0;
}
function first_existing_col(PDO $pdo, string $table, array $candidates): ?string {
  foreach ($candidates as $c) if (col_exists($pdo, $table, $c)) return $c;
  return null;
}

/** Аварийный вывод аккуратной ошибки */
function fail_page(string $msg){
  http_response_code(400);
  echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.tailwindcss.com">';
  echo '<div class="max-w-xl mx-auto mt-10 p-6 bg-white border rounded-2xl">';
  echo '<h1 class="text-xl font-semibold">Нельзя создать сделку</h1>';
  echo '<p class="mt-2 text-slate-700">'.h($msg).'</p>';
  echo '<a class="inline-block mt-4 px-4 py-2 rounded-xl border" href="/store/index.php">Вернуться в витрину</a>';
  echo '</div>';
  exit;
}

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
try { check_csrf(); } catch (Throwable $e) { fail_page('Сессия истекла. Обновите страницу и попробуйте снова.'); }

$listingId = (int)($_POST['id'] ?? 0);
if ($listingId <= 0) fail_page('Не передан ID объявления.');

if (strtoupper($user['role'] ?? '') !== 'BUYER') {
  fail_page('Отклик доступен только пользователям с ролью «Покупатель».');
}

// Подтягиваем объявление
$st = $pdo->prepare("SELECT * FROM listings WHERE id = ? LIMIT 1");
$st->execute([$listingId]);
$listing = $st->fetch();
if (!$listing) fail_page('Объявление не найдено.');

// Проверки статуса и владельца
$status = strtoupper($listing['status'] ?? 'PENDING');
if ($status !== 'ACTIVE') {
  fail_page('Объявление недоступно для отклика (не активно).');
}
$sellerId = (int)($listing['seller_id'] ?? 0);
if ($sellerId <= 0) fail_page('У объявления не задан продавец.');
if ($sellerId === (int)$user['id']) fail_page('Нельзя откликаться на собственное объявление.');

// Определяем реальные названия ключевых полей в listings
$urlCol   = first_existing_col($pdo, 'listings', ['url','product_url','link']);
$cashCol  = first_existing_col($pdo, 'listings', ['cashback','reward','amount','price','cb','sum']);
$descCol  = first_existing_col($pdo, 'listings', ['description','terms','conditions']);
$slotsCol = first_existing_col($pdo, 'listings', ['slots','remaining','max_deals']);

if (!$urlCol || !$cashCol || !$descCol) {
  fail_page('В объявлении отсутствуют необходимые поля (ссылка/кэшбэк/условия). Обратитесь к администратору.');
}

// Проверка слотов (если колонка есть)
if ($slotsCol) {
  $slotsVal = (int)$listing[$slotsCol];
  if ($slotsVal <= 0) {
    fail_page('Все слоты по этому объявлению уже заняты.');
  }
}

// Данные для сделки из объявления
$title      = trim((string)($listing['title'] ?? 'Без названия'));
$productUrl = trim((string)$listing[$urlCol]);
$cashback   = (int)preg_replace('~[^\d]~u', '', (string)$listing[$cashCol]);
$terms      = trim((string)$listing[$descCol]);
if ($cashback <= 0) fail_page('В объявлении указан некорректный кэшбэк.');

// Готовим вставку сделки
$commission = 100;

// Проверим наличие deals.listing_id перед вставкой
$hasListingId = col_exists($pdo, 'deals', 'listing_id');

// Создаём сделку
$pdo->beginTransaction();
try {
  // 1) вставляем сделку (с номером-заглушкой, потом обновим)
  $cols = ['number','seller_id','buyer_id','created_by','title','product_url','cashback','commission','terms_text','status'];
  $vals = ['?','?','?','?','?','?','?','?','?','?'];
  $args = ['TMP', $sellerId, (int)$user['id'], (int)$user['id'], $title, $productUrl, $cashback, $commission, $terms, 'PENDING_ACCEPTANCE'];

  if ($hasListingId) {
    $cols[] = 'listing_id';
    $vals[] = '?';
    $args[] = $listingId;
  }

  $sql = "INSERT INTO deals (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
  $ins = $pdo->prepare($sql);
  $ins->execute($args);

  $dealId = (int)$pdo->lastInsertId();
  $number = 'СДЕЛКА-'.str_pad((string)$dealId, 6, '0', STR_PAD_LEFT);

  // 2) обновим номер
  $pdo->prepare("UPDATE deals SET number=? WHERE id=?")->execute([$number, $dealId]);

  // 3) (опционально) система-сообщение в чат сделки
  if (function_exists('safe_system_message')) {
    safe_system_message($pdo, $dealId,
      "Создана заявка по объявлению (авто). Продавец должен подтвердить условия.",
      $user['id']
    );
  }

  // 4) уведомления
  if (function_exists('notify')) {
    notify($pdo, $sellerId, "Новая заявка по объявлению", "/deals/view.php?id=".$dealId);
    notify($pdo, (int)$user['id'], "Заявка создана", "/deals/view.php?id=".$dealId);
  }

  // ВАЖНО: слоты уменьшаем не сейчас, а когда продавец подтвердит условия,
  // чтобы не «захватывать» слот без подтверждения. (Если нужно — можно перенести сюда.)
  $pdo->commit();

  // PRG: редирект на страницу «успеха» или сразу в сделку
  if (file_exists(__DIR__.'/../deals/success.php')) {
    header('Location: /deals/success.php?id='.$dealId);
  } else {
    header('Location: /deals/view.php?id='.$dealId);
  }
  exit;

} catch (Throwable $e) {
  $pdo->rollBack();
  // Аккуратно покажем ошибку
  fail_page('Ошибка при создании сделки: '.$e->getMessage());
}
