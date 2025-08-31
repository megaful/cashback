<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

$st = $pdo->prepare('SELECT * FROM profiles WHERE user_id = ?');
$st->execute([$user['id']]);
$profile = $st->fetch();

$st = $pdo->prepare('SELECT * FROM requisites WHERE user_id = ?');
$st->execute([$user['id']]);
$req = $st->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  if (isset($_POST['save_profile'])) {
    $display = trim($_POST['display_name'] ?? '');
    $tg = trim($_POST['telegram'] ?? '');
    $pdo->prepare('UPDATE profiles SET display_name=?, telegram=? WHERE user_id=?')
        ->execute([$display,$tg,$user['id']]);
  }
  if (isset($_POST['save_requisites'])) {
    $phone = trim($_POST['sbp_phone'] ?? '');
    $bank = trim($_POST['sbp_bank'] ?? '');
    $full = trim($_POST['full_name'] ?? '');
    if ($req) {
      $updatedAt = strtotime($req['updated_at']);
      if (time() - $updatedAt < 3*24*3600) {
        flash('error','Реквизиты можно менять не чаще, чем раз в 3 дня');
        redirect('/profile/index.php');
      }
      $pdo->prepare('UPDATE requisites SET sbp_phone=?, sbp_bank=?, full_name=?, updated_at=NOW() WHERE user_id=?')
          ->execute([$phone,$bank,$full,$user['id']]);
    } else {
      $pdo->prepare('INSERT INTO requisites (user_id, sbp_phone, sbp_bank, full_name) VALUES (?,?,?,?)')
          ->execute([$user['id'],$phone,$bank,$full]);
    }
  }
  flash('ok','Сохранено');
  redirect('/profile/index.php');
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Профиль</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<main class="mx-auto max-w-3xl p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>
  <?php if ($m = flash('ok')): ?><div class="mt-3 p-3 bg-green-50 border rounded"><?php echo e($m); ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="mt-3 p-3 bg-red-50 border rounded"><?php echo e($m); ?></div><?php endif; ?>
  <div class="rounded-xl border bg-white p-4">
    <h2 class="font-semibold">Профиль</h2>
    <div class="text-sm text-slate-600">Ваш ник: <b><?php echo e($user['login']); ?></b></div>
    <form method="post" class="space-y-2 mt-3">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="save_profile" value="1">
      <div><label class="block text-sm">Отображаемое имя</label><input name="display_name" value="<?php echo e($profile['display_name'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Telegram</label><input name="telegram" value="<?php echo e($profile['telegram'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <button class="px-4 py-2 rounded-xl bg-black text-white">Сохранить</button>
    </form>
  </div>

  <div class="rounded-xl border bg-white p-4 mt-4">
    <h2 class="font-semibold">Реквизиты СБП</h2>
    <form method="post" class="space-y-2 mt-3">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="save_requisites" value="1">
      <div><label class="block text-sm">Телефон (СБП)</label><input name="sbp_phone" value="<?php echo e($req['sbp_phone'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Банк получателя</label><input name="sbp_bank" value="<?php echo e($req['sbp_bank'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">ФИО получателя</label><input name="full_name" value="<?php echo e($req['full_name'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <button class="px-4 py-2 rounded-xl bg-black text-white">Сохранить</button>
    </form>
  </div>
</main>
</body></html>
