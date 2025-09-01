<?php
// deals/view.php — страница сделки с оплатой через ЮKassa (инициация в /pay/yookassa_create.php)

error_reporting(E_ALL);
ini_set('display_errors', '1');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Мягкий обработчик исключений, чтобы вместо 500 показать понятную ошибку
set_exception_handler(function($e){
  http_response_code(200);
  echo '<!doctype html><meta charset="utf-8"><style>
  body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#f8fafc;padding:24px}
  .err{background:#fee2e2;border:1px solid #fecaca;color:#7f1d1d;border-radius:12px;padding:16px;max-width:960px}
  a{color:#0ea5e9;text-decoration:none}
  </style>
  <div class="err"><b>Ошибка выполнения:</b><br>'.h($e->getMessage()).
  '<div style="margin-top:8px"><a href="javascript:history.back()">← Назад</a></div></div>';
  exit;
});

require_once __DIR__.'/../includes/config.php';
$sys = __DIR__.'/../includes/system_message.php';
if (file_exists($sys)) require_once $sys;

if (function_exists('require_login')) { require_login(); }
else {
  if (session_status()!==PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['user'])) throw new Exception('Не выполнен вход в систему.');
}

$user = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
if (!$user || empty($user['id'])) throw new Exception('Пользователь не определён.');
$uid = (int)$user['id'];

/* ---------- утилиты для схемы БД ---------- */
function has_col(PDO $pdo, string $table, string $col): bool {
  $q=$pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$table,$col]); return (int)($q->fetch()['c'] ?? 0) > 0;
}
function pick_col(PDO $pdo, string $table, array $cands): ?string {
  foreach ($cands as $c) if (has_col($pdo,$table,$c)) return $c; return null;
}
function has_table(PDO $pdo, string $table): bool {
  $q=$pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
  $q->execute([$table]); return (int)($q->fetch()['c'] ?? 0) > 0;
}

/* ---------- загрузка сделки ---------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) throw new Exception('Сделка не найдена. Нет параметра id.');

$sql = "SELECT d.*, s.login AS seller_login, b.login AS buyer_login
        FROM deals d
        JOIN users s ON s.id=d.seller_id
        JOIN users b ON b.id=d.buyer_id
        WHERE d.id=?";
$st = $pdo->prepare($sql); $st->execute([$id]); $deal = $st->fetch();
if (!$deal) throw new Exception('Сделка не найдена.');

$admin    = function_exists('is_admin') ? is_admin() : (strtoupper($user['role'] ?? '')==='ADMIN');
if ($deal['seller_id'] != $uid && $deal['buyer_id'] != $uid && !$admin) throw new Exception('Доступ запрещён.');

$status     = (string)$deal['status'];
$isSeller   = ((int)$deal['seller_id'] === $uid);
$isBuyer    = ((int)$deal['buyer_id']  === $uid);
$isListing  = !empty($deal['listing_id']);
$initiator  = (int)($deal['created_by'] ?? 0);

/* ---------- права на действия ---------- */
$shouldSellerAccept = ($initiator === (int)$deal['buyer_id']);  // инициатор — покупатель ⇒ принимает продавец
$shouldBuyerAccept  = ($initiator === (int)$deal['seller_id']); // инициатор — продавец  ⇒ принимает покупатель

$canAcceptPending = ($status === 'PENDING_ACCEPTANCE') && (
  ($shouldSellerAccept && $isSeller) || ($shouldBuyerAccept && $isBuyer)
);
$canRejectAtPending = ($status==='PENDING_ACCEPTANCE') && ($isSeller || $isBuyer);

// Покупатель может отправлять работу как из FUNDED (первично), так и из IN_PROGRESS (повторно)
$canSubmitWork     = ($isBuyer && in_array($status, ['FUNDED','IN_PROGRESS'], true));

// Оплату в гарант вносит продавец, когда статус AWAITING_FUNDING
$canPayFund        = ($status==='AWAITING_FUNDING') && $isSeller;

