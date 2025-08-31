<?php
require_once __DIR__.'/../includes/config.php';
require_login();

function _is_admin_safe(){ if(function_exists('is_admin')) return is_admin(); $u=current_user(); return strtoupper($u['role']??'')==='ADMIN'; }
if(!_is_admin_safe()){ http_response_code(403); exit('Доступ запрещён'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row,$keys,$d=null){ foreach((array)$keys as $k){ if(array_key_exists($k,$row) && $row[$k]!=='' && $row[$k]!==null) return $row[$k]; } return $d; }

const S_PENDING='PENDING'; const S_ACTIVE='ACTIVE'; const S_REJECTED='REJECTED'; const S_ARCHIVED='ARCHIVED';
function status_ru_local($s){ return ['PENDING'=>'На модерации','ACTIVE'=>'Активно','REJECTED'=>'Отклонено','ARCHIVED'=>'Архив'][$s]??$s; }

$tab=$_GET['tab']??'pending'; $err=''; $rows=[]; $counts=['pending'=>0,'active'=>0,'rejected'=>0,'archived'=>0,'all'=>0];

try{
  $q=$pdo->query("SELECT status,COUNT(*) c FROM listings GROUP BY status");
  $m=[S_PENDING=>'pending',S_ACTIVE=>'active',S_REJECTED=>'rejected',S_ARCHIVED=>'archived'];
  while($r=$q->fetch()){ $k=$m[$r['status']]??null; if($k) $counts[$k]=(int)$r['c']; $counts['all']+=(int)$r['c']; }

  if($tab==='all'){
    $sql="SELECT l.*,u.login AS seller_login FROM listings l JOIN users u ON u.id=l.seller_id ORDER BY l.id DESC";
    $st=$pdo->prepare($sql); $st->execute();
  }else{
    $m2=['pending'=>S_PENDING,'active'=>S_ACTIVE,'rejected'=>S_REJECTED,'archived'=>S_ARCHIVED];
    $stt=$m2[$tab]??S_PENDING;
    $sql="SELECT l.*,u.login AS seller_login FROM listings l JOIN users u ON u.id=l.seller_id WHERE l.status=? ORDER BY l.id DESC";
    $st=$pdo->prepare($sql); $st->execute([$stt]);
  }
  $rows=$st->fetchAll();
}catch(Throwable $e){ $err=$e->getMessage(); }

$csrf=function_exists('csrf_token')?csrf_token():'';
$actionUrl='/admin/listings_status.php';
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Админ · Объявления</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<?php @include __DIR__.'/../includes/topbar.php'; ?>
<main class="max-w-7xl mx-auto px-4 py-6">
  <h1 class="text-2xl font-semibold mb-4">Объявления (витрина)</h1>

  <?php if($err): ?>
    <div class="rounded-xl border bg-red-50 text-red-800 p-4 mb-4">Не удалось загрузить данные. <span class="text-xs"><?php echo h($err); ?></span></div>
  <?php endif; ?>

  <div class="flex gap-2 flex-wrap mb-4">
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='pending') echo 'bg-black text-white'; ?>"  href="?tab=pending">На модерации (<?php echo (int)$counts['pending']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='active') echo 'bg-black text-white'; ?>"   href="?tab=active">Активные (<?php echo (int)$counts['active']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='rejected') echo 'bg-black text-white'; ?>" href="?tab=rejected">Отклонённые (<?php echo (int)$counts['rejected']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='archived') echo 'bg-black text-white'; ?>" href="?tab=archived">Архив (<?php echo (int)$counts['archived']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='all') echo 'bg-black text-white'; ?>"      href="?tab=all">Все (<?php echo (int)$counts['all']; ?>)</a>
  </div>

  <?php if(!$rows && !$err): ?>
    <div class="rounded-xl border bg-white p-6 text-slate-600">Нет объявлений в этой категории.</div>
  <?php elseif($rows): ?>
  <div class="overflow-x-auto rounded-xl border bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-100">
        <tr>
          <th class="text-left px-3 py-2">ID</th>
          <th class="text-left px-3 py-2">Статус</th>
          <th class="text-left px-3 py-2">Продавец</th>
          <th class="text-left px-3 py-2">Название</th>
          <th class="text-left px-3 py-2">Ссылка</th>
          <th class="text-left px-3 py-2">Кэшбэк</th>
          <th class="text-left px-3 py-2">Слоты</th>
          <th class="text-left px-3 py-2">Причина</th>
          <th class="text-left px-3 py-2">Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r):
          $title = val($r,'title','Без названия');
          $url   = val($r,['url','product_url']);
          $cash  = (int)preg_replace('~[^\d]~u','', (string)val($r,['cashback','reward','amount'],0));
          $slots = (int)val($r,['slots','remaining','max_deals'],0);
          $reason = trim((string)val($r,'reason',''));
        ?>
        <tr class="border-t align-top">
          <td class="px-3 py-2"><?php echo (int)$r['id']; ?></td>
          <td class="px-3 py-2"><?php echo h(status_ru_local($r['status'])); ?></td>
          <td class="px-3 py-2"><?php echo h($r['seller_login']); ?></td>
          <td class="px-3 py-2"><?php echo h($title); ?></td>
          <td class="px-3 py-2"><?php if($url): ?><a class="underline break-all" target="_blank" href="<?php echo h($url); ?>">ссылка</a><?php endif; ?></td>
          <td class="px-3 py-2">₽ <?php echo $cash; ?></td>
          <td class="px-3 py-2"><?php echo $slots; ?></td>
          <td class="px-3 py-2"><?php if($reason!=='') echo h($reason); ?></td>
          <td class="px-3 py-2">
            <div class="flex flex-col gap-2">
              <?php if($r['status']===S_PENDING): ?>
                <form class="flex flex-wrap items-center gap-2" method="post" action="<?php echo h($actionUrl); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="status" value="ACTIVE">
                  <button class="px-2 py-1 rounded border bg-emerald-600 text-white">Одобрить</button>
                </form>
                <form class="flex flex-wrap items-center gap-2" method="post" action="<?php echo h($actionUrl); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="status" value="REJECTED">
                  <input type="text" name="reason" placeholder="Причина" class="px-2 py-1 border rounded" style="min-width:220px">
                  <button class="px-2 py-1 rounded border bg-rose-600 text-white">Отклонить</button>
                </form>
              <?php elseif($r['status']===S_ACTIVE): ?>
                <form class="flex flex-wrap items-center gap-2" method="post" action="<?php echo h($actionUrl); ?>" onsubmit="return confirm('Перевести в архив?');">
                  <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="status" value="ARCHIVED">
                  <input type="text" name="reason" placeholder="Причина (необязательно)" class="px-2 py-1 border rounded" style="min-width:220px">
                  <button class="px-2 py-1 rounded border">В архив</button>
                </form>
              <?php elseif($r['status']===S_REJECTED || $r['status']===S_ARCHIVED): ?>
                <form class="flex flex-wrap items-center gap-2" method="post" action="<?php echo h($actionUrl); ?>" onsubmit="return confirm('Сделать активным?');">
                  <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="status" value="ACTIVE">
                  <button class="px-2 py-1 rounded border">Активировать</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</main>
</body></html>
