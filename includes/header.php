<?php
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($page_title) ? e($page_title) : 'CashBack-Market'; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="/assets/favicon.png">
  <link rel="shortcut icon" href="/favicon.ico">
  <script>window.CSRF_TOKEN = "<?php echo e(csrf_token()); ?>";</script>
</head>
<body class="bg-slate-50">
<?php
  // Флаг: скрыть колокольчик и выпадающее меню полностью (например, на /notifications/index.php)
  $HIDE_NOTIF = !empty($HIDE_NOTIF);

  $initialCount = 0;
  try {
    if (function_exists('current_user')) {
      $u = current_user();
      if ($u) {
        $st = $pdo->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)');
        $st->execute([$u['id']]);
        $r = $st->fetch();
        $initialCount = (int)($r ? $r['c'] : 0);
      }
    }
  } catch (Throwable $e) { $initialCount = 0; }
?>
<header class="bg-white border-b">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between" id="topbar">
    <a href="/" class="font-bold">CashBack-Market</a>

    <?php if (!$HIDE_NOTIF): ?>
      <div class="relative">
        <button id="notifBtn" type="button" class="relative inline-flex items-center gap-2 px-3 py-2 rounded-xl border">
          <span>🔔</span>
          <span id="notifBadge"
                data-count="<?php echo $initialCount; ?>"
                class="<?php echo $initialCount>0?'inline-flex':'hidden'; ?> min-w-[20px] h-[20px] px-1 text-xs items-center justify-center rounded-full bg-red-600 text-white">
            <?php echo $initialCount>0?$initialCount:''; ?>
          </span>
        </button>
        <div id="notifDropdown" class="absolute right-0 mt-2 w-72 bg-white border rounded-2xl shadow-lg p-2 hidden">
          <div class="flex items-center justify-between px-2 py-1">
            <div class="text-sm font-semibold">Уведомления</div>
            <div class="text-xs space-x-3">
              <button id="notifMarkAll" class="underline">Пометить все</button>
              <a href="/notifications/diag.php" class="text-slate-400 hover:text-slate-600">диагностика</a>
            </div>
          </div>
          <div id="notifList" class="max-h-80 overflow-auto"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</header>

<?php if (!$HIDE_NOTIF): ?>
<script>
(function(){
  const btn = document.getElementById('notifBtn');
  const dd  = document.getElementById('notifDropdown');
  if (btn && dd) {
    btn.addEventListener('click', ()=> dd.classList.toggle('hidden'));
    document.addEventListener('click', (e)=> {
      if (!dd.contains(e.target) && !btn.contains(e.target)) dd.classList.add('hidden');
    });
  }
})();
</script>
<script src="/assets/js/notifications.js"></script>
<?php endif; ?>
