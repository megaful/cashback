<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/redirect.php';
require_login();
$dealId = (int)($_GET['id'] ?? 0);
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Сделка создана</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<main class="mx-auto max-w-xl p-6 text-center">
  <div class="rounded-2xl border bg-white p-8">
    <h1 class="text-2xl font-semibold">Ваша сделка успешно создана</h1>
    <p class="mt-2 text-slate-600">Ожидайте подтверждения второй стороны.</p>
    <div class="mt-6 flex gap-3 justify-center">
      <?php if ($dealId): ?>
        <a class="px-4 py-2 rounded-xl bg-black text-white" href="/deals/view.php?id=<?php echo (int)$dealId; ?>">Открыть сделку</a>
      <?php endif; ?>
      <a class="px-4 py-2 rounded-xl border" href="/dashboard/index.php">Вернуться в личный кабинет</a>
    </div>
  </div>
</main>
</body></html>
