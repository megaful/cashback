<?php
require_once __DIR__.'/includes/config.php';
$u = current_user();

/* 10 последних активных объявлений с фото */
$sql = "
  SELECT l.id, l.title, l.cashback, l.slots, p.file_name
  FROM listings l
  LEFT JOIN listing_photos p ON p.listing_id = l.id
  WHERE l.status = 'ACTIVE'
  GROUP BY l.id
  ORDER BY l.created_at DESC
  LIMIT 10
";
$items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashback-Market — безопасные сделки по покупке товаров за отзыв/кэшбэк</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .ps-btn{
      position:absolute; top:50%; transform:translateY(-50%);
      width:44px; height:44px; border-radius:9999px;
      display:flex; align-items:center; justify-content:center;
      background:#fff; color:#0f172a; box-shadow:0 6px 16px rgba(0,0,0,.15);
      border:1px solid rgba(0,0,0,.06); cursor:pointer; z-index:2;
    }
    .ps-btn:hover{ filter:brightness(0.95); }
    .ps-prev{ left:-8px; } .ps-next{ right:-8px; }
    @media (min-width:768px){ .ps-prev{ left:-14px;} .ps-next{ right:-14px;} }

    /* Слайдер через CSS-переменную --pv */
    .ps-viewport { overflow:hidden; }
    .ps-track { display:flex; gap:16px; }
    .ps-slide  { flex: 0 0 calc((100% - (var(--pv,5) - 1)*16px) / var(--pv,5)); }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-[#8A00FF] to-[#005BFF] text-slate-900">
<header class="sticky top-0 z-50 backdrop-blur border-b border-white/20 bg-white/10">
  <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 text-white font-semibold">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-white font-bold bg-gradient-to-br from-[#8A00FF] to-[#005BFF]">CM</span>
      Cashback-Market
    </a>
    <nav class="hidden md:flex gap-6 text-white/90">
      <a href="#how">Как это работает</a>
      <a href="#faq">FAQ</a>
      <a href="#legal">Оплата и реквизиты</a>
      <a href="#terms">Пользовательское соглашение</a>
      <a href="#contact">Контакты</a>
    </nav>
    <div class="flex items-center gap-2">
      <?php if ($u): ?>
        <a href="/dashboard/index.php" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF] hover:opacity-90">Личный кабинет</a>
      <?php else: ?>
        <button onclick="openReg()" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white border border-white/40 hover:bg-white/10">Регистрация</button>
        <button onclick="openLogin()" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF] hover:opacity-90">Войти</button>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="mx-auto max-w-6xl px-4">
  <!-- Херо -->
  <section class="pt-16 pb-20 text-white">
    <div class="grid items-center gap-8 md:grid-cols-2">
      <div>
        <h1 class="text-4xl md:text-5xl font-bold leading-tight">Безопасные покупки за кэшбэк/отзыв на маркетплейсах!</h1>
        <p class="mt-4 text-white/90 text-lg">Сумма кэшбэка предусмотренная для покупателя — резервируется продавцом в системе, условия прозрачны, выплаты — в один клик по СБП.</p>
        <div class="mt-6 flex gap-3">
          <a href="/dashboard/index.php" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF] hover:opacity-90">Перейти в ЛК</a>
          <a href="#how" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white border border-white/40 hover:bg-white/10">Как это работает</a>
        </div>
      </div>
      <div>
        <div class="rounded-2xl bg-white p-6 shadow-lg">
          <div class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF]">Функционал площадки</div>
          <h3 class="mt-3 text-xl font-semibold bg-gradient-to-r from-[#8A00FF] to-[#005BFF] bg-clip-text text-transparent">
            Продавец открывает сделку с покупателем, указывает ссылку на товар, условия выкупа и сумму кэшбэка,
            вносит сумму кэшбэка + 100 ₽ комиссии в нашу гарант-систему.
          </h3>
          <p class="mt-3 text-slate-700 leading-relaxed">
            Покупатель получает уведомление, затем выполняет условия и отправляет пруфы.
            После подтверждения продавцом выплата автоматически зачисляется на баланс покупателя в нашей системе.
            Вывод — по СБП. В спорных ситуациях — арбитраж.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- ВИТРИНА (слайдер) -->
  <section class="py-10">
    <h2 class="text-white text-2xl md:text-3xl font-extrabold mb-4">Актуальные товары</h2>

    <?php if ($items): ?>
      <div id="ps" class="relative">
        <button class="ps-btn ps-prev" aria-label="Назад">‹</button>
        <div class="ps-viewport" style="--pv:5">
          <div id="ps-track" class="ps-track transition-transform duration-500">
            <?php foreach ($items as $it): ?>
              <?php
                $id    = (int)$it['id'];
                $title = htmlspecialchars($it['title']);
                $cash  = (int)$it['cashback'];
                $slots = (int)$it['slots'];
                $img   = !empty($it['file_name'])
                          ? '/uploads/listings/'.$it['file_name']
                          : '/assets/img/no-photo.png';
                $view  = '/store/view.php?id='.$id;
              ?>
              <div class="ps-slide">
                <div class="group rounded-2xl overflow-hidden bg-white border shadow-sm hover:shadow transition flex flex-col h-full">
                  <?php if ($u): ?>
                    <a href="<?= $view ?>" class="block">
                  <?php else: ?>
                    <a href="#" onclick="openLogin('<?= htmlspecialchars($view, ENT_QUOTES) ?>'); return false;" class="block">
                  <?php endif; ?>
                      <!-- было aspect-square + object-cover -->
                      <div class="aspect-[3/4] bg-white overflow-hidden">
                        <img src="<?= $img ?>" alt="<?= $title ?>" class="w-full h-full object-contain">
                      </div>
                      <div class="p-3">
                        <div class="text-sm font-semibold text-slate-900 line-clamp-2"><?= $title ?></div>
                        <div class="mt-2 text-xs text-slate-600 space-y-1">
                          <div>Слотов: <span class="font-semibold text-slate-900"><?= $slots ?></span></div>
                          <div>Кэшбэк: <span class="font-semibold text-slate-900">₽ <?= $cash ?></span></div>
                        </div>
                      </div>
                    </a>
                    <div class="p-3 pt-0 mt-auto">
                      <?php if ($u): ?>
                        <a href="<?= $view ?>" class="inline-flex w-full items-center justify-center rounded-full px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF] hover:opacity-90">Купить</a>
                      <?php else: ?>
                        <a href="#" onclick="openLogin('<?= htmlspecialchars($view, ENT_QUOTES) ?>'); return false;" class="inline-flex w-full items-center justify-center rounded-full px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF] hover:opacity-90">Купить</a>
                      <?php endif; ?>
                    </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="ps-btn ps-next" aria-label="Вперёд">›</button>
      </div>
    <?php else: ?>
      <div class="rounded-2xl border border-white/30 bg-white/80 p-6 shadow-sm text-slate-700">Пока нет активных товаров.</div>
    <?php endif; ?>

    <div class="mt-6 flex justify-center">
      <?php if ($u): ?>
        <a href="/store/index.php" class="inline-flex items-center justify-center rounded-full px-6 py-3 font-semibold bg-white text-slate-900 hover:opacity-90">Смотреть все товары</a>
      <?php else: ?>
        <a href="#" onclick="openLogin('/store/index.php'); return false;" class="inline-flex items-center justify-center rounded-full px-6 py-3 font-semibold bg-white text-slate-900 hover:opacity-90">Смотреть все товары</a>
      <?php endif; ?>
    </div>
  </section>

  <!-- Блок «Шаги» -->
  <section id="how" class="py-14">
    <div class="grid gap-4 md:grid-cols-4">
      <div class="rounded-2xl border bg-white p-6"><div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium">Шаг 1</div><h3 class="mt-2 text-lg font-semibold">Создание сделки</h3><p class="text-slate-600">Укажите условия, сумму кэшбэка и контрагента.</p></div>
      <div class="rounded-2xl border bg-white p-6"><div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium">Шаг 2</div><h3 class="mt-2 text-lg font-semibold">Принятие условий</h3><p class="text-slate-600">Покупатель подтверждает условия в системе.</p></div>
      <div class="rounded-2xl border bg-white p-6"><div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium">Шаг 3</div><h3 class="mt-2 text-lg font-semibold">Резерв средств</h3><p class="text-slate-600">Продавец вносит сумму кэшбэка + 100 ₽ комиссии.</p></div>
      <div class="rounded-2xl border bg-white p-6"><div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium">Шаг 4</div><h3 class="mt-2 text-lg font-semibold">Выполнение/выплата</h3><p class="text-slate-600">Покупатель отправляет пруфы, продавец принимает или спорит.</p></div>
    </div>
  </section>

 <section id="faq" class="py-14">
  <div class="rounded-2xl border bg-white p-6">
    <h3 class="text-xl font-semibold">FAQ</h3>

    <details class="mt-4">
      <summary class="cursor-pointer font-medium">Как проходит сделка от начала до конца?</summary>
      <p class="mt-2 text-slate-600">
        Продавец создаёт сделку → другая сторона принимает условия → продавец вносит сумму кэшбэка + 100 ₽ комиссии
        в гарант через ЮKassa → покупатель выполняет условия и отправляет пруфы → продавец принимает
        (или отклоняет/открывает арбитраж) → при принятии кэшбэк автоматически зачисляется покупателю, доступен к выводу по СБП.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Кто и когда вносит деньги в гарант?</summary>
      <p class="mt-2 text-slate-600">
        Сумму кэшбэка и комиссию сервиса (100 ₽) вносит <b>продавец</b> сразу после принятия условий сделки. До оплаты
        покупатель не выполняет задание — так обе стороны защищены.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Безопасно ли оплачивать? Видит ли сайт данные моей карты?</summary>
      <p class="mt-2 text-slate-600">
        Оплата проходит на защищённой платёжной странице <b>ЮKassa</b>. Данные карты сайту не передаются,
        мы получаем только статус платежа. Соответствие требованиям ЮKassa соблюдается.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Что считается «пруфами» выполнения?</summary>
      <p class="mt-2 text-slate-600">
        Скриншоты/чек оплаты, номер заказа/трек, фото полученного товара, ссылка на отзыв (если он требуется правилами
        площадки), переписка с поддержкой и т.п. Конкретный набор указан продавцом в условиях сделки.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Что делать, если продавец отклонил работу?</summary>
      <p class="mt-2 text-slate-600">
        Продавец должен указать причину. Покупатель может доработать и отправить снова. Если стороны не согласны —
        доступен <b>арбитраж</b>. На время разбирательства деньги остаются в гаранте.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Сколько идёт выплата кэшбэка покупателю?</summary>
      <p class="mt-2 text-slate-600">
        Сразу после принятия продавцом — сумма зачисляется на баланс в системе. Вывод по СБП занимает
        обычно от минут до 1 рабочего дня (зависит от банка).
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Можно ли менять условия уже созданной сделки?</summary>
      <p class="mt-2 text-slate-600">
        Нет, после принятия условий менять их нельзя. Если условия требуют правок — текущую сделку отменяют
        и создают новую с корректными параметрами.
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Какие комиссии и кто их платит?</summary>
      <p class="mt-2 text-slate-600">
        Комиссия сервиса фиксированная — <b>100 ₽</b> за сделку, оплачивает продавец при внесении кэшбэка в гарант.
        Покупатель комиссию сервису не платит.
      </p>
    </details>

  

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Какие товары допускаются и что запрещено?</summary>
      <p class="mt-2 text-slate-600">
        Разрешены обычные потребительские товары. Запрещены категории, нарушающие закон и правила платёжных систем
        (алкоголь, табак, БАДы с недопустимыми заявлениями, оружие/аксессуары, взрослый контент и т.п.).
      </p>
    </details>

    <details class="mt-3">
      <summary class="cursor-pointer font-medium">Как искать объявления на витрине?</summary>
      <p class="mt-2 text-slate-600">
        На странице «Витрина» доступен поиск по ключевым словам и фильтры: по категории и диапазону кэшбэка.
        В ближайшие обновления добавим дополнительные фильтры.
      </p>
    </details>

  </div>
