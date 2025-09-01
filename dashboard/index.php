<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

/* статус блокировки */
$st = $pdo->prepare('SELECT is_blocked FROM users WHERE id=?');
$st->execute([$user['id']]);
$isBlocked = (int)($st->fetch()['is_blocked'] ?? 0) === 1;

/* вкладки */
$tab = $_GET['tab'] ?? 'active';
$activeStatuses   = ['PENDING_ACCEPTANCE','AWAITING_FUNDING','FUNDED','IN_PROGRESS','SUBMITTED'];
$successStatuses  = ['ACCEPTED','RESOLVED_ACCEPTED'];
$rejectedStatuses = ['REJECTED','RESOLVED_REJECTED'];
$disputeStatuses  = ['DISPUTE_OPENED'];

$counts = ['active'=>0,'success'=>0,'rejected'=>0,'dispute'=>0];
function count_for($pdo,$statuses,$uid){
  if (!$statuses) return 0;
  $in = implode(',', array_fill(0, count($statuses), '?'));
  $sql = "SELECT COUNT(*) c FROM deals WHERE status IN ($in) AND (seller_id=? OR buyer_id=?)";
  $st = $pdo->prepare($sql);
  $params = $statuses; array_push($params,$uid,$uid);
  $st->execute($params);
  return (int)($st->fetch()['c'] ?? 0);
}
$counts['active']  = count_for($pdo,$activeStatuses,$user['id']);
$counts['success'] = count_for($pdo,$successStatuses,$user['id']);
$counts['rejected']= count_for($pdo,$rejectedStatuses,$user['id']);
$counts['dispute'] = count_for($pdo,$disputeStatuses,$user['id']);

$statuses = $activeStatuses;
if ($tab === 'success')  $statuses = $successStatuses;
if ($tab === 'rejected') $statuses = $rejectedStatuses;
if ($tab === 'dispute')  $statuses = $disputeStatuses;

$in = implode(',', array_fill(0, count($statuses), '?'));
$params = $statuses; array_push($params,$user['id'],$user['id']);

$sql = "SELECT d.*, s.login AS seller_login, b.login AS buyer_login
        FROM deals d
        JOIN users s ON s.id=d.seller_id
        JOIN users b ON b.id=d.buyer_id
        WHERE d.status IN ($in) AND (d.seller_id=? OR d.buyer_id=?)
        ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$deals = $stmt->fetchAll();

/* баланс */
$bal = $pdo->prepare('SELECT balance FROM balances WHERE user_id=?');
$bal->execute([$user['id']]);
$balance = (int)($bal->fetch()['balance'] ?? 0);

