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

/* --- есть ли таблица/колонка --- */
if (!function_exists('has_table')) {
  function has_table(PDO $pdo, string $table): bool {
    try {
      $q = $pdo->prepare("SELECT COUNT(*) c
                          FROM INFORMATION_SCHEMA.TABLES
                          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
      $q->execute([$table]);
      return (int)($q->fetch()['c'] ?? 0) > 0;
    } catch (Throwable $e) { return false; }
  }
}
if (!function_exists('has_col')) {
  function has_col(PDO $pdo, string $table, string $col): bool {
    try {
      $q = $pdo->prepare("SELECT COUNT(*) c
                          FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
      $q->execute([$table,$col]);
      return (int)($q->fetch()['c'] ?? 0) > 0;
    } catch (Throwable $e) { return false; }
  }
}

/* --- локальная русификация статусов (если нет payout_status_ru) --- */
if (!function_exists('payout_status_ru_local')) {
  function payout_status_ru_local($s){
    if (function_exists('payout_status_ru')) return payout_status_ru($s);
    $map = [
      'PENDING'    => 'На рассмотрении',
      'PROCESSING' => 'В обработке',
      'PAID'       => 'Выплачено',
      'DONE'       => 'Выплачено',
      'REJECTED'   => 'Отклонено',
      'CANCELLED'  => 'Отменено',
      'CANCELED'   => 'Отменено',
      'FAILED'     => 'Ошибка выплаты',
    ];
    $key = strtoupper((string)$s);
    return $map[$key] ?? (string)$s;
  }
}

/* --- получаем список выплат устойчиво --- */
$rows = [];
$err  = '';
try {
  if (!has_table($pdo, 'payout_requests')) {
    $rows = [];
  } else {
    // Проверим наличие колонок, чтобы не падать на ORDER BY
    $hasCreated = has_col($pdo, 'payout_requests', 'created_at');
    $order = $hasCreated ? ' ORDER BY created_at DESC' : ' ORDER BY id DESC';
    $st = $pdo->prepare('SELECT * FROM payout_requests WHERE user_id=?'.$order);
    $st->execute([$user['id']]);
    $rows = $st->fetchAll() ?: [];
  }
} catch (Throwable $e) {
  $err = $e->getMessage();
}

/* --- баланс (для справки) --- */
$balance = 0;
try {
  $stb = $pdo->prepare('SELECT balance FROM balances WHERE user_id = ?');
  $stb->execute([$user['id']]);
  $balance = (int)($stb->fetch()['balance'] ?? 0);
} catch (Throwable $e) { $balance = 0; }

?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>История выводов — Cashback-Market</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{ --g1:#8A00FF; --g2:#005BFF; }
    body{
      background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),
                 linear-gradient(180deg,var(--g1),var(--g2)) fixed;
    }
    .card{border:1px solid #e6e8f0;border-radius:20px}
    .glass{background:rgba(255,255,255,.86);backdrop-filter:saturate(140%) blur(6px);}
    .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:6px 12px;background:#fff}
    .badge{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;border-radius:9999px;padding:4px 10px;font-size:12px}
    .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
    .btn-grad:hover{filter:brightness(.95)}
  </style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <?php if ($err): ?>
    <div class="card p-4 mt-3 bg-rose-50 border-rose-200 text-rose-900">
      Не удалось получить историю выплат: <?= esc($err) ?>
    </div>
  <?php endif; ?>

  <section class="card glass p-4 md:p-6 mt-3">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-xl font-semibold">История заявок на вывод</h1>
        <p class="text-slate-600 mt-1">Текущий баланс: <b>₽ <?= (int)$balance ?></b></p>
      </div>
      <div class="hidden sm:block">
        <a href="/payouts/request.php" class="chip">➕ Новая заявка</a>
      </div>
    </div>

    <div class="mt-4 space-y-3">
      <?php if (!$rows): ?>
        <div class="text-slate-600">Заявок ещё нет.</div>
      <?php else: ?>
        <?php foreach ($rows as $p):
          $pid      = (int)($p['id'] ?? 0);
          $amount   = (int)($p['amount'] ?? 0);
          $status   = (string)($p['status'] ?? 'PENDING');
          $created  = $p['created_at'] ?? ($p['created'] ?? '');
          $comment  = (string)($p['admin_comment'] ?? '');
          // Подбираем цвет бейджа
          $s = strtoupper($status);
          $cls = 'bg-slate-50 border-slate-200 text-slate-700';
          if (in_array($s,['PAID','DONE']))           $cls = 'bg-emerald-50 border-emerald-200 text-emerald-900';
          elseif (in_array($s,['REJECTED','FAILED'])) $cls = 'bg-rose-50 border-rose-200 text-rose-900';
          elseif (in_array($s,['PROCESSING']))        $cls = 'bg-amber-50 border-amber-200 text-amber-900';
          elseif (in_array($s,['PENDING']))           $cls = 'bg-indigo-50 border-indigo-200 text-indigo-900';
        ?>
          <div class="card bg-white p-4 md:p-5">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm text-slate-600 truncate">
                  #<?= $pid ?> · ₽ <?= $amount ?> <?= $created ? '· '.esc($created) : '' ?>
                </div>
                <div class="mt-1">
                  <span class="badge <?= $cls ?>"><?= esc(payout_status_ru_local($status)) ?></span>
                </div>
              </div>
              <div class="shrink-0">
                <?php if (in_array($s,['REJECTED','FAILED']) && $comment===''): ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($comment !== ''): ?>
              <div class="mt-3 text-sm text-slate-700">
                <div class="font-medium">Комментарий администратора</div>
                <div class="mt-1"><?= esc($comment) ?></div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="sm:hidden mt-4">
      <a href="/payouts/request.php" class="block w-full text-center rounded-full px-4 py-2 btn-grad">Новая заявка</a>
    </div>
  </section>
</main>
</body>
</html>