// После отправки на проверку продавец принимает решение
$canSellerDecision = ($isSeller && $status==='SUBMITTED');

// Арбитраж
$canOpenDispute    = (($isSeller || $isBuyer) && in_array($status, ['IN_PROGRESS','SUBMITTED','FUNDED'], true));

/* ---------- чат закрывается в финале ---------- */
$chatLockedForUsers = in_array($status, ['ACCEPTED','RESOLVED_ACCEPTED','REJECTED','RESOLVED_REJECTED'], true);
$chatMayPost        = !($chatLockedForUsers && !$admin);

/* ---------- deal_messages / attachments авто-детект ---------- */
$dm_text  = pick_col($pdo,'deal_messages',['message','content','text','body']);
$dm_time  = pick_col($pdo,'deal_messages',['created_at','created','createdAt','created_at_utc']);
$dm_id    = has_col($pdo,'deal_messages','id') ? 'id' : null;

$hasAtt   = has_table($pdo,'deal_attachments');
$da_path  = $hasAtt ? pick_col($pdo,'deal_attachments',['file_path','path','filepath','file']) : null;
$da_mime  = $hasAtt ? pick_col($pdo,'deal_attachments',['mime_type','mime','type','content_type']) : null;
$da_size  = $hasAtt ? pick_col($pdo,'deal_attachments',['file_size','size','bytes']) : null;

/* ---------- безопасные операции с кошельком (используются при подтверждении) ---------- */
function credit_user_safe(PDO $pdo, int $userId, int $amount, string $desc=''): void {
  $pdo->prepare("INSERT INTO balances (user_id, balance) VALUES (?,0) ON DUPLICATE KEY UPDATE balance=balance")->execute([$userId]);
  $pdo->prepare("UPDATE balances SET balance=balance+? WHERE user_id=?")->execute([$amount,$userId]);
  if (has_table($pdo,'wallet_entries')) {
    $hasType = has_col($pdo,'wallet_entries','type');
    $hasDesc = has_col($pdo,'wallet_entries','description');
    $hasTime = has_col($pdo,'wallet_entries','created_at');
    $fields=['user_id','amount']; $place=['?','?']; $vals=[ $userId, $amount ];
    if ($hasType){ $fields[]='type'; $place[]='?'; $vals[]='DEAL_CREDIT'; }
    if ($hasDesc){ $fields[]='description'; $place[]='?'; $vals[]=$desc; }
    if ($hasTime){ $fields[]='created_at'; $place.=',NOW()'; }
    $sql="INSERT INTO wallet_entries (".implode(',',$fields).") VALUES (".implode(',',$place).")";
    $pdo->prepare($sql)->execute($vals);
  }
}

/* ---------- безопасная запись в disputes ---------- */
function insert_dispute_safe(PDO $pdo, int $dealId, int $openedBy, string $reason = ''): void {
  try {
    if (!has_table($pdo,'disputes')) return;
    $cols = []; foreach (['deal_id','opened_by','reason','created_at'] as $c) $cols[$c]=has_col($pdo,'disputes',$c);
    $fields=['deal_id','opened_by']; $vals=[$dealId,$openedBy]; $place='?,?';
    if ($cols['reason'])    { $fields[]='reason';    $vals[]=$reason; $place.=',?'; }
    if ($cols['created_at']){ $fields[]='created_at';              $place.=',NOW()'; }
    $pdo->prepare("INSERT INTO disputes (".implode(',',$fields).") VALUES (".$place.")")->execute($vals);
  } catch (Throwable $e) { /* ignore */ }
}

