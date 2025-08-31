<?php
require_once __DIR__.'/../includes/config.php';
require_login();
header('Content-Type: text/html; charset=utf-8');

$user = current_user();
$uid = $user ? (int)$user['id'] : 0;

function q($pdo, $sql, $args=[]){
  $st = $pdo->prepare($sql); $st->execute($args); return $st->fetchAll(PDO::FETCH_ASSOC);
}

$unread = q($pdo, 'SELECT id,title,url,is_read,created_at FROM notifications WHERE user_id=? AND (is_read=0 OR is_read IS NULL) ORDER BY created_at DESC LIMIT 20', [$uid]);
$total_unread = q($pdo, 'SELECT COUNT(*) c FROM notifications WHERE user_id=? AND (is_read=0 OR is_read IS NULL)', [$uid]);
$recent = q($pdo, 'SELECT id,title,url,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20', [$uid]);

// test poll.php directly to see JSON
$poll_url = '/notifications/poll.php?ts='.time();
?>
<!doctype html>
<meta charset="utf-8">
<title>Диагностика уведомлений</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css">
<div class="max-w-3xl mx-auto p-6">
  <a href="/dashboard/index.php" class="text-sm">&larr; Назад</a>
  <h1 class="text-2xl font-semibold mt-2">Диагностика уведомлений</h1>
  <div class="mt-4 rounded-2xl border bg-white p-4">
    <div>Пользователь: <b>#<?= $uid ?></b> (<?= htmlspecialchars($user['login']??'',ENT_QUOTES,'UTF-8') ?>)</div>
    <div>Непрочитанные (count): <b><?= (int)($total_unread[0]['c'] ?? 0) ?></b></div>
    <div class="mt-2"><a class="underline" href="<?= $poll_url ?>" target="_blank">Открыть JSON /notifications/poll.php</a></div>
  </div>

  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="rounded-2xl border bg-white p-4">
      <h3 class="font-semibold">Последние непрочитанные</h3>
      <ol class="mt-2 space-y-2">
        <?php foreach ($unread as $n): ?>
          <li class="text-sm"><span class="text-slate-500">#<?= (int)$n['id'] ?></span> · <?= htmlspecialchars($n['title']??'—',ENT_QUOTES,'UTF-8') ?> · <span class="text-slate-500"><?= htmlspecialchars($n['created_at']??'',ENT_QUOTES,'UTF-8') ?></span></li>
        <?php endforeach; if (count($unread)===0): ?><li class="text-slate-500 text-sm">нет</li><?php endif; ?>
      </ol>
    </div>
    <div class="rounded-2xl border bg-white p-4">
      <h3 class="font-semibold">Последние (все)</h3>
      <ol class="mt-2 space-y-2">
        <?php foreach ($recent as $n): ?>
          <li class="text-sm"><span class="text-slate-500">#<?= (int)$n['id'] ?></span> · <?= htmlspecialchars($n['title']??'—',ENT_QUOTES,'UTF-8') ?> · <span class="text-slate-500"><?= htmlspecialchars($n['created_at']??'',ENT_QUOTES,'UTF-8') ?></span> · <?= ((int)($n['is_read']??0))?'<span class="text-green-600">read</span>':'<span class="text-red-600">unread</span>' ?></li>
        <?php endforeach; if (count($recent)===0): ?><li class="text-slate-500 text-sm">нет</li><?php endif; ?>
      </ol>
    </div>
  </div>

  <div class="mt-4 rounded-2xl border bg-white p-4">
    <h3 class="font-semibold">Проверка AJAX с этой страницы</h3>
    <div class="text-sm text-slate-600">Ниже — «сырая» проверка fetch('/notifications/poll.php'). Открой консоль (F12 → Console/Network) и посмотри ответ сервера и ошибки.</div>
    <pre id="diagOut" class="mt-3 bg-slate-50 p-3 rounded text-xs overflow-auto"></pre>
    <button id="diagBtn" class="mt-2 px-3 py-1.5 rounded border">Запросить poll.php</button>
    <script>
      (function(){
        const out = document.getElementById('diagOut');
        document.getElementById('diagBtn').addEventListener('click', async function(){
          try{
            const resp = await fetch('/notifications/poll.php?ts='+Date.now(), {credentials:'same-origin', cache:'no-store'});
            const txt = await resp.text();
            out.textContent = txt;
          }catch(e){ out.textContent = 'AJAX error: '+ (e && e.message ? e.message : e); }
        });
      })();
    </script>
  </div>
</div>
