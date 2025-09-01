<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();

// Для системного сообщения (если файл существует)
$sys = __DIR__.'/../includes/system_message.php';
if (file_exists($sys)) require_once $sys;

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM disputes WHERE id=?');
$st->execute([$id]);
$sp = $st->fetch();
if (!$sp) die('Спор не найден');

$dealSt = $pdo->prepare('SELECT * FROM deals WHERE id=?');
$dealSt->execute([$sp['deal_id']]);
$deal = $dealSt->fetch();
if (!$deal) die('Сделка не найдена');

function ensure_balance_row(PDO $pdo, int $uid){
  $pdo->prepare("INSERT INTO balances (user_id, balance) VALUES (?,0) ON DUPLICATE KEY UPDATE balance=balance")->execute([$uid]);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $resolution = $_POST['resolution'] ?? '';
  $comment = trim($_POST['admin_comment'] ?? '');

  $cashback = (int)$deal['cashback'];
  $buyerId  = (int)$deal['buyer_id'];
  $sellerId = (int)$deal['seller_id'];

  // Денежные движения + статус сделки
  if ($resolution==='ACCEPTED') {
    // Удовлетворить спор: кэшбэк покупателю (если ранее не зачисляли)
    if ($deal['status']!=='ACCEPTED' && $deal['status']!=='RESOLVED_ACCEPTED') {
      ensure_balance_row($pdo,$buyerId);
      $pdo->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')->execute([$cashback,$buyerId]);
      if ($pdo->query("SHOW TABLES LIKE 'wallet_entries'")->fetch()) {
        $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo, deal_id) VALUES (?,?,?,?,?)')
            ->execute([$buyerId,$cashback,'CREDIT','Кэшбэк по спору '.$deal['number'],$deal['id']]);
      }
    }
    $pdo->prepare('UPDATE deals SET status="RESOLVED_ACCEPTED" WHERE id=?')->execute([$deal['id']]);
  } else {
    // Отклонить спор: возврат продавцу
    ensure_balance_row($pdo,$sellerId);
    $pdo->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')->execute([$cashback,$sellerId]);
    if ($pdo->query("SHOW TABLES LIKE 'wallet_entries'")->fetch()) {
      $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo, deal_id) VALUES (?,?,?,?,?)')
          ->execute([$sellerId,$cashback,'CREDIT','Возврат по спору '.$deal['number'],$deal['id']]);
    }
    $pdo->prepare('UPDATE deals SET status="RESOLVED_REJECTED" WHERE id=?')->execute([$deal['id']]);
  }

  // Закрываем спор
  $pdo->prepare('UPDATE disputes SET status="RESOLVED", resolution=?, admin_comment=?, closed_at=NOW() WHERE id=?')
      ->execute([$resolution,$comment,$id]);

  // ===== Сообщение в чат сделки + уведомления =====
  $adminUser = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
  $adminId   = (int)($adminUser['id'] ?? 0);

  // Текст статуса
  $resText = ($resolution === 'ACCEPTED')
    ? 'Удовлетворить (зачислить покупателю)'
    : 'Отклонить (возврат продавцу)';

  $chatMsg = "Администратор вынес решение по арбитражу: {$resText}.";
  if ($comment !== '') {
    $chatMsg .= "\nКомментарий администратора: ".$comment;
  }

  // Запись в чат (если доступна helper-функция)
  if (function_exists('safe_system_message')) {
    try { safe_system_message($pdo, (int)$deal['id'], $chatMsg, $adminId); } catch (Throwable $e) {}
  }

  // Уведомления сторонам (если функция есть)
  if (function_exists('notify')) {
    try {
      notify($pdo, $buyerId, "Решение по спору {$deal['number']}", "/deals/view.php?id=".$deal['id']."#chat");
      notify($pdo, $sellerId, "Решение по спору {$deal['number']}", "/deals/view.php?id=".$deal['id']."#chat");
    } catch (Throwable $e) {}
  }

  redirect('/admin/index.php?tab=dispute');
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Решение по спору</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50">
<main class="mx-auto max-w-xl p-6">
  <a href="/admin/index.php?tab=dispute" class="text-sm">&larr; Назад</a>
  <div class="rounded-xl border bg-white p-4 mt-3">
    <h2 class="font-semibold">Спор #<?php echo (int)$sp['id']; ?> по сделке <?php echo e($deal['number']); ?></h2>
    <div class="text-sm text-slate-600 mt-1">Кэшбэк по сделке: ₽ <?php echo (int)$deal['cashback']; ?></div>
    <form method="post" class="space-y-2 mt-3">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <div>
        <label class="block text-sm">Решение</label>
        <select name="resolution" class="w-full border rounded-xl px-3 py-2">
          <option value="ACCEPTED">Удовлетворить (зачислить покупателю)</option>
          <option value="REJECTED">Отклонить (возврат продавцу)</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Комментарий администратора</label>
        <textarea name="admin_comment" class="w-full border rounded-xl px-3 py-2" rows="4" placeholder="Краткий комментарий…"></textarea>
      </div>
      <button class="px-4 py-2 rounded-xl bg-black text-white">Сохранить решение</button>
    </form>
  </div>
</main>
</body></html>