/* ---------- POST-действия ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (function_exists('check_csrf')) { try { check_csrf(); } catch(Throwable $e){ throw new Exception('CSRF: '.$e->getMessage()); } }

  // Принятие условий
  if (isset($_POST['action']) && $_POST['action']==='accept' && $canAcceptPending) {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='AWAITING_FUNDING' WHERE id=?")->execute([$id]);
    if (function_exists('safe_system_message')) {
      $msg = $isSeller
        ? 'Продавец принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).'
        : 'Покупатель принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).';
      safe_system_message($pdo,$id,$msg,$uid);
    }
    if (function_exists('notify')) {
      $to = $isSeller ? (int)$deal['buyer_id'] : (int)$deal['seller_id'];
      notify($pdo,$to,"Условия по {$deal['number']} приняты","/deals/view.php?id=".$id);
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Отклонение на принятии
  if (isset($_POST['action']) && $_POST['action']==='reject_pending' && $canRejectAtPending) {
    $reason = trim($_POST['reason'] ?? '');
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='REJECTED' WHERE id=?")->execute([$id]);
    if (function_exists('safe_system_message')) {
      $txt='Сделка отклонена'.($reason?': '.$reason:'');
      safe_system_message($pdo,$id,$txt,$uid);
    }
    if (function_exists('notify')) {
      $to = $isSeller ? (int)$deal['buyer_id'] : (int)$deal['seller_id'];
      notify($pdo,$to,"Сделка {$deal['number']} отклонена","/deals/view.php?id=".$id);
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Покупатель отправляет работу (первично или повторно)
  if (isset($_POST['action']) && $_POST['action']==='submit_work' && $canSubmitWork) {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='SUBMITTED' WHERE id=?")->execute([$id]);
    if (function_exists('safe_system_message')) {
      $txt = ($status==='IN_PROGRESS')
        ? "Покупатель повторно отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж."
        : "Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.";
      safe_system_message($pdo,$id,$txt,$uid);
    }
    if (function_exists('notify')) notify($pdo,(int)$deal['seller_id'],"Работа по {$deal['number']} отправлена на проверку","/deals/view.php?id=".$id);
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Продавец подтверждает выполнение
  if (isset($_POST['action']) && $_POST['action']==='seller_accept' && $canSellerDecision) {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='ACCEPTED' WHERE id=?")->execute([$id]);
    $cash = (int)$deal['cashback'];
    $pdo->prepare("INSERT INTO balances (user_id, balance) VALUES (?,0) ON DUPLICATE KEY UPDATE balance=balance")->execute([(int)$deal['buyer_id']]);
    $pdo->prepare("UPDATE balances SET balance=balance+? WHERE user_id=?")->execute([$cash,(int)$deal['buyer_id']]);
    if (has_table($pdo,'wallet_entries')) {
      $pdo->prepare("INSERT INTO wallet_entries (user_id, amount, direction, memo, deal_id) VALUES (?,?,?,?,?)")
          ->execute([(int)$deal['buyer_id'],$cash,'CREDIT','Выплата по сделке '.$deal['number'],(int)$deal['id']]);
    }
    if (function_exists('safe_system_message')) {
      safe_system_message($pdo,$id,"Продавец подтвердил выполнение. Покупателю зачислено ₽ {$cash}.",$uid);
    }
    if (function_exists('notify')) {
      notify($pdo,(int)$deal['buyer_id'],"Продавец подтвердил выполнение по {$deal['number']} — начислено ₽ {$cash}","/deals/view.php?id=".$id);
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Продавец отклоняет работу — назад в IN_PROGRESS
  if (isset($_POST['action']) && $_POST['action']==='seller_reject' && $canSellerDecision) {
    $reason = trim($_POST['reason'] ?? '');
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='IN_PROGRESS' WHERE id=?")->execute([$id]);
    if (function_exists('safe_system_message')) {
      $txt='Продавец отклонил работу'.($reason?': '.$reason:'').'. Средства остаются в гарант-счёте. Покупатель может доработать и отправить снова.';
      safe_system_message($pdo,$id,$txt,$uid);
    }
    if (function_exists('notify')) {
      notify($pdo,(int)$deal['buyer_id'],"Отклонение работы по {$deal['number']} — требуется доработка","/deals/view.php?id=".$id);
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Открыть арбитраж
  if (isset($_POST['action']) && $_POST['action']==='open_dispute' && $canOpenDispute) {
    $reason = trim($_POST['reason'] ?? '');
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE deals SET status='DISPUTE_OPENED' WHERE id=?")->execute([$id]);
    try{
      if (has_table($pdo,'disputes')){
        $pdo->prepare("INSERT INTO disputes (deal_id, opened_by, reason, created_at) VALUES (?,?,?,NOW())")
            ->execute([$id,$uid,$reason]);
      }
    }catch(Throwable $e){}
    if (function_exists('safe_system_message')) {
      $txt='Открыт арбитраж'.($reason?': '.$reason:'');
      safe_system_message($pdo,$id,$txt,$uid);
    }
    if (function_exists('notify')) {
      $other = ($uid==(int)$deal['seller_id']) ? (int)$deal['buyer_id'] : (int)$deal['seller_id'];
      notify($pdo,$other,"Открыт арбитраж по {$deal['number']}","/deals/view.php?id=".$id);
      if (function_exists('notify_admin')) { @notify_admin($pdo,"Арбитраж по {$deal['number']}", "/admin/deals.php?id=".$id); }
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id); exit;
  }

  // Сообщение в чат
  if (isset($_POST['action']) && $_POST['action']==='message' && $chatMayPost) {
    $text = trim($_POST['text'] ?? '');
    if ($text==='' && empty($_FILES['files']['name'][0])) throw new Exception('Введите сообщение или добавьте файл.');
    if (!$dm_text) throw new Exception('В таблице deal_messages нет текстовой колонки (ожидались: message/content/text/body).');

    $pdo->beginTransaction();
    if ($dm_time) {
      $pdo->prepare("INSERT INTO deal_messages (deal_id,sender_id,`{$dm_text}`,`{$dm_time}`) VALUES (?,?,?,NOW())")->execute([$id,$uid,$text]);
    } else {
      $pdo->prepare("INSERT INTO deal_messages (deal_id,sender_id,`{$dm_text}`) VALUES (?,?,?)")->execute([$id,$uid,$text]);
    }
    $msgId = (int)$pdo->lastInsertId();

    if ($hasAtt && !empty($_FILES['files']['name'][0])) {
      $count = min(count($_FILES['files']['name']), 5);
      $uploadDir = __DIR__.'/../uploads'; if (!is_dir($uploadDir)) @mkdir($uploadDir,0775,true);
      for ($i=0; $i<$count; $i++){
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $name=$_FILES['files']['name'][$i]; $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); $size=(int)$_FILES['files']['size'][$i];
        if (!in_array($ext,['jpg','jpeg','png'],true)) continue; if ($size>10*1024*1024) continue;
        $tmp=$_FILES['files']['tmp_name'][$i]; $safe=bin2hex(random_bytes(16)).'.'.$ext; $dest=$uploadDir.'/'.$safe;
        if (move_uploaded_file($tmp,$dest)) {
          $fields=['message_id']; $place=['?']; $vals=[$msgId];
          $pathCol = $da_path ?: (has_col($pdo,'deal_attachments','file_path') ? 'file_path' : null);
          if ($pathCol){ $fields[]=$pathCol; $place[]='?'; $vals[]='/uploads/'.$safe; }
          if ($da_mime){ $fields[]=$da_mime; $place[]='?'; $vals[]=($ext==='png'?'image/png':'image/jpeg'); }
          if ($da_size){ $fields[]=$da_size; $place[]='?'; $vals[]=$size; }
          $sqlAtt="INSERT INTO deal_attachments (".implode(',',$fields).") VALUES (".implode(',',$place).")";
          $pdo->prepare($sqlAtt)->execute($vals);
        }
      }
    }

    if (function_exists('notify')) {
      $to = ($uid==(int)$deal['seller_id']) ? (int)$deal['buyer_id'] : (int)$deal['seller_id'];
      notify($pdo,$to,"Новое сообщение по {$deal['number']}","/deals/view.php?id=".$id.'#chat');
    }
    $pdo->commit(); header('Location: /deals/view.php?id='.$id.'#chat'); exit;
  }
}

/* ---------- чтение сообщений ---------- */
$msgs=[];
if ($dm_text) {
  $selText="m.`{$dm_text}`";
  $selTime = $dm_time ? "m.`{$dm_time}`" : ($dm_id ? "m.`{$dm_id}`" : "m.`{$dm_text}`");
  $orderExpr = $dm_time ? "`{$dm_time}` ASC, m.id ASC" : "m.id ASC";

  $sqlMsgs="SELECT m.*,u.login,{$selText} AS msg_text,{$selTime} AS msg_created
            FROM deal_messages m
            JOIN users u ON u.id=m.sender_id
            WHERE m.deal_id=?
            ORDER BY {$orderExpr}";
  $st=$pdo->prepare($sqlMsgs); $st->execute([$id]); $msgs=$st->fetchAll();
}