/* русификация статусов */
if (!function_exists('status_ru')) {
  function status_ru($s){ return $s; }
}
function _status_ru_local($s){ return status_ru($s); }
?>
<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Личный кабинет — Cashback-Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      :root{
        --grad1:#8A00FF; --grad2:#005BFF;
      }
      body{background:linear-gradient(180deg,#f5ecff, #eef4ff 220px), linear-gradient(180deg,var(--grad1),var(--grad2)) fixed;}
      .glass{background:rgba(255,255,255,.86); backdrop-filter:saturate(140%) blur(6px);}
      .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:8px 14px;background:#fff}
      .chip.active{background:linear-gradient(90deg, #8A00FF, #005BFF); color:#fff; border-color:transparent}
      .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF); color:#fff}
      .btn-grad:hover{filter:brightness(.95)}
      /* чипы не «наезжают» на мобиле */
      .chips{display:flex;flex-wrap:wrap;gap:10px}
      /* карточки */
      .card{border:1px solid #e6e8f0;border-radius:20px}
      /* моб-адапт */
      @media (max-width: 480px){
        h1{font-size:24px}
        .tiles{grid-template-columns:1fr !important}
      }
    </style>
  </head>
  <body class="text-slate-900">
    <?php include __DIR__.'/../includes/topbar.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-5 md:py-8">
      <!-- приветствие -->
      <section class="card glass px-4 py-4 md:px-6 md:py-6 mb-4 md:mb-6">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-sm text-slate-500">Добро пожаловать, <b><?= htmlspecialchars($user['login']??'') ?></b></div>
            <h1 class="mt-1 text-2xl md:text-3xl font-bold">Личный кабинет</h1>
            <p class="mt-1 text-slate-600">Управляйте сделками, балансом и объявлениями в одном месте.</p>
          </div>
          <div class="hidden sm:flex gap-2 flex-wrap justify-end">
            <a href="/deals/create.php" class="chip">
              <span>➕</span><span class="font-medium">Создать сделку</span>
            </a>
            <?php if (($user['role'] ?? '') === 'SELLER'): ?>
              <a href="/seller/listings/create.php" class="chip">
                <span>🛍️</span><span class="font-medium">Выставить товар</span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <?php if ($isBlocked): ?>
        <div class="card glass p-4 md:p-5 border-red-200 bg-red-50/80 text-red-800">
          <b>Ваш аккаунт заблокирован.</b> Обратитесь к администратору.
        </div>
      <?php else: ?>

      <div class="grid tiles gap-4 md:gap-6 md:grid-cols-3">
        <!-- сделки -->
        <section class="card glass md:col-span-2 p-4 md:p-6">
          <h2 class="text-lg md:text-xl font-semibold mb-3">Мои сделки</h2>

          <div class="chips mb-3">
            <a class="chip <?= $tab==='active'?'active':'' ?>" href="/dashboard/index.php?tab=active">⚡ Активные (<?= (int)$counts['active'] ?>)</a>
            <a class="chip <?= $tab==='success'?'active':'' ?>" href="/dashboard/index.php?tab=success">✅ Успешные (<?= (int)$counts['success'] ?>)</a>
            <a class="chip <?= $tab==='rejected'?'active':'' ?>" href="/dashboard/index.php?tab=rejected">❌ Отклонённые (<?= (int)$counts['rejected'] ?>)</a>
            <a class="chip <?= $tab==='dispute'?'active':'' ?>" href="/dashboard/index.php?tab=dispute">🧑‍⚖️ Арбитраж (<?= (int)$counts['dispute'] ?>)</a>
          </div>

          <div class="space-y-3">
            <?php foreach ($deals as $d): ?>
              <?php $counter = ($d['seller_id']==$user['id']) ? $d['buyer_login'] : $d['seller_login']; ?>
              <div class="card bg-white p-4 md:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-sm text-slate-600 truncate">
                    #<?= htmlspecialchars($d['number']) ?> · <?= htmlspecialchars(_status_ru_local($d['status'])) ?> ·
                    Контрагент: <b><?= htmlspecialchars($counter) ?></b>
                  </div>
                  <div class="font-medium mt-1 truncate"><?= htmlspecialchars($d['title']) ?></div>
                  <?php if ($d['status']==='AWAITING_FUNDING'): ?>
                    <span class="mt-2 inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs bg-amber-50 border-amber-200 text-amber-900">Ожидает оплаты</span>
                  <?php endif; ?>
                </div>
                <div class="shrink-0">
                  <a class="inline-flex items-center gap-2 rounded-full px-4 py-2 btn-grad" href="/deals/view.php?id=<?= (int)$d['id'] ?>">↗ Открыть</a>
                </div>
              </div>
            <?php endforeach; if (!$deals): ?>
              <div class="text-slate-600">Нет сделок в этой категории.</div>
            <?php endif; ?>
          </div>
        </section>

        <!-- баланс / инструменты -->
        <aside class="card glass p-4 md:p-6">
          <div class="flex items-start justify-between">
            <h3 class="text-lg font-semibold">Баланс</h3>
            <span class="text-slate-400">🧾</span>
          </div>
          <div class="text-3xl mt-1">₽ <?= (int)$balance ?></div>

          <div class="mt-3 flex flex-wrap gap-2">
            <a href="/payouts/request.php" class="inline-flex items-center gap-2 rounded-full px-4 py-2 btn-grad">📨 Вывести</a>
            <a href="/payouts/history.php" class="inline-flex items-center gap-2 rounded-full px-4 py-2 chip">🕘 История</a>
          </div>

          <?php if (($user['role'] ?? '') === 'SELLER'): ?>
            <div class="mt-5 pt-4 border-t">
              <div class="text-sm text-slate-600 mb-2">Инструменты продавца</div>
              <div class="flex flex-col gap-2">
                <a href="/seller/listings/index.php" class="chip w-full justify-between"><span>📋 Мои объявления</span> ➜</a>
                <a href="/seller/listings/create.php" class="chip w-full justify-between"><span>🛍️ Выставить товар</span> ➜</a>
              </div>
            </div>
          <?php endif; ?>
        </aside>
      </div>

      <?php endif; ?>
    </main>
    <script src="/assets/js/topbar.js"></script>
  </body>
</html>