</section>


  <!-- ЮKassa / Оплата / Реквизиты -->
  <section id="legal" class="py-14">
    <h2 class="text-2xl font-semibold text-white mb-4">Оплата, тарифы и реквизиты</h2>

    <div class="grid gap-4 md:grid-cols-3">
      <!-- Тарифы -->
      <div class="rounded-2xl border bg-white p-6">
        <h3 class="text-lg font-semibold">Тарифы сервиса</h3>
        <ul class="mt-3 text-slate-700 list-disc pl-5 space-y-1">
          <li>Оплата кэшбэка резервируется продавцом в гарант-системе.</li>
          <li>Комиссия сервиса: <b>100&nbsp;₽</b> (оплачивает продавец при пополнении гаранта).</li>
          <li>Вывод средств покупателю — по СБП после подтверждения продавцом.</li>
        </ul>
        <p class="mt-3 text-xs text-slate-500">
          Указанные тарифы действуют для онлайн-платежей на сайте cashback-market.ru и могут меняться. Актуальная версия публикуется здесь.
        </p>
      </div>

      <!-- Процесс оплаты -->
      <div class="rounded-2xl border bg-white p-6">
        <h3 class="text-lg font-semibold">Как проходит оплата</h3>
        <ol class="mt-3 text-slate-700 list-decimal pl-5 space-y-1">
          <li>Продавец принимает условия сделки и переходит к оплате гаранта.</li>
          <li>Оплата проводится на защищённой платёжной странице <b>ЮKassa</b> (карты, SberPay и др.). Данные карт сайту не передаются.</li>
          <li>После оплаты система возвращает на сайт и обновляет статус сделки автоматически.</li>
        </ol>
        <div class="mt-3 text-sm text-slate-700">
          Возвраты: при отмене сделки до выплаты покупателю средства возвращаются продавцу тем же способом, которым оплачивались, за вычетом комиссии сервиса.
        </div>
      </div>

      <!-- Реквизиты -->
      <div class="rounded-2xl border bg-white p-6">
        <h3 class="text-lg font-semibold">Реквизиты</h3>
        <div class="mt-3 text-slate-700 space-y-1">
          <div><b>ИП Еганян Агаси Гагикович</b></div>
          <div>ИНН: <span class="tabular-nums">773773777139</span></div>
        
        </div>
        <div class="mt-4 text-slate-700">
          Контакты: <a class="underline" href="mailto:support@cashback-market.ru">support@cashback-market.ru</a>,
          Telegram: <a class="underline" href="https://t.me/agassi_e" target="_blank" rel="noreferrer">@agassi_e</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Пользовательское соглашение (кратко) -->
  <section id="terms" class="pb-14">
    <div class="rounded-2xl border bg-white p-6">
      <h3 class="text-lg font-semibold">Пользовательское соглашение (кратко)</h3>
      <p class="mt-2 text-slate-700">
        Используя сайт cashback-market.ru, вы принимаете условия сервиса: правила размещения объявлений,
        порядок заключения и исполнения сделок «Продавец–Покупатель», комиссии сервиса, порядок выплат и возвратов,
        а также политику конфиденциальности.
      </p>
      <details class="mt-3">
        <summary class="cursor-pointer font-medium">Развернуть основные положения</summary>
        <ul class="mt-2 text-slate-700 list-disc pl-5 space-y-1">
          <li>Сервис предоставляет техническую возможность заключать сделки и резервировать кэшбэк.</li>
          <li>Продавец вносит сумму кэшбэка и комиссию сервиса до начала выполнения.</li>
          <li>Покупатель предоставляет подтверждения выполнения условий выкупа.</li>
          <li>Выплата кэшбэка — после подтверждения продавцом либо по итогам арбитража.</li>
          <li>Споры рассматриваются через арбитраж сервиса на основании предоставленных доказательств.</li>
        </ul>
      </details>
      <p class="mt-3 text-sm text-slate-500">
        Полные версии документов будут опубликованы по ссылкам:
        <a class="underline" href="/legal/oferta.html">Оферта</a>,
        <a class="underline" href="/legal/privacy.html">Политика конфиденциальности</a>.
      </p>
    </div>
  </section>

  <section id="contact" class="py-14">
    <div class="rounded-2xl border bg-white p-6">
      <h3 class="text-xl font-semibold">Контакты</h3>
      <p class="mt-2 text-slate-700">Telegram: <a class="underline" href="https://t.me/agassi_e" target="_blank" rel="noreferrer">@agassi_e</a></p>
    </div>
  </section>
