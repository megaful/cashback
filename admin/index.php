<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();

$tab = $_GET['tab'] ?? 'active';

$active   = ['PENDING_ACCEPTANCE','AWAITING_FUNDING','FUNDED','IN_PROGRESS','SUBMITTED'];
$success  = ['ACCEPTED','RESOLVED_ACCEPTED'];
$rejected = ['REJECTED','RESOLVED_REJECTED'];
$dispute  = ['DISPUTE_OPENED'];

function count_global($pdo,$statuses){
  if (empty($statuses)) return 0;
  $in = implode(',', array_fill(0, count($statuses), '?'));
  $st = $pdo->prepare("SELECT COUNT(*) c FROM deals WHERE status IN ($in)");
  $st->execute($statuses);
  $r = $st->fetch();
  return (int)($r['c'] ?? 0);
}
$counts = [
  'active'  => count_global($pdo,$active),
  'success' => count_global($pdo,$success),
  'rejected'=> count_global($pdo,$rejected),
  'dispute' => count_global($pdo,$dispute),
];

function _status_ru_local($s){ return function_exists('status_ru') ? status_ru($s) : $s; }
function _payout_ru_local($s){ return function_exists('payout_status_ru') ? payout_status_ru($s) : $s; }
function _role_ru_local($s){ return function_exists('role_ru') ? role_ru($s) : $s; }

/** Загрузка списка для текущей вкладки */
if ($tab === 'dispute') {
  // Для вкладки "Арбитраж" показываем именно споры (join с сделками)
  $sql = "SELECT sp.id   AS dispute_id,
                 sp.status AS dispute_status,
                 sp.created_at AS dispute_created,
                 d.id, d.number, d.title, d.status, d.created_at
          FROM disputes sp
          JOIN deals d ON d.id = sp.deal_id
          WHERE d.status IN ('DISPUTE_OPENED')
          ORDER BY sp.created_at DESC
          LIMIT 200";
  $stmt = $pdo->query($sql);
  $disputes = $stmt->fetchAll();
  $deals = []; // не используется в этой вкладке
} else {
  $statuses = $active;
  if ($tab === 'success')  $statuses = $success;
  if ($tab === 'rejected') $statuses = $rejected;
  $in = implode(',', array_fill(0, count($statuses), '?'));
  $sql = "SELECT id,number,title,status,created_at FROM deals WHERE status IN ($in) ORDER BY created_at DESC LIMIT 200";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($statuses);
  $deals = $stmt->fetchAll();
  $disputes = [];
}

