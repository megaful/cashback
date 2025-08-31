<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/redirect.php';
require_once __DIR__.'/../includes/system_message.php';
$utils = __DIR__.'/../includes/utils.php'; if (file_exists($utils)) require_once $utils;
// Хелперы витрины (если есть)
$listings_lib = __DIR__.'/../includes/listings_lib.php'; if (file_exists($listings_lib)) require_once $listings_lib;

require_login();
$user = current_user();

function is_valid_http_url(string $url): bool {
  if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
  $parts = parse_url($url);
  if (!$parts) return false;
  if (!isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http','https'])) return false;
  if (empty($parts['host'])) return false;
  if (preg_match('~[\x00-\x1F\x7F\s]~u', $url)) return false;
  return true;
}

/** -------- Префилл из витрины -------- */
$from_listing = (int)($_GET['from_listing'] ?? $_POST['from_listing'] ?? 0);
$prefill = null;
$prefill_errors = [];

if ($from_listing > 0 && function_exists('listing_slots_left')) {
  // Тянем объявление вместе с логином продавца
  $st = $pdo->prepare("SELECT l.*, u.login AS seller_login FROM listings l JOIN users u ON u.id=l.seller_id WHERE l.id=?");
  $st->execute([$from_listing]);
  $prefill = $st->fetch();

  if (!$prefill) {
    $prefill_errors[] = 'Объявление не найдено.';
    $prefill = null;
  } else {
    // Разрешаем откликаться только покупателю
    if (($user['role'] ?? '') !== 'BUYER') {
      $prefill_errors[] = 'Отклик по объявлению доступен только пользователям со статусом «Покупатель».';
      $prefill = null;
    } else {
      // Проверки статуса и свободных слотов
      if (($prefill['status'] ?? '') !== 'ACTIVE') {
        $prefill_errors[] = 'Объявление недоступно для отклика (не активно).';
        $prefill = null;
      } elseif (!listing_can_buyer_apply($pdo, $prefill, (int)$user['id'])) {
        $prefill_errors[] = 'Вы уже откликались на это объявление или слоты закончились.';
        $prefill = null;
      }
    }
  }
}

// Значения по умолчанию для формы (если префилл прошёл)
$default_other_nick = $prefill ? ($prefill['seller_login'] ?? '') : '';
$default_title      = $prefill ? ($prefill['title'] ?? '') : '';
$default_url        = $prefill ? ($prefill['product_url'] ?? '') : '';
$default_cashback   = $prefill ? (int)($prefill['cashback_rub'] ?? 0) : 0;
$default_terms      = '';
if ($prefill) {
  $default_terms = "Покупка товара по объявлению #{$prefill['id']} на витрине.\n"
                 . "Следуйте условиям из описания объявления.\n";
}

/** -------- Обработка создания -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    check_csrf();

    $other_nick = trim($_POST['other_nick'] ?? '');
    $title      = trim($_POST['title'] ?? '');
    $product_url= trim($_POST['product_url'] ?? '');
    $cashback   = (int)($_POST['cashback'] ?? 0);
    $terms      = trim($_POST['terms'] ?? '');

    // Если префилл валиден — жёстко берём данные из объявления (не доверяем полям формы)
    if ($from_listing > 0 && $prefill) {
      $other_nick = $prefill['seller_login'] ?? '';
      $title      = $prefill['title'] ?? '';
      $product_url= $prefill['product_url'] ?? '';
      $cashback   = (int)($prefill['cashback_rub'] ?? 0);
      if ($terms === '') {
        $terms = $default_terms;
      }
    }

    // Базовая проверка
    if (!$other_nick || !$title || !$product_url || $cashback < 1 || !$terms) {
      throw new Exception('Заполните все поля корректно');
    }
    if (!is_valid_http_url($product_url)) {
      throw new Exception('Поле «Ссылка на товар» заполнено некорректно. Укажите действительный URL (http/https).');
    }

    // Контрагент
    $stmt = $pdo->prepare('SELECT id, role, login FROM users WHERE login = ? LIMIT 1');
    $stmt->execute([$other_nick]);
    $other = $stmt->fetch();
    if (!$other) throw new Exception('Пользователь с таким ником не найден');

    if ($other['role'] === $user['role']) throw new Exception('Сделки между одинаковыми ролями запрещены');

    $seller_id = $user['role']==='SELLER' ? $user['id'] : (int)$other['id'];
    $buyer_id  = $user['role']==='BUYER'  ? $user['id'] : (int)$other['id'];

    // Если префилл был — дополнительно страхуемся, что контрагент действительно продавец из объявления
    if ($from_listing > 0 && $prefill) {
      if ((int)$prefill['seller_id'] !== (int)$seller_id) {
        throw new Exception('Контрагент не соответствует продавцу из объявления.');
      }
    }

    // Создание сделки
    $commission = 100;

    // Пытаемся вставить с колонкой listing_id (если миграция применена)
    $dealId = 0;
    try {
      $pdo->prepare('INSERT INTO deals (number,seller_id,buyer_id,created_by,title,product_url,cashback,commission,terms_text,listing_id,status) 
                     VALUES (?,?,?,?,?,?,?,?,?,?,"PENDING_ACCEPTANCE")')
          ->execute([
            'TMP', $seller_id, $buyer_id, $user['id'],
            $title, $product_url, $cashback, $commission, $terms,
            ($prefill ? (int)$prefill['id'] : null)
          ]);
      $dealId = (int)$pdo->lastInsertId();
    } catch (PDOException $ex) {
      // Если нет столбца listing_id — откатываемся на старый INSERT без привязки
      if (strpos($ex->getMessage(), 'Unknown column') !== false || $ex->getCode() === '42S22') {
        $pdo->prepare('INSERT INTO deals (number,seller_id,buyer_id,created_by,title,product_url,cashback,commission,terms_text,status) 
                       VALUES (?,?,?,?,?,?,?,?,?,"PENDING_ACCEPTANCE")')
            ->execute([
              'TMP', $seller_id, $buyer_id, $user['id'],
              $title, $product_url, $cashback, $commission, $terms
            ]);
        $dealId = (int)$pdo->lastInsertId();
      } else {
        throw $ex;
      }
    }

    // Номер сделки
    $number = 'СДЕЛКА-'.str_pad((string)$dealId, 6, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE deals SET number = ? WHERE id = ?')->execute([$number,$dealId]);

    // Системное сообщение и уведомление
    safe_system_message($pdo, $dealId, "Создана новая сделка {$number}. Вторая сторона должна принять условия.", $user['id']);
    if (function_exists('notify')) {
      $recipient = ($user['role']==='SELLER') ? $buyer_id : $seller_id;
      notify($pdo, $recipient, "Новая сделка {$number}", "/deals/view.php?id=".$dealId);
    }

    // Если использовалось объявление — можно сразу проверить, не исчерпаны ли слоты
    if ($prefill && function_exists('listing_auto_archive_if_full')) {
      listing_auto_archive_if_full($pdo, (int)$prefill['id']);
    }

    // PRG: успех -> страница подтверждения
    safe_redirect('/deals/success.php?id='.$dealId);

  } catch (Throwable $e) {
    error_log('[create_deal] '.$e->getMessage());
    http_response_code(200);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css">';
    echo '<div class="max-w-xl mx-auto mt-10 p-6 bg-white border rounded-2xl">';
    echo '<h1 class="text-xl font-semibold">Не удалось создать сделку</h1>';
    echo '<p class="mt-2 text-slate-600">'.htmlspecialchars($e->getMessage(), ENT_QUOTES|ENT_SUBSTITUTE, "UTF-8").'</p>';
    echo '<a class="inline-block mt-4 px-4 py-2 rounded-xl border" href="/deals/create.php'.($from_listing?('?from_listing='.(int)$from_listing):'').'">Вернуться</a>';
    echo '</div>';
    exit;
  }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Создать сделку</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<main class="mx-auto max-w-2xl p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>
  <h1 class="text-2xl font-semibold mt-2">Создать сделку</h1>

  <?php if (!empty($prefill_errors)): ?>
    <div class="mt-3 rounded-xl border bg-red-50 p-4">
      <?php foreach ($prefill_errors as $pe): ?>
        <div><?php echo htmlspecialchars($pe, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="mt-4 space-y-3" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <?php if ($from_listing > 0 && $prefill): ?>
      <input type="hidden" name="from_listing" value="<?php echo (int)$from_listing; ?>">
      <!-- Ник контрагента: только просмотр -->
      <div>
        <label class="block text-sm">Ник контрагента</label>
        <div class="w-full border rounded-xl px-3 py-2 bg-slate-100"><?php echo htmlspecialchars($default_other_nick, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></div>
        <input type="hidden" name="other_nick" value="<?php echo htmlspecialchars($default_other_nick, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>">
      </div>
      <div>
        <label class="block text-sm">Наименование товара</label>
        <input name="title" value="<?php echo htmlspecialchars($default_title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
      </div>
      <div>
        <label class="block text-sm">Ссылка на товар</label>
        <input name="product_url" type="url" value="<?php echo htmlspecialchars($default_url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
        <p class="text-xs text-slate-500 mt-1">Ссылка подтянута из объявления</p>
      </div>
      <div>
        <label class="block text-sm">Сумма кэшбэка, ₽</label>
        <input name="cashback" type="number" min="1" value="<?php echo (int)$default_cashback; ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
      </div>
      <div>
        <label class="block text-sm">Условия получения</label>
        <textarea name="terms" class="w-full border rounded-xl px-3 py-2" rows="5"><?php echo htmlspecialchars($default_terms, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
        <p class="text-xs text-slate-500 mt-1">Вы можете дополнить условия, если нужно.</p>
      </div>
    <?php else: ?>
      <!-- Обычное создание вручную -->
      <div><label class="block text-sm">Ник контрагента</label><input name="other_nick" required class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Наименование товара</label><input name="title" required class="w-full border rounded-xl px-3 py-2"></div>
      <div>
        <label class="block text-sm">Ссылка на товар</label>
        <input name="product_url" type="url" placeholder="https://..." required class="w-full border rounded-xl px-3 py-2">
        <p class="text-xs text-slate-500 mt-1">Укажите полноценную ссылку вида https://пример.ру/...</p>
      </div>
      <div><label class="block text-sm">Сумма кэшбэка, ₽</label><input name="cashback" type="number" min="1" required class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Условия получения</label><textarea name="terms" required class="w-full border rounded-xl px-3 py-2" rows="5"></textarea></div>
    <?php endif; ?>

    <button class="px-4 py-2 rounded-xl bg-black text-white" type="submit">Создать</button>
  </form>
</main>
</body></html>