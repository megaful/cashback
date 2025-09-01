<?php
require_once __DIR__.'/../includes/config.php';
$redir = __DIR__.'/../includes/redirect.php'; if (file_exists($redir)) require_once $redir;
$sys   = __DIR__.'/../includes/system_message.php'; if (file_exists($sys)) require_once $sys;
// Хелперы витрины (если есть)
$listings_lib = __DIR__.'/../includes/listings_lib.php'; if (file_exists($listings_lib)) require_once $listings_lib;

require_login();
$user = current_user();

/* ---------- безопасный esc ---------- */
if (!function_exists('esc')) {
  function esc($s){
    if (function_exists('e')) return e($s);
    if (function_exists('h')) return h($s);
    return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
  }
}

/* ---------- проверка URL ---------- */
function is_valid_http_url(string $url): bool {
  if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
  $parts = parse_url($url);
  if (!$parts) return false;
  if (!isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http','https'], true)) return false;
  if (empty($parts['host'])) return false;
  if (preg_match('~[\x00-\x1F\x7F\s]~u', $url)) return false;
  return true;
}

/* ---------- префилл из витрины ---------- */
$from_listing = (int)($_GET['from_listing'] ?? $_POST['from_listing'] ?? 0);
$prefill = null; $prefill_errors = [];

if ($from_listing > 0) {
  try {
    // Тянем объявление и логин продавца
    $st = $pdo->prepare("SELECT l.*, u.login AS seller_login
                         FROM listings l
                         JOIN users u ON u.id=l.seller_id
                         WHERE l.id=?");
    $st->execute([$from_listing]);
    $prefill = $st->fetch();

    if (!$prefill) {
      $prefill_errors[] = 'Объявление не найдено.';
      $prefill = null;
    } else {
      // Отклик — только для роли BUYER
      if (strtoupper($user['role'] ?? '') !== 'BUYER') {
        $prefill_errors[] = 'Отклик по объявлению доступен только пользователям со статусом «Покупатель».';
        $prefill = null;
      } else {
        // Проверяем доп. условия, если в либе есть соответствующие функции
        $isActive = (string)($prefill['status'] ?? '') === 'ACTIVE';
        $canApply = true;
        if (function_exists('listing_can_buyer_apply')) {
          $canApply = listing_can_buyer_apply($pdo, $prefill, (int)$user['id']);
        }
        if (!$isActive || !$canApply) {
          $prefill_errors[] = 'Нельзя откликнуться: объявление недоступно или слоты исчерпаны/вы уже откликались.';
          $prefill = null;
        }
      }
    }
  } catch (Throwable $e) {
    $prefill_errors[] = 'Не удалось получить данные объявления.';
    $prefill = null;
  }
}

/* ---------- значения по умолчанию для формы ---------- */
$default_other_nick = $prefill ? (string)($prefill['seller_login'] ?? '') : '';
$default_title      = $prefill ? (string)($prefill['title'] ?? '') : '';
$default_url        = $prefill ? (string)($prefill['product_url'] ?? '') : '';
$default_cashback   = $prefill ? (int)($prefill['cashback_rub'] ?? $prefill['cashback'] ?? 0) : 0;
$default_terms      = $prefill
  ? ("Покупка товара по объявлению #".$prefill['id']." на витрине.\nСледуйте условиям из описания объявления.\n")
  : "";

/* ---------- создание сделки ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (function_exists('check_csrf')) check_csrf();

    $other_nick  = trim($_POST['other_nick']  ?? '');
    $title       = trim($_POST['title']       ?? '');
    $product_url = trim($_POST['product_url'] ?? '');
    $cashback    = (int)($_POST['cashback']   ?? 0);
    $terms       = trim($_POST['terms']       ?? '');

    // Если префилл валиден — не доверяем полям формы
    if ($from_listing > 0 && $prefill) {
      $other_nick  = (string)($prefill['seller_login'] ?? '');
      $title       = (string)($prefill['title'] ?? '');
      $product_url = (string)($prefill['product_url'] ?? '');
      $cashback    = (int)($prefill['cashback_rub'] ?? $prefill['cashback'] ?? 0);
      if ($terms === '') $terms = $default_terms;
    }

    // Базовые проверки
    if ($other_nick === '' || $title === '' || $product_url === '' || $cashback < 1 || $terms === '') {
      throw new Exception('Заполните все поля корректно.');
    }
    if (!is_valid_http_url($product_url)) {
      throw new Exception('Поле «Ссылка на товар» должно содержать корректный http/https URL.');
    }

    // Контрагент
    $stmt = $pdo->prepare('SELECT id, role, login FROM users WHERE login = ? LIMIT 1');
    $stmt->execute([$other_nick]);
    $other = $stmt->fetch();
    if (!$other) throw new Exception('Пользователь с таким ником не найден.');

    if (($other['role'] ?? '') === ($user['role'] ?? '')) {
      throw new Exception('Сделки между одинаковыми ролями запрещены.');
    }

    $seller_id = (($user['role'] ?? '') === 'SELLER') ? (int)$user['id'] : (int)$other['id'];
    $buyer_id  = (($user['role'] ?? '') === 'BUYER')  ? (int)$user['id'] : (int)$other['id'];

    // Если префилл — проверим соответствие продавца
    if ($from_listing > 0 && $prefill && (int)$prefill['seller_id'] !== $seller_id) {
      throw new Exception('Контрагент не соответствует продавцу из объявления.');
    }

    // Комиссия сервиса
    $commission = defined('SERVICE_COMMISSION_RUB') ? (int)SERVICE_COMMISSION_RUB : 100;

    // Вставка сделки. Пробуем вариант с listing_id, при ошибке — без.
    $dealId = 0;
    try {
      $pdo->prepare('INSERT INTO deals
        (number, seller_id, buyer_id, created_by, title, product_url, cashback, commission, terms_text, listing_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "PENDING_ACCEPTANCE")')
        ->execute([
          'TMP', $seller_id, $buyer_id, (int)$user['id'],
          $title, $product_url, $cashback, $commission, $terms,
          ($prefill ? (int)$prefill['id'] : null)
        ]);
      $dealId = (int)$pdo->lastInsertId();
    } catch (PDOException $ex) {
      $code = $ex->getCode();
      $msg  = $ex->getMessage();
      if ($code === '42S22' || stripos($msg, 'Unknown column') !== false) {
        // Колонки listing_id нет — пишем без неё
        $pdo->prepare('INSERT INTO deals
          (number, seller_id, buyer_id, created_by, title, product_url, cashback, commission, terms_text, status)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "PENDING_ACCEPTANCE")')
          ->execute([
            'TMP', $seller_id, $buyer_id, (int)$user['id'],
            $title, $product_url, $cashback, $commission, $terms
          ]);
        $dealId = (int)$pdo->lastInsertId();
      } else {
        throw $ex;
      }
    }

    // Номер сделки
    $number = 'СДЕЛКА-'.str_pad((string)$dealId, 6, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE deals SET number = ? WHERE id = ?')->execute([$number, $dealId]);

    // Системное сообщение / уведомления — только если функции есть
    if (function_exists('safe_system_message')) {
      safe_system_message($pdo, $dealId, "Создана новая сделка {$number}. Вторая сторона должна принять условия.", (int)$user['id']);
    }
    if (function_exists('notify')) {
      $recipient = (($user['role'] ?? '') === 'SELLER') ? $buyer_id : $seller_id;
      notify($pdo, $recipient, "Новая сделка {$number}", "/deals/view.php?id=".$dealId);
    }

    // Если использовалось объявление — попробуем автоархив при исчерпании слотов
    if ($prefill && function_exists('listing_auto_archive_if_full')) {
      try { listing_auto_archive_if_full($pdo, (int)$prefill['id']); } catch(Throwable $e) {}
    }

    $okUrl = '/deals/success.php?id='.$dealId;
    if (function_exists('safe_redirect')) { safe_redirect($okUrl); }
    header('Location: '.$okUrl);
    exit;

  } catch (Throwable $e) {
    error_log('[deals/create] '.$e->getMessage());
    http_response_code(200);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.tailwindcss.com">';
    echo '<div class="max-w-xl mx-auto mt-10 p-6 bg-white border rounded-2xl">';
    echo '<h1 class="text-xl font-semibold">Не удалось создать сделку</h1>';
    echo '<p class="mt-2 text-slate-600">'.esc($e->getMessage()).'</p>';
    $back = '/deals/create.php'.($from_listing?('?from_listing='.(int)$from_listing):'');
    echo '<a class="inline-block mt-4 px-4 py-2 rounded-xl border" href="'.esc($back).'">Вернуться</a>';
    echo '</div>'; exit;
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Создать сделку — Cashback-Market</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{ --g1:#8A00FF; --g2:#005BFF; }
    body{
      background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),
                 linear-gradient(180deg,var(--g1),var(--g2)) fixed;
    }
    .card{border:1px solid #e6e8f0;border-radius:20px}
    .glass{background:rgba(255,255,255,.86);backdrop-filter:saturate(140%) blur(6px);}
    .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:8px 14px;background:#fff}
    .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF); color:#fff}
    .btn-grad:hover{filter:brightness(.95)}
  </style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-2xl mx-auto px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <section class="card glass p-4 md:p-6 mt-3">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-xl font-semibold">Создать сделку</h1>
        <p class="text-slate-600 mt-1">Укажите контрагента, условия и ссылку на товар.</p>
      </div>
      <?php if ($from_listing>0 && $prefill): ?>
        <div class="hidden sm:block chip">🛍️ Из объявления #<?= (int)$from_listing ?></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($prefill_errors)): ?>
      <div class="mt-3 rounded-xl border bg-rose-50 text-rose-900 p-4">
        <?php foreach ($prefill_errors as $pe): ?>
          <div><?= esc($pe) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-4 space-y-3" novalidate>
      <input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
      <?php if ($from_listing > 0 && $prefill): ?>
        <input type="hidden" name="from_listing" value="<?= (int)$from_listing ?>">

        <div>
          <label class="block text-sm">Ник контрагента</label>
          <div class="w-full border rounded-xl px-3 py-2 bg-slate-100"><?= esc($default_other_nick) ?></div>
          <input type="hidden" name="other_nick" value="<?= esc($default_other_nick) ?>">
        </div>

        <div>
          <label class="block text-sm">Наименование товара</label>
          <input name="title" value="<?= esc($default_title) ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
        </div>

        <div>
          <label class="block text-sm">Ссылка на товар</label>
          <input name="product_url" type="url" value="<?= esc($default_url) ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
          <p class="text-xs text-slate-500 mt-1">Ссылка подтянута из объявления</p>
        </div>

        <div>
          <label class="block text-sm">Сумма кэшбэка, ₽</label>
          <input name="cashback" type="number" min="1" value="<?= (int)$default_cashback ?>" readonly class="w-full border rounded-xl px-3 py-2 bg-slate-100">
        </div>

        <div>
          <label class="block text-sm">Условия получения</label>
          <textarea name="terms" class="w-full border rounded-xl px-3 py-2" rows="5"><?= esc($default_terms) ?></textarea>
          <p class="text-xs text-slate-500 mt-1">Вы можете дополнить условия, если нужно.</p>
        </div>

      <?php else: ?>
        <div>
          <label class="block text-sm">Ник контрагента</label>
          <input name="other_nick" required class="w-full border rounded-xl px-3 py-2">
        </div>

        <div>
          <label class="block text-sm">Наименование товара</label>
          <input name="title" required class="w-full border rounded-xl px-3 py-2">
        </div>

        <div>
          <label class="block text-sm">Ссылка на товар</label>
          <input name="product_url" type="url" placeholder="https://..." required class="w-full border rounded-xl px-3 py-2">
          <p class="text-xs text-slate-500 mt-1">Укажите полноценную ссылку вида https://пример.ру/...</p>
        </div>

        <div>
          <label class="block text-sm">Сумма кэшбэка, ₽</label>
          <input name="cashback" type="number" min="1" required class="w-full border rounded-xl px-3 py-2">
        </div>

        <div>
          <label class="block text-sm">Условия получения</label>
          <textarea name="terms" required class="w-full border rounded-xl px-3 py-2" rows="5"></textarea>
        </div>
      <?php endif; ?>

      <button class="px-4 py-2 rounded-full btn-grad" type="submit">Создать сделку</button>
    </form>
  </section>
</main>
</body>
</html>
