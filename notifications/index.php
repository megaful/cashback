<?php
require_once __DIR__.'/../includes/config.php';
require_login();

$page_title = 'Уведомления';
$HIDE_NOTIF = true; // скрываем колокольчик на этой странице

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

include __DIR__.'/../includes/header.php';
?>
<main class="mx-auto max-w-3xl p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>

  <div class="rounded-2xl border bg-white p-4 mt-3">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold">Уведомления</h3>
      <button id="notifMarkAllPage" class="text-sm underline">Пометить все прочитанными</button>
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
        <div class="notif-item rounded-xl border px-4 py-3 <?php echo $is_read ? '' : 'bg-yellow-50'; ?>" data-id="<?php echo $nid; ?>">
          <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
              <div class="font-medium truncate"><?php echo h($title); ?></div>
              <?php if ($created): ?><div class="text-xs text-slate-500"><?php echo h($created); ?></div><?php endif; ?>
            </div>
            <?php if (!empty($url) && $url !== '#'): ?>
              <a href="<?php echo h($url); ?>" class="open-notif shrink-0 px-3 py-1.5 rounded-lg border" data-id="<?php echo $nid; ?>">Открыть</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (count($rows)===0): ?>
        <div class="text-slate-500 text-sm">Уведомлений нет.</div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
(function(){
  // Клик по "Открыть" — пометить одно уведомление прочитанным и обновить бейдж
  const badge = document.getElementById('notifBadge');
  document.querySelectorAll('.open-notif').forEach(btn=>{
    btn.addEventListener('click', async (e)=>{
      e.preventDefault();
      const id   = btn.dataset.id;
      const href = btn.getAttribute('href') || '#';

      try {
        await fetch('/notifications/mark_read.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + (window.CSRF_TOKEN||'')
        });
      } catch(e) {}

      const card = btn.closest('.notif-item');
      if (card) card.classList.remove('bg-yellow-50');

      if (badge) {
        let c = parseInt(badge.dataset.count || '0', 10) || 0;
        if (c > 0) c--;
        badge.dataset.count = String(c);
        if (c <= 0) { badge.textContent = ''; badge.style.display = 'none'; }
        else { badge.textContent = String(c); badge.style.display = 'inline-flex'; }
      }

      // Небольшая задержка — даём запросу докатиться
      setTimeout(()=>{ window.location.href = href; }, 50);
    });
  });

  // "Пометить все прочитанными"
  const btnAll = document.getElementById('notifMarkAllPage');
  if (btnAll){
    btnAll.addEventListener('click', async (ev)=>{
      ev.preventDefault();
      try{
        const resp = await fetch('/notifications/mark_all_read.php', {
          method:'POST',
          credentials:'same-origin',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'csrf_token='+(window.CSRF_TOKEN||'')
        });
        if (resp.ok){
          if (badge){ badge.textContent=''; badge.style.display='none'; badge.dataset.count='0'; }
          document.querySelectorAll('#notifListPage .bg-yellow-50').forEach(el=>el.classList.remove('bg-yellow-50'));
        }
      }catch(e){}
    });
  }
})();
</script>
</body></html>
