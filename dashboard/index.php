<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

$st = $pdo->prepare('SELECT is_blocked FROM users WHERE id=?');
$st->execute([$user['id']]);
$isBlocked = (int)($st->fetch()['is_blocked'] ?? 0) === 1;

$unread = function_exists('unread_count') ? unread_count($pdo, $user['id']) : 0;

$tab = $_GET['tab'] ?? 'active';
$activeStatuses   = ['PENDING_ACCEPTANCE','AWAITING_FUNDING','FUNDED','IN_PROGRESS','SUBMITTED'];
$successStatuses  = ['ACCEPTED','RESOLVED_ACCEPTED'];
$rejectedStatuses = ['REJECTED','RESOLVED_REJECTED'];
$disputeStatuses  = ['DISPUTE_OPENED'];

$counts = ['active'=>0,'success'=>0,'rejected'=>0,'dispute'=>0];
function count_for($pdo,$statuses,$uid){
  if (empty($statuses)) return 0;
  $in = implode(',', array_fill(0, count($statuses), '?'));
  $sql = "SELECT COUNT(*) c FROM deals WHERE status IN ($in) AND (seller_id=? OR buyer_id=?)";
  $st = $pdo->prepare($sql);
  $params = $statuses; array_push($params,$uid,$uid);
  $st->execute($params);
  $r = $st->fetch();
  return (int)($r['c'] ?? 0);
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
$params = $statuses;
array_push($params, $user['id'], $user['id']);

$sql = "SELECT d.*, s.login AS seller_login, b.login AS buyer_login
        FROM deals d
        JOIN users s ON s.id=d.seller_id
        JOIN users b ON b.id=d.buyer_id
        WHERE d.status IN ($in) AND (d.seller_id = ? OR d.buyer_id = ?)
        ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deals = $stmt->fetchAll();

$bal = $pdo->prepare('SELECT balance FROM balances WHERE user_id = ?');
$bal->execute([$user['id']]);
$balance = (int)($bal->fetch()['balance'] ?? 0);

function _status_ru_local($s){ return function_exists('status_ru') ? status_ru($s) : $s; }
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Личный кабинет CashBack-Market</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head><body class="bg-slate-50">

<?php
// ✅ Адаптивный топ-бар. НЕ задаём $menu — включится авто-меню (покажет «Витрина», «Мои объявления», «Выставить товар» для SELLER).
$user_login = $user['login'] ?? null;
include __DIR__.'/../includes/topbar.php';
?>

<main class="mx-auto max-w-6xl px-4 py-6 grid md:grid-cols-3 gap-4">

  <?php if ($isBlocked): ?>
    <div class="md:col-span-3 rounded-xl border bg-red-50 p-6 text-red-800">
      <b>Ваш аккаунт заблокирован.</b> Обратитесь к администратору для уточнения деталей.
    </div>
  <?php else: ?>

  <section class="md:col-span-2">
    <h2 class="text-xl font-semibold mb-3">Мои сделки</h2>

    <div class="mb-3 flex gap-2 flex-wrap">
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='active') echo 'bg-black text-white'; ?>" href="/dashboard/index.php?tab=active">Активные (<?php echo (int)$counts['active']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='success') echo 'bg-black text-white'; ?>" href="/dashboard/index.php?tab=success">Успешные (<?php echo (int)$counts['success']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='rejected') echo 'bg-black text-white'; ?>" href="/dashboard/index.php?tab=rejected">Отклоненные (<?php echo (int)$counts['rejected']; ?>)</a>
      <a class="px-3 py-1 rounded-xl border <?php if($tab==='dispute') echo 'bg-black text-white'; ?>" href="/dashboard/index.php?tab=dispute">Арбитраж (<?php echo (int)$counts['dispute']; ?>)</a>
    </div>

    <div class="space-y-2">
      <?php foreach ($deals as $d): ?>
        <?php $counter = ($d['seller_id']==$user['id']) ? $d['buyer_login'] : $d['seller_login']; ?>
        <div class="rounded-xl border bg-white p-4 flex justify-between items-center">
          <div>
            <div class="text-sm text-slate-500">#<?php echo e($d['number']); ?> · <?php echo e(_status_ru_local($d['status'])); ?> · Контрагент: <b><?php echo e($counter); ?></b></div>
            <div class="font-medium"><?php echo e($d['title']); ?></div>
          </div>
          <div class="flex items-center gap-2">
            <a class="px-4 py-2 rounded-xl bg-black text-white" href="/deals/view.php?id=<?php echo (int)$d['id']; ?>">Открыть</a>
          </div>
        </div>
      <?php endforeach; if (!$deals): ?>
        <div class="text-slate-600">Нет сделок в этой категории.</div>
      <?php endif; ?>
    </div>
  </section>

  <aside>
    <div class="rounded-xl border bg-white p-4">
      <h3 class="font-semibold">Баланс</h3>
      <div class="text-2xl mt-1">₽ <?php echo (int)$balance; ?></div>
      <div class="mt-3 flex gap-2">
        <a href="/payouts/request.php" class="px-4 py-2 rounded-xl bg-black text-white">Вывести</a>
        <a href="/payouts/history.php" class="px-4 py-2 rounded-xl border">История</a>
      </div>
    </div>
  </aside>

  <?php endif; ?>

</main>
<script src="/assets/js/topbar.js"></script></body></html>
