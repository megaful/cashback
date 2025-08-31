
Добавлен модуль ОБЪЯВЛЕНИЙ/ВИТРИНЫ.

Файлы:
- sql/2025-08-30-listings.sql : миграция БД
- includes/listings_lib.php : хелперы
- store/* : витрина и карточка
- seller/listings/* : кабинет продавца (объявления)
- admin/listings.php : модерация объявлений
- deals/create_from_listing_patch.txt : инструкция для связи с create.php

Шаги установки:
1) Импортировать SQL.
2) Скопировать файлы в проект.
3) Добавить ссылку на /store/index.php в главном меню.
4) Внести правки в deals/create.php по инструкции.