</main>

<footer class="border-t border-white/20 bg-white/10 text-white/80">
  <div class="mx-auto max-w-6xl px-4 py-6 text-sm">© <?php echo date('Y'); ?> Cashback-Market</div>
</footer>

<?php if (!$u): ?>
<!-- Login Modal -->
<dialog id="login" class="rounded-2xl p-0 w-[95vw] max-w-md">
  <form method="post" action="/auth/login.php" class="p-6 space-y-4">
    <h3 class="text-xl font-semibold">Войти</h3>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="next" id="loginNext" value="">
    <div><label class="block text-sm">Логин</label><input name="login" required class="w-full border rounded-xl px-3 py-2"></div>
    <div><label class="block text-sm">Пароль</label><input type="password" name="password" required class="w-full border rounded-xl px-3 py-2"></div>
    <div class="flex gap-2">
      <button class="rounded-2xl px-4 py-2 text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF]">Войти</button>
      <button type="button" onclick="closeLogin()" class="rounded-2xl px-4 py-2 border">Отмена</button>
    </div>
    <div class="text-sm text-slate-600">
      Нет аккаунта?
      <button type="button" class="underline" onclick="openRegFromLogin()">Регистрация</button>
    </div>
  </form>
</dialog>

<!-- Register Modal -->
<dialog id="reg" class="rounded-2xl p-0 w-[95vw] max-w-md">
  <form method="post" action="/auth/register.php" class="p-6 space-y-4">
    <h3 class="text-xl font-semibold">Регистрация</h3>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="next" id="regNext" value="">
    <div><label class="block text-sm">Логин</label><input name="login" required class="w-full border rounded-xl px-3 py-2"></div>
    <div><label class="block text-sm">Пароль</label><input type="password" name="password" required class="w-full border rounded-xl px-3 py-2"></div>
    <div><label class="block text-sm">Email</label><input type="email" name="email" required class="w-full border rounded-xl px-3 py-2"></div>
    <div><label class="block text-sm">Вы кто?</label>
      <select name="role" required class="w-full border rounded-xl px-3 py-2">
        <option value="SELLER">Продавец</option>
        <option value="BUYER">Покупатель</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button class="rounded-2xl px-4 py-2 text-white bg-gradient-to-r from-[#8A00FF] to-[#005BFF]">Зарегистрироваться</button>
      <button type="button" onclick="closeReg()" class="rounded-2xl px-4 py-2 border">Отмена</button>
    </div>
    <div class="text-sm text-slate-600">
      Уже есть аккаунт?
      <button type="button" class="underline" onclick="openLoginFromReg()">Войти</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<!-- === Функции модалок (всегда подключены) === -->
