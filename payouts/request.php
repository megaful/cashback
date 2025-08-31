<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

$bal = $pdo->prepare('SELECT balance FROM balances WHERE user_id = ?');
$bal->execute([$user['id']]);
$balance = (int)($bal->fetch()['balance'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $amount = (int)($_POST['amount'] ?? 0);
  if ($amount < 1 || $amount > $balance) {
    flash('error','Некорректная сумма');
    redirect('/payouts/request.php');
  }

  // 1) Создаем заявку
  $pdo->prepare('INSERT INTO payout_requests (user_id,amount) VALUES (?,?)')->execute([$user['id'],$amount]);

  // 2) Моментально «замораживаем» сумму: списываем с баланса
  $pdo->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?')->execute([$amount, $user['id']]);

  // 3) Пишем движение кошелька (DEBIT)
  $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo) VALUES (?, ?, "DEBIT", "Заявка на вывод (заморозка)")')
      ->execute([$user['id'], $amount]);

  flash('ok','Заявка отправлена администратору');
  redirect('/dashboard/index.php');
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Заявка на вывод</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<main class="mx-auto max-w-md p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>
  <?php if ($m = flash('error')): ?><div class="mt-3 p-3 bg-red-50 border rounded"><?php echo e($m); ?></div><?php endif; ?>
  <div class="rounded-xl border bg-white p-4 mt-3">
    <h2 class="font-semibold">Вывод средств</h2>
    <p class="text-slate-600">Баланс: ₽ <?php echo (int)$balance; ?></p>
    <form method="post" class="mt-3 space-y-2">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <div><label class="block text-sm">Сумма, ₽</label><input name="amount" type="number" min="1" max="<?php echo (int)$balance; ?>" required class="w-full border rounded-xl px-3 py-2"></div>
      <button class="px-4 py-2 rounded-xl bg-black text-white">Отправить</button>
    </form>
  </div>
</main>
</body></html>
