<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

/* --- универсальный экранирующий хелпер: не конфликтует с твоими e()/h() --- */
if (!function_exists('esc')) {
  function esc($s){
    if (function_exists('e')) return e($s);
    if (function_exists('h')) return h($s);
    return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
  }
}

/* --- загрузка профиля и реквизитов (устойчиво) --- */
try {
  $st = $pdo->prepare('SELECT * FROM profiles WHERE user_id = ?');
  $st->execute([$user['id']]);
  $profile = $st->fetch() ?: [];

  $st = $pdo->prepare('SELECT * FROM requisites WHERE user_id = ?');
  $st->execute([$user['id']]);
  $req = $st->fetch() ?: [];
} catch (Throwable $e) {
  $profile = [];
  $req = [];
}

/* --- POST обработка --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('check_csrf')) { check_csrf(); }

  try {
    // Сохранить профиль
    if (isset($_POST['save_profile'])) {
      $display = trim($_POST['display_name'] ?? '');
      $tg      = trim($_POST['telegram'] ?? '');

      if ($profile) {
        $pdo->prepare('UPDATE profiles SET display_name=?, telegram=? WHERE user_id=?')
            ->execute([$display, $tg, $user['id']]);
      } else {
        $pdo->prepare('INSERT INTO profiles (user_id, display_name, telegram) VALUES (?,?,?)')
            ->execute([$user['id'], $display, $tg]);
      }
    }

    // Сохранить реквизиты СБП
    if (isset($_POST['save_requisites'])) {
      $phone = trim($_POST['sbp_phone'] ?? '');
      $bank  = trim($_POST['sbp_bank'] ?? '');
      $full  = trim($_POST['full_name'] ?? '');

      if ($req) {
        // Проверка частоты изменений (3 дня), устойчивая к пустому updated_at
        $updatedAt = 0;
        if (!empty($req['updated_at'])) {
          $ts = strtotime((string)$req['updated_at']);
          if ($ts !== false) $updatedAt = (int)$ts;
        }
        if (time() - $updatedAt < 3*24*3600) {
          if (function_exists('flash')) flash('error','Реквизиты можно менять не чаще, чем раз в 3 дня');
          if (function_exists('redirect')) { redirect('/profile/index.php'); }
          header('Location: /profile/index.php'); exit;
        }

        $pdo->prepare('UPDATE requisites SET sbp_phone=?, sbp_bank=?, full_name=?, updated_at=NOW() WHERE user_id=?')
            ->execute([$phone, $bank, $full, $user['id']]);
      } else {
        $pdo->prepare('INSERT INTO requisites (user_id, sbp_phone, sbp_bank, full_name, updated_at) VALUES (?,?,?,?,NOW())')
            ->execute([$user['id'], $phone, $bank, $full]);
      }
    }

    if (function_exists('flash')) flash('ok','Сохранено успешно');
  } catch (Throwable $e) {
    if (function_exists('flash')) { flash('error','Ошибка: '.$e->getMessage()); }
  }

  if (function_exists('redirect')) { redirect('/profile/index.php'); }
  header('Location: /profile/index.php'); exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Профиль — Cashback-Market</title>
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

<main class="max-w-3xl mx-auto px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <?php if (function_exists('flash') && ($m = flash('ok'))): ?>
    <div class="card p-3 mt-3 bg-emerald-50 text-emerald-900 border-emerald-200"><?= esc($m) ?></div>
  <?php endif; ?>
  <?php if (function_exists('flash') && ($m = flash('error'))): ?>
    <div class="card p-3 mt-3 bg-rose-50 text-rose-900 border-rose-200"><?= esc($m) ?></div>
  <?php endif; ?>

  <!-- Профиль -->
  <section class="card glass p-4 md:p-6 mt-3">
    <h2 class="text-lg md:text-xl font-semibold">Профиль</h2>
    <div class="text-sm text-slate-600 mt-1">Ваш ник: <b><?= esc($user['login'] ?? '') ?></b></div>

    <form method="post" class="space-y-3 mt-4">
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="save_profile" value="1">

      <div>
        <label class="block text-sm">Отображаемое имя</label>
        <input name="display_name" value="<?= esc($profile['display_name'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2">
      </div>

      <div>
        <label class="block text-sm">Telegram</label>
        <input name="telegram" value="<?= esc($profile['telegram'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2" placeholder="@username">
      </div>

      <button class="px-4 py-2 rounded-full btn-grad">Сохранить</button>
    </form>
  </section>

  <!-- Реквизиты -->
  <section class="card glass p-4 md:p-6 mt-4">
    <h2 class="text-lg md:text-xl font-semibold">Реквизиты СБП</h2>
    <p class="text-xs text-slate-500 mt-1">Для вывода средств. Менять можно не чаще, чем раз в 3 дня.</p>

    <form method="post" class="space-y-3 mt-4">
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="save_requisites" value="1">

      <div>
        <label class="block text-sm">Телефон (СБП)</label>
        <input name="sbp_phone" value="<?= esc($req['sbp_phone'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2" placeholder="+7XXXXXXXXXX">
      </div>

      <div>
        <label class="block text-sm">Банк получателя</label>
        <input name="sbp_bank" value="<?= esc($req['sbp_bank'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2" placeholder="Название банка">
      </div>

      <div>
        <label class="block text-sm">ФИО получателя</label>
        <input name="full_name" value="<?= esc($req['full_name'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2">
      </div>

      <button class="px-4 py-2 rounded-full btn-grad">Сохранить</button>
    </form>
  </section>
</main>
</body>
</html>