<script>
  function openLogin(next){
    const dlg = document.getElementById('login');
    const field = document.getElementById('loginNext');
    if (field) field.value = next || '';
    if (dlg && typeof dlg.showModal === 'function') dlg.showModal();
  }
  function closeLogin(){
    const d = document.getElementById('login'); if (d && typeof d.close === 'function') d.close();
  }
  function openReg(next){
    const dlg = document.getElementById('reg');
    const field = document.getElementById('regNext');
    if (field) field.value = next || '';
    if (dlg && typeof dlg.showModal === 'function') dlg.showModal();
  }
  function closeReg(){
    const d = document.getElementById('reg'); if (d && typeof d.close === 'function') d.close();
  }
  function openRegFromLogin(){
    const n = document.getElementById('loginNext')?.value || '';
    closeLogin(); openReg(n);
  }
  function openLoginFromReg(){
    const n = document.getElementById('regNext')?.value || '';
    closeReg(); openLogin(n);
  }
</script>

<!-- === Слайдер: всегда подключаем, со свайпом/drag === -->
<script>
(function(){
  const wrap  = document.getElementById('ps');
  const viewport = wrap?.querySelector('.ps-viewport');
  const track = document.getElementById('ps-track');
  if(!wrap || !viewport || !track) return;

  const GAP = 16;
  let pv = 5;          // per view (2/3/5)
  let cur = 0;         // индекс с учётом клонов
  let allow = true;    // блокировка во время анимации
  const originalHTML = track.innerHTML;

  function calcPV(){
    const w = window.innerWidth;
    if (w >= 1024) return 5;
    if (w >= 640)  return 3;
    return 2;
  }
  function stepSize(){
    const first = track.querySelector('.ps-slide');
    return first ? first.getBoundingClientRect().width + GAP : 0;
  }
  function setTranslate(px, withTransition){
    if (!withTransition) track.style.transition = 'none';
    track.style.transform = `translateX(${px}px)`;
    if (!withTransition) requestAnimationFrame(()=>{ track.style.transition = 'transform .5s'; });
  }

  function setup(){
    track.style.transition = 'none';
    track.innerHTML = originalHTML;

    pv = calcPV();
    viewport.style.setProperty('--pv', pv);

    const originals = Array.from(track.children);
    const head = originals.slice(0, pv).map(n=>n.cloneNode(true));
    const tail = originals.slice(-pv).map(n=>n.cloneNode(true));
    head.forEach(n=>track.appendChild(n));
    tail.reverse().forEach(n=>track.insertBefore(n, track.firstChild));

    cur = pv;
    setTranslate(-cur*stepSize(), false);
  }

  function go(dir){
    if(!allow) return;
    allow = false;
    cur += dir;
    setTranslate(-cur*stepSize(), true);
  }

  track.addEventListener('transitionend', ()=>{
    const total = track.children.length;
    const real = total - 2*pv;
    const step = stepSize();

    if (cur >= pv + real){
      track.style.transition = 'none';
      cur = pv;
      track.style.transform = `translateX(${-cur*step}px)`;
      requestAnimationFrame(()=>{ track.style.transition = 'transform .5s'; });
    } else if (cur < pv){
      track.style.transition = 'none';
      cur = pv + real - 1;
      track.style.transform = `translateX(${-cur*step}px)`;
      requestAnimationFrame(()=>{ track.style.transition = 'transform .5s'; });
    }
    allow = true;
  });

  wrap.querySelector('.ps-prev')?.addEventListener('click', ()=>go(-1));
  wrap.querySelector('.ps-next')?.addEventListener('click', ()=>go( 1));

  /* ====== SWIPE / DRAG ====== */
  let isDown = false;
  let startX = 0;
  let startTx = 0;
  let dragged = false;

  function onDown(x){
    if (!allow) return;
    isDown = true;
    dragged = false;
    startX = x;
    startTx = -cur * stepSize();
    track.style.transition = 'none';
  }
  function onMove(x){
    if (!isDown) return;
    const dx = x - startX;
    if (Math.abs(dx) > 5) dragged = true;
    track.style.transform = `translateX(${startTx + dx}px)`;
  }
  function onUp(x){
    if (!isDown) return;
    isDown = false;
    const dx = (x ?? startX) - startX;
    const threshold = Math.min(120, stepSize() * 0.2); // 20% шага (но не больше 120px)
    track.style.transition = 'transform .5s';

    if (Math.abs(dx) > threshold){
      go(dx < 0 ? 1 : -1);
    } else {
      setTranslate(-cur*stepSize(), true);
    }
  }

  // touch
  viewport.addEventListener('touchstart', (e)=>onDown(e.touches[0].clientX), {passive:true});
  viewport.addEventListener('touchmove',  (e)=>{ onMove(e.touches[0].clientX); }, {passive:true});
  viewport.addEventListener('touchend',   (e)=>onUp(e.changedTouches[0]?.clientX));

  // mouse
  viewport.addEventListener('mousedown', (e)=>onDown(e.clientX));
  window.addEventListener('mousemove',   (e)=>onMove(e.clientX));
  window.addEventListener('mouseup',     (e)=>onUp(e.clientX));
  viewport.addEventListener('mouseleave',()=>{ if(isDown) onUp(); });

  // блокируем клики, если было перетаскивание
  track.addEventListener('click', (e)=>{ if (dragged) { e.preventDefault(); e.stopPropagation(); } });

  // init + resize
  setup();
  let rto;
  window.addEventListener('resize', ()=>{ clearTimeout(rto); rto = setTimeout(setup, 150); });
})();
</script>

</body>
</html>
