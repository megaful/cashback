<?php
require_once __DIR__.'/../includes/config.php';
require_login();

$page_title = 'Уведомления';
$HIDE_NOTIF = true; // попросим топбар не показывать колокольчик

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

try {
  $user = current_user();
  $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 300');
  $stmt->execute([$user['id']]);
  $rows = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
  http_response_code(200);
  echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="https://cdn.tailwindcss.com">';
  echo '<div class="max-w-xl mx-auto mt-10 p-6 bg-white border rounded-2xl">';
  echo '<h1 class="text-xl font-semibold">Не удалось получить уведомления</h1>';
  echo '<p class="mt-2 text-slate-600">'.h($e->getMessage()).'</p>';
  echo '<a class="inline-block mt-4 px-4 py-2 rounded-xl border" href="/dashboard/index.php">Назад</a>';
  echo '</div>'; exit;
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Уведомления — Cashback-Market</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),linear-gradient(180deg,var(--g1),var(--g2)) fixed;}
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .btn{border:1px solid #e5e7eb;border-radius:12px;padding:6px 12px;background:#fff}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>
<script>window.CSRF_TOKEN="<?= h(csrf_token()) ?>";</script>

<main class="mx-auto max-w-3xl px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <div class="card bg-white p-4 md:p-6 mt-3">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold">Уведомления</h3>
      <button id="notifMarkAllPage" class="btn text-sm">Пометить все прочитанными</button>
    </div>

    <div id="notifListPage" class="mt-3 space-y-3">
      <?php foreach ($rows as $n): ?>
        <?php
          $nid     = (int)($n['id'] ?? 0);
          $title   = $n['title'] ?? $n['message'] ?? $n['text'] ?? 'Уведомление';
          $url     = $n['url'] ?? $n['link'] ?? '#';
          $is_read = (int)($n['is_read'] ?? 0);
          $created = $n['created_at'] ?? $n['created'] ?? '';
        ?>
        <div class="notif-item card px-4 py-3 <?= $is_read ? 'bg-white' : 'bg-yellow-50' ?>" data-id="<?= $nid ?>">
          <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
              <div class="font-medium truncate"><?= h($title) ?></div>
              <?php if ($created): ?><div class="text-xs text-slate-500"><?= h($created) ?></div><?php endif; ?>
            </div>
            <?php if (!empty($url) && $url !== '#'): ?>
              <a href="<?= h($url) ?>" class="open-notif shrink-0 btn text-sm" data-id="<?= $nid ?>">Открыть</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; if (!count($rows)): ?>
        <div class="text-slate-500 text-sm">Уведомлений нет.</div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
(function(){
  const badge = document.getElementById('notifBadge');
  document.querySelectorAll('.open-notif').forEach(btn=>{
    btn.addEventListener('click', async (e)=>{
      e.preventDefault();
      const id = btn.dataset.id, href = btn.getAttribute('href') || '#';
      try{
        await fetch('/notifications/mark_read.php', {
          method:'POST', credentials:'same-origin',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'id='+encodeURIComponent(id)+'&csrf_token='+(window.CSRF_TOKEN||'')
        });
      }catch(e){}
      btn.closest('.notif-item')?.classList.remove('bg-yellow-50');
      if (badge){ let c=parseInt(badge.dataset.count||'0',10)||0; if(c>0)c--; badge.dataset.count=String(c);
        if(c<=0){badge.textContent='';badge.style.display='none';} else {badge.textContent=String(c);badge.style.display='inline-flex';} }
      setTimeout(()=>location.href=href,50);
    });
  });
  document.getElementById('notifMarkAllPage')?.addEventListener('click', async (ev)=>{
    ev.preventDefault();
    try{
      const r = await fetch('/notifications/mark_all_read.php',{method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'csrf_token='+(window.CSRF_TOKEN||'')});
      if(r.ok){ document.querySelectorAll('#notifListPage .bg-yellow-50').forEach(el=>el.classList.remove('bg-yellow-50'));
        const b=document.getElementById('notifBadge'); if(b){b.textContent='';b.style.display='none';b.dataset.count='0';} }
    }catch(e){}
  });
})();
</script>
</body></html>
