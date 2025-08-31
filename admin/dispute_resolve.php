<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM disputes WHERE id=?');
$st->execute([$id]);
$sp = $st->fetch();
if (!$sp) die('Спор не найден');

$dealSt = $pdo->prepare('SELECT * FROM deals WHERE id=?');
$dealSt->execute([$sp['deal_id']]);
$deal = $dealSt->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $resolution = $_POST['resolution'] ?? '';
  $comment = trim($_POST['admin_comment'] ?? '');

  if ($resolution==='ACCEPTED') {
    if ($deal['status']!=='ACCEPTED' && $deal['status']!=='RESOLVED_ACCEPTED') {
      $pdo->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')->execute([$deal['cashback'],$deal['buyer_id']]);
      $pdo->prepare('INSERT INTO wallet_entries (user_id, amount, direction, memo, deal_id) VALUES (?,?,?,?,?)')
          ->execute([$deal['buyer_id'],$deal['cashback'],'CREDIT','Кэшбэк по спору '.$deal['number'],$deal['id']]);
    }
    $pdo->prepare('UPDATE deals SET status="RESOLVED_ACCEPTED" WHERE id=?')->execute([$deal['id']]);
  } else {
    $pdo->prepare('UPDATE deals SET status="RESOLVED_REJECTED" WHERE id=?')->execute([$deal['id']]);
  }

  $pdo->prepare('UPDATE disputes SET status="RESOLVED", resolution=?, admin_comment=?, closed_at=NOW() WHERE id=?')
      ->execute([$resolution,$comment,$id]);

  redirect('/admin/index.php');
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Решение по спору</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50">
<main class="mx-auto max-w-xl p-6">
  <a href="/admin/index.php" class="text-sm">&larr; Назад</a>
  <div class="rounded-xl border bg-white p-4 mt-3">
    <h2 class="font-semibold">Спор #<?php echo (int)$sp['id']; ?> по сделке <?php echo (int)$sp['deal_id']; ?></h2>
    <form method="post" class="space-y-2 mt-3">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <div><label class="block text-sm">Решение</label>
        <select name="resolution" class="w-full border rounded-xl px-3 py-2">
          <option value="ACCEPTED">Удовлетворить (зачислить покупателю)</option>
          <option value="REJECTED">Отклонить</option>
        </select>
      </div>
      <div><label class="block text-sm">Комментарий администратора</label><textarea name="admin_comment" class="w-full border rounded-xl px-3 py-2" rows="4"></textarea></div>
      <button class="px-4 py-2 rounded-xl bg-black text-white">Сохранить решение</button>
    </form>
  </div>
</main>
</body></html>
