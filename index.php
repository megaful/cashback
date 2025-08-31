<?php require_once __DIR__.'/includes/config.php'; $u = current_user(); ?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashback-Market — безопасные сделки по покупке товаров за отзыв/кэшбэк</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-b from-[#8A00FF] to-[#005BFF] text-slate-900">
<header class="sticky top-0 z-50 backdrop-blur border-b border-white/20 bg-white/10">
  <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 text-white font-semibold">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-black text-white">CM</span> Cashback-Market
    </a>
    <nav class="hidden md:flex gap-6 text-white/90">
      <a href="#how">Как это работает</a>
      <a href="#faq">FAQ</a>
      <a href="#contact">Контакты</a>
    </nav>
    <div class="flex items-center gap-2">
      <?php if ($u): ?>
        <a href="/dashboard/index.php" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold bg-black text-white hover:opacity-90">Личный кабинет</a>
      <?php else: ?>
        <button onclick="document.getElementById('reg').showModal()" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white border border-white/40 hover:bg-white/10">Регистрация</button>
        <button onclick="document.getElementById('login').showModal()" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold bg-black text-white hover:opacity-90">Войти</button>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="mx-auto max-w-6xl px-4">
  <section class="pt-16 pb-20 text-white">
    <div class="grid items-center gap-8 md:grid-cols-2">
      <div>
        <h1 class="text-4xl md:text-5xl font-bold leading-tight">Безопасные покупки за кэшбэк/отзыв на маркетплейсах!</h1>
        <p class="mt-4 text-white/90 text-lg">Сумма кэшбэка предусмотренная для покупателя - резервируется продвцом в системе, условия прозрачны, выплаты — в один клик по СБП.</p>
        <div class="mt-6 flex gap-3">
          <a href="/dashboard/index.php" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold bg-black text-white hover:opacity-90">Перейти в ЛК</a>
          <a href="#how" class="inline-flex items-center justify-center rounded-2xl px-5 py-3 font-semibold text-white border border-white/40 hover:bg-white/10">Как это работает</a>
        </div>
      </div>
      <div>
        <div class="rounded-2xl border border-white/30 bg-white/80 p-6 shadow-sm">
          <div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium border-white/40 mb-3">Функционал площадки</div>
          <h3 class="text-xl font-semibold">Продавец открывает сделку с покупателем, указывает ссылку на товар, условия выкупа и сумму кэшбэка, вносит сумму кэшбэка + 100 ₽ комиссии в нашу гарант-систему.</h3>
          <p class="mt-2 text-slate-700">Покупатель получает уведомление, что продавец внес в гарант-систему необходимую сумму для кэшбэка, затем покупатель выполняет условия, отправляет пруфы, продавец подтверждает — только после этого выплата зачисляется автоматически на баланс покупателя в нашей системе. Покупатель может в любой момент вывести средства с нашей системы на свою банковскую карту по СБП. В спорных ситуациях стороны могут открыть арбитраж.</p>
        </div>
      </div>
    </div>
  </section>

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
      <details class="mt-4"><summary class="cursor-pointer font-medium">Вы требуете положительные отзывы?</summary><p class="mt-2 text-slate-600">Нет. Запрещено требование «только положительный отзыв». Соблюдаем правила площадок.</p></details>
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
    <div><label class="block text-sm">Логин</label><input name="login" required class="w-full border rounded-xl px-3 py-2"></div>
    <div><label class="block text-sm">Пароль</label><input type="password" name="password" required class="w-full border rounded-xl px-3 py-2"></div>
    <div class="flex gap-2">
      <button class="rounded-2xl px-4 py-2 bg-black text-white">Войти</button>
      <button type="button" onclick="document.getElementById('login').close()" class="rounded-2xl px-4 py-2 border">Отмена</button>
    </div>
  </form>
</dialog>

<!-- Register Modal -->
<dialog id="reg" class="rounded-2xl p-0 w-[95vw] max-w-md">
  <form method="post" action="/auth/register.php" class="p-6 space-y-4">
    <h3 class="text-xl font-semibold">Регистрация</h3>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
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
      <button class="rounded-2xl px-4 py-2 bg-black text-white">Зарегистрироваться</button>
      <button type="button" onclick="document.getElementById('reg').close()" class="rounded-2xl px-4 py-2 border">Отмена</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

</body>
</html>
