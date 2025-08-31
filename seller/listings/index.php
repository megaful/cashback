<?php
require_once __DIR__.'/../../includes/config.php';
require_login();
$user = current_user();
if (strtoupper($user['role']??'')!=='SELLER' && !is_admin()) { http_response_code(403); exit('Доступ запрещён'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row,$keys,$d=null){ foreach((array)$keys as $k){ if(array_key_exists($k,$row) && $row[$k]!=='' && $row[$k]!==null) return $row[$k]; } return $d; }
function status_ru_local($s){ return ['PENDING'=>'На модерации','ACTIVE'=>'Активно','REJECTED'=>'Отклонено','ARCHIVED'=>'Архив'][$s]??$s; }

$tab=$_GET['tab']??'all';

$uid=(int)$user['id'];
$counts=['all'=>0,'active'=>0,'pending'=>0,'rejected'=>0,'archived'=>0];
$st=$pdo->prepare("SELECT status,COUNT(*) c FROM listings WHERE seller_id=? GROUP BY status");
$st->execute([$uid]);
while($r=$st->fetch()){
  $counts['all'] += (int)$r['c'];
  if(isset($counts[strtolower($r['status'])])) $counts[strtolower($r['status'])]=(int)$r['c'];
}

$params=[$uid]; $where="seller_id=?";
if($tab==='active')   {$where.=" AND status='ACTIVE'";}
elseif($tab==='pending') {$where.=" AND status='PENDING'";}
elseif($tab==='rejected'){$where.=" AND status='REJECTED'";}
elseif($tab==='archived'){$where.=" AND status='ARCHIVED'";}

$sql="SELECT * FROM listings WHERE $where ORDER BY id DESC";
$q=$pdo->prepare($sql); $q->execute($params); $rows=$q->fetchAll();
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Мои объявления</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<?php @include __DIR__.'/../../includes/topbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-6">
  <h1 class="text-2xl font-semibold mb-4">Мои объявления</h1>

  <div class="flex gap-2 flex-wrap mb-4">
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='all') echo 'bg-black text-white'; ?>"      href="?tab=all">Все (<?php echo (int)$counts['all']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='active') echo 'bg-black text-white'; ?>"   href="?tab=active">Активные (<?php echo (int)$counts['active']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='pending') echo 'bg-black text-white'; ?>"  href="?tab=pending">На модерации (<?php echo (int)$counts['pending']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='rejected') echo 'bg-black text-white'; ?>" href="?tab=rejected">Отклонённые (<?php echo (int)$counts['rejected']; ?>)</a>
    <a class="px-3 py-1 rounded-xl border <?php if($tab==='archived') echo 'bg-black text-white'; ?>" href="?tab=archived">Архив (<?php echo (int)$counts['archived']; ?>)</a>
    <a class="ml-auto px-3 py-1 rounded-xl border" href="/seller/listings/create.php">Выставить товар</a>
  </div>

  <?php if(!$rows): ?>
    <div class="rounded-xl border bg-white p-6 text-slate-600">Объявлений нет.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
      <?php foreach($rows as $r):
        $title = val($r,'title','Без названия');
        $url   = val($r,['url','product_url']);
        $cash  = (int)preg_replace('~[^\d]~u','', (string)val($r,['cashback','reward','amount'],0));
        $slots = (int)val($r,['slots','remaining','max_deals'],0);
        $reason = trim((string)val($r,'reason',''));
      ?>
      <div class="rounded-xl border bg-white p-4 flex flex-col gap-2">
        <div class="text-sm text-slate-500">#<?php echo (int)$r['id']; ?> · статус: <b><?php echo h(status_ru_local($r['status'])); ?></b></div>
        <div class="font-semibold"><?php echo h($title); ?></div>
        <?php if($url): ?><a class="underline text-sm break-all" target="_blank" href="<?php echo h($url); ?>"><?php echo h($url); ?></a><?php endif; ?>
        <div class="text-sm">Кэшбэк: <b>₽ <?php echo $cash; ?></b></div>
        <div class="text-xs text-slate-600">Доступно слотов: <?php echo $slots; ?></div>

        <?php if(($r['status']==='REJECTED' || $r['status']==='ARCHIVED') && $reason!==''): ?>
          <div class="rounded-lg bg-rose-50 text-rose-800 px-3 py-2 text-sm">Причина: <?php echo h($reason); ?></div>
        <?php endif; ?>

        <div class="mt-1">
          <a class="px-3 py-2 rounded-xl border" target="_blank" href="/store/view.php?id=<?php echo (int)$r['id']; ?>">Открыть на витрине</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body></html>