$payouts = $pdo->query('SELECT p.*, u.login FROM payout_requests p JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 200')->fetchAll();
$users = $pdo->query('SELECT id,login,email,role,is_blocked,created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Админка</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50">
<main class="mx-auto max-w-6xl p-6 space-y-6">
  <h1 class="text-2xl font-semibold">Админ-панель</h1>

  <section class="rounded-xl border bg-white p-4">
    <h2 class="font-semibold">Сделки</h2>
    <div class="mb-3 flex gap-2 flex-wrap">
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='active') echo 'bg-black text-white'; ?>" href="/admin/index.php?tab=active">Активные (<?php echo (int)$counts['active']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='success') echo 'bg-black text-white'; ?>" href="/admin/index.php?tab=success">Успешные (<?php echo (int)$counts['success']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='rejected') echo 'bg-black text-white'; ?>" href="/admin/index.php?tab=rejected">Отклоненные (<?php echo (int)$counts['rejected']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='dispute') echo 'bg-black text-white'; ?>" href="/admin/index.php?tab=dispute">Арбитраж (<?php echo (int)$counts['dispute']; ?>)</a>
    </div>

    <?php if ($tab !== 'dispute'): ?>
      <div class="grid md:grid-cols-2 gap-2">
        <?php foreach ($deals as $d): ?>
          <div class="border rounded p-3 flex justify-between items-center">
            <div>#<?php echo (int)$d['id']; ?> · <?php echo e($d['number']); ?> · <?php echo e(_status_ru_local($d['status'])); ?> · <?php echo e($d['created_at']); ?></div>
            <div class="flex gap-2">
              <a class="px-3 py-1 rounded border" href="/deals/view.php?id=<?php echo (int)$d['id']; ?>">Открыть</a>
              <form method="post" action="/admin/deal_delete.php" onsubmit="return confirm('Удалить сделку? Действие необратимо.');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
                <button class="px-3 py-1 rounded border text-red-700">Удалить</button>
              </form>
            </div>
          </div>
        <?php endforeach; if (!$deals): ?>
          <div class="text-slate-600">Нет сделок в этой категории.</div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Вкладка "Арбитраж" — показываем споры -->
      <div class="grid md:grid-cols-2 gap-2">
        <?php foreach ($disputes as $row): ?>
          <div class="border rounded p-3 flex justify-between items-center">
            <div>
              #<?php echo (int)$row['id']; ?> · <?php echo e($row['number']); ?> · Арбитраж · <?php echo e($row['dispute_created']); ?>
            </div>
            <div class="flex gap-2">
              <a class="px-3 py-1 rounded border" href="/deals/view.php?id=<?php echo (int)$row['id']; ?>">Открыть</a>
              <a class="px-3 py-1 rounded border bg-black text-white" href="/admin/dispute_resolve.php?id=<?php echo (int)$row['dispute_id']; ?>">Решить</a>
              <form method="post" action="/admin/deal_delete.php" onsubmit="return confirm('Удалить сделку целиком? Действие необратимо.');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
                <button class="px-3 py-1 rounded border text-red-700">Удалить</button>
              </form>
            </div>
          </div>
        <?php endforeach; if (!$disputes): ?>
          <div class="text-slate-600">Споров нет.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="rounded-xl border bg-white p-4">
    <h2 class="font-semibold">Заявки на вывод</h2>
    <div class="mt-3 space-y-3">
      <?php foreach ($payouts as $p): ?>
        <div class="border rounded p-3">
          <div class="flex justify-between items-center">
            <div>#<?php echo (int)$p['id']; ?> · <a class="underline" href="/admin/user_edit.php?id=<?php echo (int)$p['user_id']; ?>"><?php echo e($p['login']); ?></a> · ₽ <?php echo (int)$p['amount']; ?> · <?php echo e(_payout_ru_local($p['status'])); ?> · <?php echo e($p['created_at']); ?></div>
            <?php if ($p['status']==='PENDING'): ?>
              <form method="post" action="/admin/payout_action.php" class="inline">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button name="do" value="approve" class="px-3 py-1 rounded border">Одобрить</button>
              </form>
            <?php endif; ?>
          </div>
          <?php if ($p['status']==='PENDING'): ?>
          <form method="post" action="/admin/payout_action.php" class="mt-2 space-y-2">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
            <label class="block text-sm">Комментарий при отклонении (обязательно)</label>
            <textarea name="reason" required class="w-full border rounded px-3 py-2" rows="2" placeholder="Причина отклонения..."></textarea>
            <button name="do" value="reject" class="px-3 py-1 rounded border">Отклонить</button>
          </form>
          <?php elseif (!empty($p['admin_comment'])): ?>
            <div class="mt-2 text-sm text-slate-600">Комментарий администратора: <?php echo e($p['admin_comment']); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; if (!$payouts): ?>
        <div class="text-slate-600">Заявок нет.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="rounded-xl border bg-white p-4">
    <h2 class="font-semibold">Пользователи</h2>
    <div class="mt-3 grid md:grid-cols-2 gap-2">
      <?php foreach ($users as $u): ?>
        <div class="border rounded p-3 flex justify-between items-center">
          <div>#<?php echo (int)$u['id']; ?> · <?php echo e($u['login']); ?> · <?php echo e(_role_ru_local($u['role'])); ?></div>
          <a class="px-3 py-1 rounded border" href="/admin/user_edit.php?id=<?php echo (int)$u['id']; ?>">Править</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>
</body></html>
