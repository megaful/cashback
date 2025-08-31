<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare('SELECT id,login,email,role,is_blocked FROM users WHERE id=?');
$st->execute([$id]);
$u = $st->fetch();
if (!$u) die('Пользователь не найден');

$req = $pdo->prepare('SELECT * FROM requisites WHERE user_id=?');
$req->execute([$id]);
$requisites = $req->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();

  $login = trim($_POST['login'] ?? $u['login']);
  $email = trim($_POST['email'] ?? $u['email']);
  $role = $_POST['role'] ?? $u['role'];
  $blocked = isset($_POST['is_blocked']) ? 1 : 0;

  $pdo->prepare('UPDATE users SET login=?, email=?, role=?, is_blocked=? WHERE id=?')
      ->execute([$login,$email,$role,$blocked,$id]);

  if (!empty($_POST['new_password'])) {
    $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET pass_hash=? WHERE id=?')->execute([$hash,$id]);
  }

  $sbp_phone = trim($_POST['sbp_phone'] ?? '');
  $sbp_bank  = trim($_POST['sbp_bank'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');

  if ($requisites) {
    $pdo->prepare('UPDATE requisites SET sbp_phone=?, sbp_bank=?, full_name=?, updated_at=NOW() WHERE user_id=?')
        ->execute([$sbp_phone,$sbp_bank,$full_name,$id]);
  } else {
    $pdo->prepare('INSERT INTO requisites (user_id, sbp_phone, sbp_bank, full_name) VALUES (?,?,?,?)')
        ->execute([$id,$sbp_phone,$sbp_bank,$full_name]);
  }

  flash('ok','Сохранено');
  redirect('/admin/user_edit.php?id='.$id);
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Правка пользователя</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50">
<main class="mx-auto max-w-xl p-6">
  <a href="/admin/index.php" class="text-sm">&larr; Назад</a>
  <?php if ($m=flash('ok')): ?><div class="mt-3 p-3 bg-green-50 border rounded"><?php echo e($m); ?></div><?php endif; ?>
  <div class="rounded-xl border bg-white p-4 mt-3">
    <h2 class="font-semibold">Правка пользователя #<?php echo (int)$u['id']; ?></h2>
    <form method="post" class="space-y-2 mt-3">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <div><label class="block text-sm">Ник (login)</label><input name="login" value="<?php echo e($u['login']); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Email</label><input name="email" value="<?php echo e($u['email']); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Роль</label>
        <select name="role" class="w-full border rounded-xl px-3 py-2">
          <?php foreach (['SELLER'=>'Продавец','BUYER'=>'Покупатель','ADMIN'=>'Администратор'] as $val=>$lbl): ?>
            <option value="<?php echo $val; ?>" <?php if($u['role']===$val) echo 'selected'; ?>><?php echo $lbl; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label><input type="checkbox" name="is_blocked" <?php if($u['is_blocked']) echo 'checked'; ?>> Заблокирован</label></div>
      <div><label class="block text-sm">Новый пароль (опционально)</label><input type="password" name="new_password" class="w-full border rounded-xl px-3 py-2"></div>

      <h3 class="font-semibold mt-4">Реквизиты СБП</h3>
      <div><label class="block text-sm">Телефон (СБП)</label><input name="sbp_phone" value="<?php echo e($requisites['sbp_phone'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">Банк получателя</label><input name="sbp_bank" value="<?php echo e($requisites['sbp_bank'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>
      <div><label class="block text-sm">ФИО получателя</label><input name="full_name" value="<?php echo e($requisites['full_name'] ?? ''); ?>" class="w-full border rounded-xl px-3 py-2"></div>

      <button class="px-4 py-2 rounded-xl bg-black text-white">Сохранить</button>
    </form>
  </div>
</main>
</body></html>
