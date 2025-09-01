<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

/* --- безопасный экранирующий хелпер, без конфликта с e()/h() --- */
if (!function_exists('esc')) {
  function esc($s){
    if (function_exists('e')) return e($s);
    if (function_exists('h')) return h($s);
    return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
  }
}

/* --- утилита: есть ли таблица --- */
if (!function_exists('has_table')) {
  function has_table(PDO $pdo, string $table): bool {
    try {
      $q = $pdo->prepare("SELECT COUNT(*) c
                          FROM INFORMATION_SCHEMA.TABLES
                          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
      $q->execute([$table]);
      return (int)($q->fetch()['c'] ?? 0) > 0;
    } catch (Throwable $e) {
      return false;
    }
  }
}

/* --- получаем баланс (устойчиво) --- */
$balance = 0;
try {
  $st = $pdo->prepare('SELECT balance FROM balances WHERE user_id = ?');
  $st->execute([$user['id']]);
  $balance = (int)($st->fetch()['balance'] ?? 0);
} catch (Throwable $e) {
  $balance = 0; // не валим страницу
}

/* --- обработка формы --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('check_csrf')) { check_csrf(); }

  // читаем и валидируем сумму
  $amountRaw = trim($_POST['amount'] ?? '');
  $amount    = (int)preg_replace('~[^\d]~', '', $amountRaw);

  $err = '';
  if ($amount < 1)                  $err = 'Сумма должна быть положительным числом';
  elseif ($amount > max(0,$balance))$err = 'Недостаточно средств на балансе';

  if ($err) {
    if (function_exists('flash')) { flash('error', $err); }
    if (function_exists('redirect')) { redirect('/payouts/request.php'); }
    header('Location: /payouts/request.php'); exit;
  }

  try {
    $pdo->beginTransaction();

    // 1) Создаём заявку
    $ins = $pdo->prepare('INSERT INTO payout_requests (user_id, amount) VALUES (?,?)');
    $ins->execute([$user['id'], $amount]);

    // 2) Моментально «замораживаем» сумму — списываем с баланса
    $upd = $pdo->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?');
    $upd->execute([$amount, $user['id']]);

    // 3) Пишем движение кошелька, если есть таблица
    if (has_table($pdo, 'wallet_entries')) {
      $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo) VALUES (?, ?, "DEBIT", "Заявка на вывод (заморозка)")')
          ->execute([$user['id'], $amount]);
    }

    $pdo->commit();

    if (function_exists('flash')) { flash('ok', 'Заявка отправлена администратору'); }
    if (function_exists('redirect')) { redirect('/dashboard/index.php'); }
    header('Location: /dashboard/index.php'); exit;

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (function_exists('flash')) { flash('error', 'Ошибка при создании заявки: '.$e->getMessage()); }
    if (function_exists('redirect')) { redirect('/payouts/request.php'); }
    header('Location: /payouts/request.php'); exit;
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Заявка на вывод — Cashback-Market</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{ --g1:#8A00FF; --g2:#005BFF; }
    body{
      background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),
                 linear-gradient(180deg,var(--g1),var(--g2)) fixed;
    }
    .card{border:1px solid #e6e8f0;border-radius:20px}
    .glass{background:rgba(255,255,255,.86);backdrop-filter:saturate(140%) blur(6px);}
    .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
    .btn-grad:hover{filter:brightness(.95)}
  </style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-md mx-auto px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <?php if (function_exists('flash') && ($m = flash('error'))): ?>
    <div class="card p-3 mt-3 bg-rose-50 text-rose-900 border-rose-200"><?= esc($m) ?></div>
  <?php endif; ?>

  <section class="card glass p-4 md:p-6 mt-3">
    <h2 class="text-lg md:text-xl font-semibold">Вывод средств</h2>
    <p class="text-slate-600 mt-1">Доступно к выводу: <b>₽ <?= (int)$balance ?></b></p>

    <form method="post" class="mt-4 space-y-3" novalidate>
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
      <?php endif; ?>
      <div>
        <label class="block text-sm">Сумма, ₽</label>
        <input
          name="amount"
          type="number"
          min="1"
          max="<?= max(0,(int)$balance) ?>"
          step="1"
          required
          class="w-full border rounded-xl px-3 py-2"
          placeholder="Например, 1500"
        >
      </div>

      <button class="px-4 py-2 rounded-full btn-grad">Отправить заявку</button>
    </form>

    <p class="text-xs text-slate-500 mt-3">
      После отправки заявки средства резервируются (списываются с баланса) до обработки администратором.
    </p>
  </section>
</main>
</body>
</html>
