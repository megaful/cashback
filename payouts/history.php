<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

$rows = $pdo->prepare('SELECT * FROM payout_requests WHERE user_id=? ORDER BY created_at DESC');
$rows->execute([$user['id']]);
$list = $rows->fetchAll();

function _payout_ru_local($s){ return function_exists('payout_status_ru') ? payout_status_ru($s) : $s; }
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>История выводов</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50">
<main class="mx-auto max-w-3xl p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>
  <div class="rounded-xl border bg-white p-4 mt-3">
    <h2 class="font-semibold">История заявок на вывод</h2>
    <div class="mt-3 space-y-2">
      <?php foreach ($list as $p): ?>
        <div class="border rounded p-3">
          <div>#<?php echo (int)$p['id']; ?> · ₽ <?php echo (int)$p['amount']; ?> · <?php echo e(_payout_ru_local($p['status'])); ?> · <?php echo e($p['created_at']); ?></div>
          <?php if (!empty($p['admin_comment'])): ?><div class="text-sm text-slate-600 mt-1">Комментарий администратора: <?php echo e($p['admin_comment']); ?></div><?php endif; ?>
        </div>
      <?php endforeach; if (!$list): ?>
        <div class="text-slate-600">Заявок нет.</div>
      <?php endif; ?>
    </div>
  </div>
</main>
</body></html>
