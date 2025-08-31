<?php
if (!isset($user) && function_exists('current_user')) { $user = current_user(); }
$user_login = $user['login'] ?? ($_SESSION['user']['login'] ?? null);
$user_role  = $user['role']  ?? null;

$defIsAdmin = function($u){
  $r = isset($u['role']) ? (string)$u['role'] : '';
  return (strtoupper($r) === 'ADMIN' || $r === 'Админ');
};
$isAdmin = function_exists('is_admin') ? is_admin() : $defIsAdmin($user);

/* счетчик непрочитанных */
$unreadCount = 0;
try {
  if (isset($pdo, $user['id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) c
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='is_read'");
    $chk->execute();
    $hasIsRead = ((int)($chk->fetch()['c'] ?? 0) > 0);
    if ($hasIsRead) {
      $st = $pdo->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
      $st->execute([$user['id']]);
      $unreadCount = (int)($st->fetch()['c'] ?? 0);
    }
  }
} catch (Throwable $e) { $unreadCount = 0; }

/* меню */
$autoMenu = [
  ['href'=>'/deals/create.php','label'=>'Создать сделку','primary'=>true],
  ['href'=>'/store/index.php','label'=>'Витрина'],
  ['href'=>'/profile/index.php','label'=>'Профиль'],
  ['href'=>'/payouts/request.php','label'=>'Вывести'],
  ['href'=>'/notifications/index.php','label'=>'Уведомления'],
];
if (($user_role ?? null) === 'SELLER') {
  $autoMenu[] = ['href'=>'/seller/listings/index.php','label'=>'Мои объявления'];
  $autoMenu[] = ['href'=>'/seller/listings/create.php','label'=>'Выставить товар'];
}
if ($isAdmin) {
  $autoMenu[] = ['href'=>'/admin/index.php','label'=>'Админ-панель'];
  $autoMenu[] = ['href'=>'/admin/listings.php','label'=>'Модерация объявлений'];
}
$menu = isset($menu) && is_array($menu) && $menu ? $menu : $autoMenu;
$_seen = [];
$menu = array_values(array_filter($menu, function($i) use (&$_seen){
  $k = $i['href'] ?? ''; if (isset($_seen[$k])) return false; $_seen[$k]=1; return true;
}));

/* НАДЁЖНО: считаем, что мы на странице уведомлений, если в URI есть /notifications/ */
$uri    = $_SERVER['REQUEST_URI'] ?? '';
$self   = $_SERVER['PHP_SELF'] ?? '';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$isNotificationsPage =
  (strpos($uri,   '/notifications/') !== false) ||
  (strpos($self,  '/notifications/') !== false) ||
  (strpos($script,'/notifications/') !== false);
?>
<style>
  .sa-top{padding-top:env(safe-area-inset-top)}
  .topbar-mobile{display:block}.topbar-desktop{display:none}
  @media (min-width:768px){.topbar-mobile{display:none}.topbar-desktop{display:flex}}
  .scroll-x{overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
  .scroll-x::-webkit-scrollbar{display:none}
  .pill{border:1px solid #e5e7eb;border-radius:9999px;padding:8px 16px;display:inline-block;white-space:nowrap;position:relative}
  .pill-primary{background:#000;color:#fff;border-color:#000}
  header .row{display:flex;align-items:center;justify-content:space-between;height:56px}
  .notif-btn{position:relative;display:inline-flex;align-items:center}
  .notif-badge{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;padding:0 4px;border-radius:9999px;background:#ef4444;color:#fff;font-size:12px;line-height:18px;text-align:center;display:<?= $unreadCount>0?'inline-block':'none' ?>}
</style>

<header id="topbar" class="bg-white border-b sticky top-0 z-40 sa-top">
  <div class="max-w-6xl mx-auto" style="padding:0 16px;">
    <div class="row">
      <a href="/dashboard/index.php" class="font-semibold">На главную</a>

      <div class="hidden md:flex items-center gap-3">
        <div class="text-sm" style="color:#475569;">Вы вошли как <span class="font-semibold"><?= htmlspecialchars($user_login ?? '') ?></span></div>

        <?php if (!$isNotificationsPage): ?>
          <a href="/notifications/index.php" class="pill notif-btn" aria-label="Уведомления" id="notifBell">
            🔔
            <span id="notifBadge" class="notif-badge" data-count="<?= (int)$unreadCount ?>"><?= (int)$unreadCount ?></span>
          </a>
        <?php endif; ?>
      </div>

      <button id="topbarBurger" class="md:hidden" style="width:40px;height:40px;border:1px solid #e5e7eb;border-radius:12px;">☰</button>
    </div>

    <div class="topbar-mobile" style="position:relative;">
      <div class="scroll-x" style="display:flex;gap:12px;padding:12px 0 16px 0;">
        <?php foreach ($menu as $item): ?>
          <a href="<?= htmlspecialchars($item['href']) ?>" class="pill <?= !empty($item['primary'])?'pill-primary':'' ?>"><?= htmlspecialchars($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="topbar-desktop" style="gap:8px;padding-bottom:12px;">
      <?php foreach ($menu as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="pill <?= !empty($item['primary'])?'pill-primary':'' ?>"><?= htmlspecialchars($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="topbarDrawer" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0" data-close style="background:rgba(0,0,0,.4)"></div>
    <nav class="absolute right-0 top-0 h-full" style="width:78%;max-width:320px;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.15);padding:16px;display:flex;flex-direction:column;gap:8px;">
      <div class="row" style="justify-content:space-between;">
        <div class="text-sm" style="color:#475569;">Вы: <span class="font-semibold"><?= htmlspecialchars($user_login ?? '') ?></span></div>
        <button class="pill" data-close>✕</button>
      </div>
      <?php foreach ($menu as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="pill"><?= htmlspecialchars($item['label']) ?></a>
      <?php endforeach; ?>
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
        <a href="/auth/logout.php" class="pill">Выйти</a>
      </div>
    </nav>
  </div>
</header>

<script>
(function(){
  const b=document.getElementById('topbarBurger'), d=document.getElementById('topbarDrawer');
  if(!b||!d) return; b.addEventListener('click', ()=> d.classList.remove('hidden'));
  d.addEventListener('click', (e)=>{ if(e.target.hasAttribute('data-close')) d.classList.add('hidden'); });
})();
</script>