/* ---------- русификация статусов ---------- */
if (!function_exists('status_ru')) {
  function status_ru($s){ return [
    'PENDING_ACCEPTANCE'=>'Ожидает подтверждения',
    'AWAITING_FUNDING'  =>'Ожидает оплаты',
    'FUNDED'            =>'Оплачено',
    'IN_PROGRESS'       =>'В работе',
    'SUBMITTED'         =>'Отправлено на проверку',
    'ACCEPTED'          =>'Успешно завершена',
    'REJECTED'          =>'Отклонена',
    'DISPUTE_OPENED'    =>'Арбитраж',
    'RESOLVED_ACCEPTED' =>'Арбитраж: успешная',
    'RESOLVED_REJECTED' =>'Арбитраж: отклонённая',
  ][$s] ?? $s; }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($deal['number']) ?> — Сделка</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{
    background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),
               linear-gradient(180deg,var(--g1),var(--g2)) fixed;
  }
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .glass{background:rgba(255,255,255,.86);backdrop-filter:saturate(140%) blur(6px);}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF); color:#fff}
  .btn-grad:hover{filter:brightness(.95)}
  .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:6px 12px;background:#fff}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-5 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <div class="mt-3 grid md:grid-cols-3 gap-4">
    <!-- Основной блок сделки -->
    <section class="md:col-span-2 card glass p-5 md:p-6">
      <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg md:text-xl font-semibold"><?= h($deal['number']) ?></h1>
        <div class="text-sm text-slate-600"><?= h(status_ru($status)); ?></div>
      </div>

      <div class="mt-2 text-sm text-slate-700">
        Продавец: <b><?= h($deal['seller_login']) ?></b> ·
        Покупатель: <b><?= h($deal['buyer_login']) ?></b>
      </div>

      <div class="mt-3">
        <div class="font-medium"><?= h($deal['title']); ?></div>
        <?php if(!empty($deal['product_url'])): ?>
          <div class="break-all text-sm mt-1">
            Товар: <a class="underline" target="_blank" href="<?= h($deal['product_url']); ?>"><?= h($deal['product_url']); ?></a>
          </div>
        <?php endif; ?>
        <div class="text-sm mt-1">Кэшбэк: <b>₽ <?= (int)$deal['cashback']; ?></b> + комиссия сервиса <b>₽ <?= (int)$deal['commission']; ?></b></div>
        <?php if(!empty($deal['terms_text'])): ?>
          <div class="mt-3 text-sm whitespace-pre-line"><?= h($deal['terms_text']); ?></div>
        <?php endif; ?>
        <?php if($isListing): ?>
          <div class="mt-2 text-xs text-slate-500">Сделка создана по объявлению (Витрина).</div>
        <?php endif; ?>
      </div>

      <!-- Кнопки состояний -->
      <?php if ($status==='PENDING_ACCEPTANCE'): ?>
        <div class="mt-4 flex flex-wrap gap-2">
          <?php if ($canAcceptPending): ?>
            <form method="post">
              <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
              <input type="hidden" name="action" value="accept">
              <button class="px-4 py-2 rounded-full btn-grad">Принять условия</button>
            </form>
          <?php endif; ?>
          <?php if ($canRejectAtPending): ?>
            <form method="post" class="flex flex-wrap gap-2 items-center">
              <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
              <input type="hidden" name="action" value="reject_pending">
              <input class="px-3 py-2 rounded-xl border" name="reason" placeholder="Причина (необязательно)">
              <button class="px-4 py-2 rounded-xl border">Отклонить</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($canPayFund): ?>
        <div class="mt-4 p-4 rounded-2xl border bg-white/70">
          <div class="text-sm">
            К оплате в гарант: <b>₽ <?= (int)$deal['cashback'] + (int)$deal['commission']; ?></b>
            (кэшбэк ₽<?= (int)$deal['cashback']; ?> + комиссия ₽<?= (int)$deal['commission']; ?>)
          </div>
          <form method="post" action="/pay/yookassa_create.php" class="mt-2">
            <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
            <input type="hidden" name="deal_id" value="<?= (int)$deal['id']; ?>">
            <button class="px-4 py-2 rounded-full btn-grad">Оплатить в гарант (ЮKassa)</button>
          </form>
          <div class="text-xs text-slate-500 mt-2">После успешной оплаты вы вернётесь на эту страницу. Статус станет «Оплачено».</div>
        </div>
      <?php endif; ?>

      <?php if ($canSubmitWork): ?>
        <div class="mt-4 p-4 rounded-2xl border bg-white/70">
          <div class="text-sm">
            <?php if($status==='IN_PROGRESS'): ?>
              Продавец запросил доработку. Прикрепите недостающие пруфы в чат ниже и отправьте повторно на проверку.
            <?php else: ?>
              После выполнения задания отправьте работу на проверку продавцу.
            <?php endif; ?>
          </div>
          <form method="post" class="mt-2">
            <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
            <input type="hidden" name="action" value="submit_work">
            <button class="px-4 py-2 rounded-full btn-grad">
              <?= ($status==='IN_PROGRESS') ? 'Повторно отправить на проверку' : 'Отправить на проверку (подтвердить выполнение)'; ?>
            </button>
          </form>
          <div class="text-xs text-slate-500 mt-2">Скриншоты и комментарии прикладывайте в чат ниже.</div>
        </div>
      <?php endif; ?>

      <?php if ($canSellerDecision): ?>
        <div class="mt-4 p-4 rounded-2xl border bg-white/70">
          <div class="text-sm font-medium mb-2">Решение продавца:</div>
          <div class="flex flex-col gap-2">
            <form method="post">
              <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
              <input type="hidden" name="action" value="seller_accept">
              <button class="px-4 py-2 rounded-full btn-grad">Подтвердить выполнение</button>
            </form>
            <form method="post" class="flex flex-wrap gap-2 items-center">
              <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
              <input type="hidden" name="action" value="seller_reject">
              <input class="px-3 py-2 rounded-xl border" name="reason" placeholder="Причина отклонения (желательно)">
              <button class="px-4 py-2 rounded-xl bg-amber-600 text-white">Отклонить</button>
              <span class="text-xs text-slate-600">Средства остаются в гарант-счёте, покупатель может доработать и отправить заново.</span>
            </form>
            <form method="post" class="flex flex-wrap gap-2 items-center">
              <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
              <input type="hidden" name="action" value="open_dispute">
              <input class="px-3 py-2 rounded-xl border" name="reason" placeholder="Краткая причина (необязательно)">
              <button class="px-4 py-2 rounded-xl bg-red-600 text-white">Арбитраж</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <!-- Боковая панель -->
    <aside class="card glass p-5 md:p-6">
      <h3 class="font-semibold">Действия</h3>
      <div class="mt-2 text-sm text-slate-700">
        Процесс: Принятие условий → Оплата продавцом → Выполнение покупателем → (Принять / Отклонить / Арбитраж) → Решение.
      </div>
      <div class="mt-3 text-xs text-slate-600">Текущий статус: <?= h(status_ru($status)); ?></div>
    </aside>
  </div>

  <!-- ЧАТ -->
  <section id="chat" class="mt-4 card glass p-5 md:p-6">
    <h2 class="text-lg font-semibold mb-2">Чат сделки</h2>

    <?php if (!$dm_text): ?>
      <div class="rounded-xl border bg-yellow-50 text-yellow-900 p-3">
        В таблице <b>deal_messages</b> не найдена текстовая колонка (ожидались: <code>message</code>, <code>content</code>, <code>text</code>, <code>body</code>).
        Пример:
        <pre style="white-space:pre-wrap">ALTER TABLE deal_messages ADD COLUMN message TEXT NOT NULL;</pre>
      </div>
    <?php endif; ?>

    <?php
      $msgs = $msgs ?? [];
      $attStmt = null;
      if ($hasAtt) $attStmt = $pdo->prepare("SELECT * FROM deal_attachments WHERE message_id=? ORDER BY id ASC");
      $pathOut = $da_path ?: (has_col($pdo,'deal_attachments','file_path') ? 'file_path' : null);
    ?>
    <div class="space-y-3">
      <?php foreach ($msgs as $m): ?>
        <div class="rounded-xl border p-3 <?= ($m['sender_id']==$uid)?'bg-slate-50':''; ?>">
          <div class="text-sm text-slate-500"><?= h($m['login'] ?: ('User #'.$m['sender_id'])); ?> · <?= h($m['msg_created']); ?></div>
          <?php if(!empty($m['msg_text'])): ?>
            <div class="mt-1 whitespace-pre-line"><?= nl2br(h($m['msg_text'])); ?></div>
          <?php endif; ?>

          <?php if ($attStmt && $pathOut): ?>
            <?php $attStmt->execute([$m['id']]); $atts=$attStmt->fetchAll(); ?>
            <?php if ($atts): ?>
              <div class="mt-2 flex gap-2 flex-wrap">
                <?php foreach($atts as $a): $p=$a[$pathOut]??null; if(!$p) continue; ?>
                  <a class="inline-block" href="<?= h($p); ?>" target="_blank" rel="noopener">
                    <img src="<?= h($p); ?>" alt="" style="max-height:120px;border-radius:12px;border:1px solid #e5e7eb;">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; if (!$msgs): ?>
        <div class="text-slate-600">Сообщений пока нет.</div>
      <?php endif; ?>
    </div>

    <?php if ($chatMayPost && $dm_text): ?>
      <form class="mt-4 space-y-2" method="post" enctype="multipart/form-data" action="/deals/view.php?id=<?= (int)$id; ?>#chat">
        <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
        <input type="hidden" name="action" value="message">
        <textarea name="text" rows="3" class="w-full rounded-xl border p-3" placeholder="Напишите сообщение…"></textarea>

        <!-- Мобильная правка: в колонку, кнопка на всю ширину; на широкой — в линию -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <div class="text-sm text-slate-700 w-full sm:w-auto">
            <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png" class="block w-full">
            <div class="text-xs text-slate-500">До 5 файлов · JPG/PNG · до 10 МБ каждый</div>
          </div>
          <button class="w-full sm:w-auto px-4 py-2 rounded-full btn-grad">Отправить</button>
        </div>
      </form>
    <?php elseif(!$chatMayPost): ?>
      <div class="mt-3 text-sm text-slate-600">Чат закрыт, т.к. сделка завершена. Администраторы могут писать всегда.</div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
