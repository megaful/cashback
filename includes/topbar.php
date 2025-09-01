<?php
if (!isset($user) && function_exists('current_user')) { $user = current_user(); }
$user_login = $user['login'] ?? ($_SESSION['user']['login'] ?? null);
$user_role  = $user['role']  ?? null;

/* меню по умолчанию */
$autoMenu = [
  ['href'=>'/deals/create.php','label'=>'Создать сделку','icon'=>'➕','primary'=>true],
  ['href'=>'/store/index.php','label'=>'Витрина','icon'=>'🏬'],
  ['href'=>'/dashboard/index.php','label'=>'Мои сделки','icon'=>'📈'],
  ['href'=>'/profile/index.php','label'=>'Профиль','icon'=>'👤'],
  ['href'=>'/payouts/request.php','label'=>'Вывести','icon'=>'📤'],
  ['href'=>'/notifications/index.php','label'=>'Уведомления','icon'=>'🔔'],
];
$role = strtoupper($user_role ?? '');
if ($role==='SELLER'){
  $autoMenu[] = ['href'=>'/seller/listings/index.php','label'=>'Мои объявления','icon'=>'🗂️'];
  $autoMenu[] = ['href'=>'/seller/listings/create.php','label'=>'Выставить товар','icon'=>'🛍️'];
}
if (function_exists('is_admin') ? is_admin() : (strtoupper($user_role ?? '')==='ADMIN')){
  $autoMenu[] = ['href'=>'/admin/index.php','label'=>'Админ-панель','icon'=>'🛡️'];
  $autoMenu[] = ['href'=>'/admin/listings.php','label'=>'Модерация объявлений','icon'=>'✅'];
}
$menu = isset($menu) && is_array($menu) && $menu ? $menu : $autoMenu;
$_seen=[]; $menu=array_values(array_filter($menu,function($i)use(&$_seen){$k=$i['href']??'';if(isset($_seen[$k]))return false;$_seen[$k]=1;return true;}));
?>
<style>
  .tb-wrap{backdrop-filter:saturate(140%) blur(6px); background:rgba(255,255,255,.88); border-bottom:1px solid #e6e8f0}
  .tb-row{display:flex;align-items:center;justify-content:space-between;height:64px}
  .pill{border:1px solid #e5e7eb;border-radius:9999px;padding:10px 16px;background:#fff;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
  .pill-primary{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff;border-color:transparent}
  .tb-scroll{overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
  .tb-scroll::-webkit-scrollbar{display:none}
  @media (min-width:768px){ .tb-mobile{display:none} }

  /* Drawer (вынесен вне header) */
  #tbDrawer{position:fixed;inset:0;z-index:1000;display:none}
  #tbDrawer.active{display:block}
  #tbMask{position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px)}
  #tbPanel{position:absolute;top:0;right:0;bottom:0;width:min(88vw,360px);background:#fff;box-shadow:-10px 0 30px rgba(0,0,0,.25);display:flex;flex-direction:column}
</style>

<header class="tb-wrap sticky top-0 z-50">
  <div class="max-w-6xl mx-auto px-4">
    <div class="tb-row">
      <a href="/" class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-white font-bold" style="background:linear-gradient(135deg,#8A00FF,#005BFF)">CM</span>
        <span class="font-semibold">Cashback-Market</span>
      </a>

      <div class="hidden md:flex items-center gap-2">
        <?php foreach ($menu as $item): ?>
          <a href="<?= htmlspecialchars($item['href']) ?>" class="pill <?= !empty($item['primary'])?'pill-primary':'' ?>">
            <span><?= htmlspecialchars($item['icon'] ?? '') ?></span>
            <span><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="md:hidden flex items-center gap-3">
        <div class="text-sm text-slate-600">Вы: <b><?= htmlspecialchars($user_login ?? '') ?></b></div>
        <button id="tbBurger" class="pill" aria-label="Меню">⋯</button>
      </div>
    </div>

    <div class="tb-mobile py-2 md:hidden">
      <div class="tb-scroll flex gap-2">
        <?php foreach ($menu as $item): ?>
          <a href="<?= htmlspecialchars($item['href']) ?>" class="pill <?= !empty($item['primary'])?'pill-primary':'' ?>">
            <span><?= htmlspecialchars($item['icon'] ?? '') ?></span>
            <span><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</header>

<!-- Drawer ВНЕ header (исправляет обрезание на iOS) -->
<div id="tbDrawer" aria-hidden="true">
  <div id="tbMask" data-close></div>
  <nav id="tbPanel" role="dialog" aria-modal="true">
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <div class="text-sm text-slate-600">Вы: <b><?= htmlspecialchars($user_login ?? '') ?></b></div>
      <button class="pill" id="tbClose" aria-label="Закрыть">✕</button>
    </div>
    <div class="p-3 space-y-2 overflow-auto">
      <?php foreach ($menu as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="pill w-full justify-between">
          <span class="flex items-center gap-2"><?= htmlspecialchars($item['icon'] ?? '') ?> <?= htmlspecialchars($item['label']) ?></span>
          <span>➜</span>
        </a>
      <?php endforeach; ?>
      <div class="pt-2 mt-2 border-t">
        <a href="/auth/logout.php" class="pill w-full justify-between"><span>↩️ Выйти</span> <span>➜</span></a>
      </div>
    </div>
  </nav>
</div>

<script>
(function(){
  const burger = document.getElementById('tbBurger');
  const drawer = document.getElementById('tbDrawer');
  const mask   = document.getElementById('tbMask');
  const closeX = document.getElementById('tbClose');
  const panel  = document.getElementById('tbPanel');

  function open(){ drawer.classList.add('active'); document.body.style.overflow='hidden'; }
  function close(){ drawer.classList.remove('active'); document.body.style.overflow=''; }

  burger && burger.addEventListener('click', open);
  mask   && mask.addEventListener('click', close);
  closeX && closeX.addEventListener('click', close);
  // Закрытие по Esc
  window.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && drawer.classList.contains('active')) close(); });
  // Переход по ссылке — тоже закрываем
  drawer.querySelectorAll('a').forEach(a=> a.addEventListener('click', close));
})();
</script>
