-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Сен 01 2025 г., 22:46
-- Версия сервера: 5.7.35-38
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cv96936_crowd`
--

-- --------------------------------------------------------

--
-- Структура таблицы `balances`
--

CREATE TABLE IF NOT EXISTS `balances` (
  `user_id` int(11) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `balances`
--

INSERT INTO `balances` (`user_id`, `balance`) VALUES
(1, 0),
(2, 0),
(3, 0),
(4, 0),
(5, 300);

-- --------------------------------------------------------

--
-- Структура таблицы `deals`
--

CREATE TABLE IF NOT EXISTS `deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(20) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL,
  `product_url` varchar(500) NOT NULL,
  `cashback` int(11) NOT NULL,
  `commission` int(11) NOT NULL DEFAULT '100',
  `terms_text` text NOT NULL,
  `moderation_mode` enum('none','full') NOT NULL DEFAULT 'none',
  `status` enum('PENDING_ACCEPTANCE','AWAITING_FUNDING','FUNDED','IN_PROGRESS','SUBMITTED','ACCEPTED','REJECTED','DISPUTE_OPENED','RESOLVED_ACCEPTED','RESOLVED_REJECTED') NOT NULL DEFAULT 'PENDING_ACCEPTANCE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `listing_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  KEY `seller_id` (`seller_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `fk_deals_listing` (`listing_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `deals`
--

INSERT INTO `deals` (`id`, `number`, `seller_id`, `buyer_id`, `created_by`, `title`, `product_url`, `cashback`, `commission`, `terms_text`, `moderation_mode`, `status`, `created_at`, `updated_at`, `listing_id`) VALUES
(29, 'СДЕЛКА-000029', 4, 5, 4, 'Фитолампа', 'https://www.wildberries.ru/catalog/158906165/detail.aspx', 300, 100, '1. заказать\r\n2.', 'none', 'ACCEPTED', '2025-08-30 17:08:46', '2025-08-30 17:11:41', NULL),
(30, 'СДЕЛКА-000030', 2, 3, 3, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 100, '123', 'none', 'ACCEPTED', '2025-08-30 20:17:11', '2025-08-30 21:01:13', 1),
(31, 'СДЕЛКА-000031', 2, 3, 3, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 100, 'sadasd', 'none', 'ACCEPTED', '2025-08-30 23:51:02', '2025-08-31 11:22:36', 30),
(34, 'СДЕЛКА-000034', 2, 3, 2, 'Фен', 'https://www.wildberries.ru/catalog/368756070/detail.aspx', 400, 100, '1. Заказать товар\r\n2. Выкупить на ПВЗ\r\n3. Оставить отзыв с фото и текстом и оценкой 5 звезд\r\n4. Прикрепить скрины пруфов выкупа и отзыва в чат сделки', 'none', 'RESOLVED_ACCEPTED', '2025-09-01 08:53:33', '2025-09-01 09:12:26', NULL),
(35, 'СДЕЛКА-000035', 2, 3, 2, 'Фен', 'https://www.wildberries.ru/catalog/368756070/detail.aspx', 400, 100, '12323', 'none', 'RESOLVED_ACCEPTED', '2025-09-01 09:19:30', '2025-09-01 09:20:19', NULL),
(37, 'СДЕЛКА-000037', 2, 3, 3, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 400, 100, '12312', 'none', 'ACCEPTED', '2025-09-01 09:33:40', '2025-09-01 09:34:30', NULL),
(40, 'СДЕЛКА-000040', 2, 3, 2, 'Фен', 'https://www.wildberries.ru/catalog/368756070/detail.aspx', 400, 100, '123213', 'none', 'ACCEPTED', '2025-09-01 09:44:46', '2025-09-01 09:45:12', NULL),
(41, 'СДЕЛКА-000041', 2, 3, 3, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 400, 100, '214124', 'none', 'RESOLVED_REJECTED', '2025-09-01 10:12:24', '2025-09-01 10:12:56', NULL),
(42, 'СДЕЛКА-000042', 2, 3, 2, 'Фен', 'https://www.wildberries.ru/catalog/368756070/detail.aspx', 100, 100, '123', 'none', 'AWAITING_FUNDING', '2025-09-01 12:42:41', '2025-09-01 12:42:47', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `deal_attachments`
--

CREATE TABLE IF NOT EXISTS `deal_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `deal_attachments`
--

INSERT INTO `deal_attachments` (`id`, `message_id`, `file_path`, `mime`, `size`, `created_at`) VALUES
(9, 121, '/uploads/08021079cc497b81.png', 'image/png', 357745, '2025-08-30 17:10:56'),
(10, 130, '/uploads/b7d74bb6985251c63215b692f50fbbc6.png', 'image/png', 1587190, '2025-08-30 20:49:41'),
(11, 130, '/uploads/7e4de806830035c5c0f54cfde07581a0.png', 'image/png', 357745, '2025-08-30 20:49:41'),
(12, 130, '/uploads/fef5264d81ae7bfb1020d5134282b293.png', 'image/png', 1362516, '2025-08-30 20:49:41'),
(13, 130, '/uploads/0333e6c161a5445dc3c9081a8df73ec3.png', 'image/png', 1031636, '2025-08-30 20:49:41'),
(14, 130, '/uploads/ab76dff789200bd735b99f4b33e3b3e4.png', 'image/png', 1075611, '2025-08-30 20:49:41'),
(15, 145, '/uploads/1a152f35f148ed27320c12726e96a528.png', 'image/png', 560087, '2025-09-01 08:54:07'),
(16, 166, '/uploads/fefb25520099a1f5dbcdc35d7c1f6f01.png', 'image/png', 357745, '2025-09-01 09:33:57'),
(17, 167, '/uploads/2275042d52c243403e246b41e69d12d8.png', 'image/png', 1362516, '2025-09-01 09:34:01');

-- --------------------------------------------------------

--
-- Структура таблицы `deal_messages`
--

CREATE TABLE IF NOT EXISTS `deal_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deal_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `deal_id` (`deal_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `deal_messages`
--

INSERT INTO `deal_messages` (`id`, `deal_id`, `sender_id`, `text`, `created_at`) VALUES
(116, 29, 4, 'Создана новая сделка СДЕЛКА-000029. Вторая сторона должна принять условия.', '2025-08-30 17:08:46'),
(117, 29, 5, 'Условия приняты адресатом. Продавцу нужно внести сумму кэшбэка + 100 ₽ комиссии.', '2025-08-30 17:09:38'),
(118, 29, 5, 'опарав', '2025-08-30 17:09:47'),
(119, 29, 4, 'прроро', '2025-08-30 17:09:55'),
(120, 29, 4, 'Продавец внёс сумму. Покупатель может приступать к выполнению.', '2025-08-30 17:10:20'),
(121, 29, 5, 'готово', '2025-08-30 17:10:56'),
(122, 29, 4, 'Продавец принял работу. Кэшбэк зачислён на баланс покупателя.', '2025-08-30 17:11:41'),
(123, 30, 3, 'Сделка СДЕЛКА-000030 создана по объявлению #1. Продавец должен подтвердить условия.', '2025-08-30 20:17:11'),
(124, 30, 2, 'ку', '2025-08-30 20:21:48'),
(125, 30, 3, 'rere', '2025-08-30 20:34:05'),
(126, 30, 2, 'Продавец принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-08-30 20:34:16'),
(127, 30, 2, 'Продавец внёс в гарант ₽ 600. Сделка переведена в статус «Оплачено».', '2025-08-30 20:40:31'),
(129, 30, 3, 'sds', '2025-08-30 20:45:28'),
(130, 30, 3, 'resw', '2025-08-30 20:49:41'),
(131, 30, 3, 'Покупатель отправил работу на проверку. Продавец может принять или открыть арбитраж.', '2025-08-30 20:50:01'),
(132, 30, 2, 'Продавец подтвердил выполнение. Покупателю зачислено ₽ 500.', '2025-08-30 21:01:13'),
(133, 31, 3, 'Создана заявка по объявлению (авто). Продавец должен подтвердить условия.', '2025-08-30 23:51:02'),
(134, 31, 2, 'Продавец принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-08-31 11:22:15'),
(135, 31, 2, 'Продавец внёс в гарант ₽ 600. Сделка переведена в статус «Оплачено».', '2025-08-31 11:22:27'),
(136, 31, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-08-31 11:22:32'),
(137, 31, 2, 'Продавец подтвердил выполнение. Покупателю зачислено ₽ 500.', '2025-08-31 11:22:36'),
(142, 34, 2, 'Создана новая сделка СДЕЛКА-000034. Вторая сторона должна принять условия.', '2025-09-01 08:53:33'),
(143, 34, 3, '123', '2025-09-01 08:53:51'),
(144, 34, 2, '421', '2025-09-01 08:53:54'),
(145, 34, 3, '', '2025-09-01 08:54:07'),
(146, 34, 3, 'Покупатель принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 08:54:21'),
(147, 34, 2, 'Продавец внёс в гарант ₽ 500. Сделка переведена в статус «Оплачено».', '2025-09-01 08:54:47'),
(148, 34, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 08:54:57'),
(149, 34, 2, 'Продавец отклонил работу. Средства остаются в гарант-счёте. Покупатель может доработать и отправить снова.', '2025-09-01 08:55:07'),
(150, 34, 3, '12321', '2025-09-01 08:55:47'),
(151, 34, 3, 'Открыт арбитраж: 123', '2025-09-01 09:05:42'),
(152, 35, 2, 'Создана новая сделка СДЕЛКА-000035. Вторая сторона должна принять условия.', '2025-09-01 09:19:30'),
(153, 35, 3, 'Покупатель принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 09:19:37'),
(154, 35, 3, '12312', '2025-09-01 09:19:42'),
(155, 35, 2, 'Продавец внёс в гарант ₽ 500. Сделка переведена в статус «Оплачено».', '2025-09-01 09:19:49'),
(156, 35, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 09:19:59'),
(157, 35, 2, 'Продавец отклонил работу: 123. Средства остаются в гарант-счёте. Покупатель может доработать и отправить снова.', '2025-09-01 09:20:06'),
(158, 35, 3, 'Открыт арбитраж: 123', '2025-09-01 09:20:10'),
(159, 35, 1, 'Администратор вынес решение по арбитражу: Удовлетворить (зачислить покупателю).\nКомментарий администратора: Вот так', '2025-09-01 09:20:19'),
(162, 37, 3, 'Создана новая сделка СДЕЛКА-000037. Вторая сторона должна принять условия.', '2025-09-01 09:33:40'),
(163, 37, 2, 'Продавец принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 09:33:48'),
(164, 37, 3, '213', '2025-09-01 09:33:51'),
(165, 37, 2, '12321', '2025-09-01 09:33:53'),
(166, 37, 2, '', '2025-09-01 09:33:57'),
(167, 37, 3, '', '2025-09-01 09:34:01'),
(168, 37, 2, 'Продавец внёс в гарант ₽ 500. Сделка переведена в статус «Оплачено».', '2025-09-01 09:34:18'),
(169, 37, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 09:34:25'),
(170, 37, 2, 'Продавец подтвердил выполнение. Покупателю зачислено ₽ 400.', '2025-09-01 09:34:30'),
(182, 40, 2, 'Создана новая сделка СДЕЛКА-000040. Вторая сторона должна принять условия.', '2025-09-01 09:44:46'),
(183, 40, 3, 'Покупатель принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 09:44:50'),
(184, 40, 2, 'Продавец внёс в гарант ₽ 500. Сделка переведена в статус «Оплачено».', '2025-09-01 09:44:51'),
(185, 40, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 09:44:57'),
(186, 40, 2, 'Продавец отклонил работу: 123213. Средства остаются в гарант-счёте. Покупатель может доработать и отправить снова.', '2025-09-01 09:45:00'),
(187, 40, 3, 'Покупатель повторно отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 09:45:04'),
(188, 40, 2, 'Продавец отклонил работу: 21321. Средства остаются в гарант-счёте. Покупатель может доработать и отправить снова.', '2025-09-01 09:45:06'),
(189, 40, 3, 'Покупатель повторно отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 09:45:08'),
(190, 40, 2, 'Продавец подтвердил выполнение. Покупателю зачислено ₽ 400.', '2025-09-01 09:45:12'),
(191, 41, 3, 'Создана новая сделка СДЕЛКА-000041. Вторая сторона должна принять условия.', '2025-09-01 10:12:24'),
(192, 41, 2, 'Продавец принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 10:12:34'),
(193, 41, 2, 'Продавец внёс в гарант ₽ 500. Сделка переведена в статус «Оплачено».', '2025-09-01 10:12:36'),
(194, 41, 3, 'Покупатель отправил работу на проверку. Продавец может принять, отклонить или открыть арбитраж.', '2025-09-01 10:12:43'),
(195, 41, 2, 'Открыт арбитраж: 123', '2025-09-01 10:12:47'),
(196, 41, 1, 'Администратор вынес решение по арбитражу: Отклонить (возврат продавцу).\nКомментарий администратора: 123', '2025-09-01 10:12:56'),
(197, 42, 2, 'Создана новая сделка СДЕЛКА-000042. Вторая сторона должна принять условия.', '2025-09-01 12:42:41'),
(198, 42, 3, 'Покупатель принял условия. Ожидается оплата в гарант (сумма кэшбэка + комиссия ₽100).', '2025-09-01 12:42:47');

-- --------------------------------------------------------

--
-- Структура таблицы `disputes`
--

CREATE TABLE IF NOT EXISTS `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deal_id` int(11) NOT NULL,
  `opened_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
  `resolution` enum('ACCEPTED','REJECTED') DEFAULT NULL,
  `admin_comment` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  `winner_user_id` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deal_id` (`deal_id`),
  KEY `opened_by` (`opened_by`),
  KEY `idx_winner` (`winner_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `disputes`
--

INSERT INTO `disputes` (`id`, `deal_id`, `opened_by`, `reason`, `status`, `resolution`, `admin_comment`, `created_at`, `closed_at`, `winner_user_id`, `resolved_at`) VALUES
(8, 34, 3, '123', 'RESOLVED', 'ACCEPTED', 'ответ', '2025-09-01 09:05:42', '2025-09-01 09:12:26', NULL, NULL),
(9, 35, 3, '123', 'RESOLVED', 'ACCEPTED', 'Вот так', '2025-09-01 09:20:10', '2025-09-01 09:20:19', NULL, NULL),
(10, 41, 2, '123', 'RESOLVED', 'REJECTED', '123', '2025-09-01 10:12:47', '2025-09-01 10:12:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `listings`
--

CREATE TABLE IF NOT EXISTS `listings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `product_url` varchar(500) NOT NULL,
  `cashback_rub` int(11) NOT NULL DEFAULT '0',
  `category` varchar(100) NOT NULL,
  `quantity_limit` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `status` enum('PENDING','ACTIVE','REJECTED','ARCHIVED') DEFAULT 'PENDING',
  `reject_reason` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remaining` int(11) DEFAULT NULL,
  `cashback` int(11) NOT NULL DEFAULT '0',
  `slots` int(11) NOT NULL DEFAULT '1',
  `reason` text,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `idx_listings_remaining` (`remaining`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `listings`
--

INSERT INTO `listings` (`id`, `seller_id`, `title`, `product_url`, `cashback_rub`, `category`, `quantity_limit`, `description`, `status`, `reject_reason`, `created_at`, `updated_at`, `remaining`, `cashback`, `slots`, `reason`) VALUES
(1, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '123', 'ARCHIVED', NULL, '2025-08-30 19:53:27', '2025-08-30 21:40:23', NULL, 0, 1, NULL),
(2, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '2313', 'ARCHIVED', NULL, '2025-08-30 21:28:26', '2025-08-30 21:40:14', NULL, 0, 1, NULL),
(3, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '2313', 'ARCHIVED', NULL, '2025-08-30 21:28:40', '2025-08-30 21:40:11', NULL, 0, 1, NULL),
(4, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '1245', 'ARCHIVED', NULL, '2025-08-30 21:32:26', '2025-08-30 21:40:05', NULL, 0, 1, NULL),
(5, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '167632', 'ARCHIVED', NULL, '2025-08-30 21:33:34', '2025-08-30 21:40:09', NULL, 0, 1, NULL),
(6, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '167632', 'ARCHIVED', NULL, '2025-08-30 21:33:44', '2025-08-30 21:34:11', NULL, 0, 1, NULL),
(7, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'home', 50, '15125', 'ARCHIVED', NULL, '2025-08-30 21:40:35', '2025-08-30 21:59:15', NULL, 0, 1, NULL),
(8, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'beauty', 50, 'sdasdsa', 'ARCHIVED', NULL, '2025-08-30 21:41:08', '2025-08-30 21:46:08', NULL, 0, 1, NULL),
(9, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 500, 'beauty', 50, 'hhfwsr5325r2', 'ARCHIVED', NULL, '2025-08-30 21:45:45', '2025-08-30 21:46:03', NULL, 0, 1, NULL),
(10, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'sdfasdasd', 'REJECTED', '', '2025-08-30 21:59:00', '2025-08-30 21:59:34', NULL, 500, 50, NULL),
(11, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'vxzvfaszfd', 'REJECTED', '', '2025-08-30 21:59:20', '2025-08-30 21:59:33', NULL, 500, 50, NULL),
(12, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'xdxgsdgh', 'ARCHIVED', NULL, '2025-08-30 21:59:42', '2025-08-30 22:25:22', NULL, 500, 50, NULL),
(13, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'gsda', 'ARCHIVED', NULL, '2025-08-30 22:17:31', '2025-08-30 22:25:19', NULL, 500, 50, NULL),
(14, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'ппвфыа', 'REJECTED', NULL, '2025-08-30 22:25:26', '2025-08-30 22:25:30', NULL, 500, 50, NULL),
(15, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'asdasd', 'REJECTED', NULL, '2025-08-30 22:29:03', '2025-08-30 22:29:09', NULL, 500, 50, NULL),
(16, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'fdadfsasd', 'REJECTED', NULL, '2025-08-30 22:34:13', '2025-08-30 22:34:19', NULL, 500, 50, NULL),
(17, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'sdfsadfasd', 'ARCHIVED', NULL, '2025-08-30 22:34:38', '2025-08-30 23:43:26', NULL, 500, 50, NULL),
(18, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'asffgasf', 'REJECTED', NULL, '2025-08-30 22:42:21', '2025-08-30 22:42:29', NULL, 500, 50, 'asd1'),
(19, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'sgasfg', 'ARCHIVED', NULL, '2025-08-30 22:48:07', '2025-08-30 23:43:23', NULL, 500, 50, NULL),
(20, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, '6123', 'ARCHIVED', NULL, '2025-08-30 22:59:45', '2025-08-30 23:43:19', NULL, 500, 50, NULL),
(21, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'asdasd', 'ARCHIVED', NULL, '2025-08-30 23:05:19', '2025-08-30 23:43:16', NULL, 500, 50, NULL),
(22, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'asdasdas', 'ARCHIVED', NULL, '2025-08-30 23:10:59', '2025-08-30 23:43:12', NULL, 500, 50, NULL),
(23, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, '214124', 'ARCHIVED', NULL, '2025-08-30 23:14:55', '2025-08-30 23:43:09', NULL, 500, 50, NULL),
(24, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'фывфыв', 'ARCHIVED', NULL, '2025-08-30 23:17:56', '2025-08-30 23:43:05', NULL, 500, 50, NULL),
(25, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, '123123', 'ARCHIVED', NULL, '2025-08-30 23:21:29', '2025-08-30 23:42:52', NULL, 500, 50, NULL),
(26, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, '24124', 'ARCHIVED', NULL, '2025-08-30 23:22:23', '2025-08-30 23:42:48', NULL, 500, 50, NULL),
(27, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'фывфыв', 'ARCHIVED', NULL, '2025-08-30 23:25:33', '2025-08-30 23:42:45', NULL, 500, 50, NULL),
(28, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, '125451', 'ARCHIVED', NULL, '2025-08-30 23:30:26', '2025-08-30 23:42:38', NULL, 500, 50, NULL),
(29, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'ывфывфыв', 'ARCHIVED', NULL, '2025-08-30 23:44:15', '2025-08-30 23:45:52', NULL, 500, 50, NULL),
(30, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'sadasd', 'ARCHIVED', NULL, '2025-08-30 23:45:58', '2025-08-31 11:11:43', NULL, 500, 50, NULL),
(31, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'вфыв', 'REJECTED', NULL, '2025-08-30 23:48:30', '2025-08-30 23:48:52', NULL, 500, 50, 'отсутствие фото'),
(32, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, '', 1, 'sdasd', 'ACTIVE', NULL, '2025-08-31 11:51:08', '2025-08-31 11:51:38', NULL, 500, 50, NULL),
(33, 2, 'Фен для волос', 'https://www.wildberries.ru/catalog/169017408/detail.aspx', 0, '', 1, '', 'ACTIVE', NULL, '2025-08-31 13:41:05', '2025-08-31 13:44:19', NULL, 1000, 50, NULL),
(34, 2, 'Сканер', 'https://www.wildberries.ru/catalog/322528426/detail.aspx', 0, '', 1, 'фывфывфыв', 'ACTIVE', NULL, '2025-08-31 15:21:10', '2025-08-31 15:21:14', NULL, 300, 50, NULL),
(35, 2, 'Сканер', 'https://www.wildberries.ru/catalog/322528426/detail.aspx', 0, '', 1, '213123', 'ACTIVE', NULL, '2025-08-31 15:21:45', '2025-08-31 15:21:48', NULL, 300, 50, NULL),
(36, 2, 'Сканер', 'https://www.wildberries.ru/catalog/322528426/detail.aspx', 0, '', 1, 'ыфвфыв', 'ACTIVE', NULL, '2025-08-31 15:21:55', '2025-08-31 15:21:57', NULL, 300, 50, NULL),
(37, 2, 'Сканер', 'https://www.wildberries.ru/catalog/322528426/detail.aspx', 0, '', 1, 'фывыв', 'ACTIVE', NULL, '2025-08-31 15:22:20', '2025-08-31 15:22:22', NULL, 300, 50, NULL),
(38, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, 'Красота и здоровье', 1, 'sadsad', 'ACTIVE', NULL, '2025-09-01 10:47:54', '2025-09-01 10:48:03', NULL, 500, 30, NULL),
(39, 2, 'Фитолампа', 'https://www.wildberries.ru/catalog/275025912/detail.aspx', 0, 'Одежда для детей', 1, '123213', 'PENDING', NULL, '2025-09-01 15:35:26', '2025-09-01 15:35:26', NULL, 500, 30, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `listing_images`
--

CREATE TABLE IF NOT EXISTS `listing_images` (
  `listing_id` int(11) NOT NULL,
  `images_json` text NOT NULL,
  `fetched_at` datetime NOT NULL,
  PRIMARY KEY (`listing_id`),
  KEY `fetched_at` (`fetched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `listing_images`
--

INSERT INTO `listing_images` (`listing_id`, `images_json`, `fetched_at`) VALUES
(12, '[]', '2025-08-30 22:16:58'),
(16, '[]', '2025-08-30 22:34:30'),
(17, '[\"/static/no-photo.png\"]', '2025-08-30 23:21:59'),
(18, '[]', '2025-08-30 22:42:41'),
(19, '[\"/static/no-photo.png\"]', '2025-08-30 23:21:58'),
(20, '[\"/static/no-photo.png\"]', '2025-08-30 23:21:58'),
(21, '[\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-01.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-02.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-03.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-04.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-05.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-06.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-07.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-08.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-09.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/1.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/2.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/3.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/4.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/5.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/6.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/7.jpg\",\"https://basket-10.wb.ru/vol2750/part275025/275025912/images/big/8.jpg\"]', '2025-08-30 23:05:25'),
(22, '[\"https://images.wbstatic.net/big/new/275025912-1.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-1.jpg\",\"https://images.wbstatic.net/big/new/275025912-2.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-2.jpg\",\"https://images.wbstatic.net/big/new/275025912-3.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-3.jpg\",\"https://images.wbstatic.net/big/new/275025912-4.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-4.jpg\",\"https://images.wbstatic.net/big/new/275025912-5.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-5.jpg\",\"https://images.wbstatic.net/big/new/275025912-6.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-6.jpg\",\"https://images.wbstatic.net/big/new/275025912-7.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-7.jpg\",\"https://images.wbstatic.net/big/new/275025912-8.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-8.jpg\",\"https://images.wbstatic.net/big/new/275025912-9.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-9.jpg\",\"https://images.wbstatic.net/big/new/275025912-10.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-10.jpg\"]', '2025-08-30 23:11:09'),
(23, '[\"https://images.wbstatic.net/big/new/275025912-1.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-1.webp\",\"https://images.wbstatic.net/big/new/275025912-1.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-1.jpg\",\"https://images.wbstatic.net/big/new/275025912-2.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-2.webp\",\"https://images.wbstatic.net/big/new/275025912-2.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-2.jpg\",\"https://images.wbstatic.net/big/new/275025912-3.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-3.webp\",\"https://images.wbstatic.net/big/new/275025912-3.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-3.jpg\",\"https://images.wbstatic.net/big/new/275025912-4.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-4.webp\",\"https://images.wbstatic.net/big/new/275025912-4.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-4.jpg\"]', '2025-08-30 23:15:01'),
(24, '[\"/static/no-photo.png\"]', '2025-08-30 23:21:58'),
(25, '[\"https://images.wbstatic.net/big/new/275025912-1.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-1.webp\",\"https://images.wbstatic.net/big/new/275025912-1.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-1.jpg\",\"https://images.wbstatic.net/big/new/275025912-2.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-2.webp\",\"https://images.wbstatic.net/big/new/275025912-2.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-2.jpg\",\"https://images.wbstatic.net/big/new/275025912-3.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-3.webp\",\"https://images.wbstatic.net/big/new/275025912-3.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-3.jpg\",\"https://images.wbstatic.net/big/new/275025912-4.webp\",\"https://images.wbstatic.net/c516x688/new/275025912-4.webp\",\"https://images.wbstatic.net/big/new/275025912-4.jpg\",\"https://images.wbstatic.net/c516x688/new/275025912-4.jpg\"]', '2025-08-30 23:21:41'),
(26, '[\"/static/no-photo.png\"]', '2025-08-30 23:22:30'),
(27, '[\"/static/no-photo.png\"]', '2025-08-30 23:25:40'),
(28, '[]', '2025-08-30 23:30:34');

-- --------------------------------------------------------

--
-- Структура таблицы `listing_photos`
--

CREATE TABLE IF NOT EXISTS `listing_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `listing_photos`
--

INSERT INTO `listing_photos` (`id`, `listing_id`, `file_name`, `uploaded_at`) VALUES
(1, 29, 'photo_68b3629fb880a8.00440754.png', '2025-08-30 23:44:15'),
(2, 29, 'photo_68b3629fb8ce82.06514133.png', '2025-08-30 23:44:15'),
(3, 29, 'photo_68b3629fb91ac9.85827115.png', '2025-08-30 23:44:15'),
(4, 29, 'photo_68b3629fb96635.72378979.png', '2025-08-30 23:44:15'),
(5, 29, 'photo_68b3629fb9c2f9.45196477.png', '2025-08-30 23:44:15'),
(6, 30, 'photo_68b36306a92ce4.32375344.png', '2025-08-30 23:45:58'),
(7, 32, 'photo_68b40cfc3faf16.13476195.png', '2025-08-31 11:51:08'),
(8, 33, 'photo_68b426c1b786b0.93756746.png', '2025-08-31 13:41:05'),
(9, 34, 'photo_68b43e3683c617.18592196.png', '2025-08-31 15:21:10'),
(10, 35, 'photo_68b43e59f335a7.02694754.png', '2025-08-31 15:21:45'),
(11, 36, 'photo_68b43e63aeef35.08887692.png', '2025-08-31 15:21:55'),
(12, 37, 'photo_68b43e7c90bf37.84382610.png', '2025-08-31 15:22:20'),
(13, 38, 'photo_68b54faacfb308.83247321.png', '2025-09-01 10:47:54'),
(14, 39, 'photo_68b5930e8eb498.05024302.png', '2025-09-01 15:35:26');

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=295 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `link`, `is_read`, `created_at`) VALUES
(1, 3, 'Новое сообщение в DEAL-000005', '/deals/view.php?id=5', 1, '2025-08-30 13:20:11'),
(2, 2, 'Новое сообщение в DEAL-000005', '/deals/view.php?id=5', 1, '2025-08-30 13:20:27'),
(3, 2, 'Новое сообщение в DEAL-000005', '/deals/view.php?id=5', 1, '2025-08-30 13:20:44'),
(4, 2, 'Работа отправлена на проверку (DEAL-000005)', '/deals/view.php?id=5', 1, '2025-08-30 13:21:23'),
(5, 3, 'Выплата отклонена', '/payouts/history.php', 1, '2025-08-30 13:21:57'),
(6, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 13:23:02'),
(7, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 13:24:06'),
(8, 2, 'Работа отправлена на проверку (DEAL-000009)', '/deals/view.php?id=9', 1, '2025-08-30 13:50:31'),
(9, 2, 'Работа отправлена на проверку (DEAL-000012)', '/deals/view.php?id=12', 1, '2025-08-30 13:54:20'),
(10, 2, 'Новое сообщение в DEAL-000012', '/deals/view.php?id=12', 1, '2025-08-30 14:08:47'),
(11, 2, 'Новая сделка DEAL-000015', '/deals/view.php?id=15', 1, '2025-08-30 14:19:27'),
(12, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 14:23:17'),
(13, 2, 'Новая сделка DEAL-000016', '/deals/view.php?id=16', 1, '2025-08-30 14:23:44'),
(14, 2, 'Условия приняты по DEAL-000016', '/deals/view.php?id=16', 1, '2025-08-30 14:24:05'),
(15, 3, 'Средства зарезервированы по DEAL-000016', '/deals/view.php?id=16', 1, '2025-08-30 14:24:25'),
(16, 2, 'Работа отправлена на проверку (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:24:33'),
(17, 3, 'Работа отклонена (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:24:41'),
(18, 2, 'Открыт арбитраж (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:24:50'),
(19, 3, 'Открыт арбитраж (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:24:50'),
(20, 3, 'Новое сообщение в DEAL-000016', '/deals/view.php?id=16', 1, '2025-08-30 14:24:59'),
(21, 2, 'Новое сообщение в DEAL-000016', '/deals/view.php?id=16', 1, '2025-08-30 14:25:06'),
(22, 3, 'Арбитраж: сделка успешна (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:29:44'),
(23, 2, 'Арбитраж завершён: успешная (DEAL-000016)', '/deals/view.php?id=16', 1, '2025-08-30 14:29:44'),
(24, 3, 'Новая сделка DEAL-000017', '/deals/view.php?id=17', 1, '2025-08-30 14:30:51'),
(25, 2, 'Новая сделка DEAL-000018', '/deals/view.php?id=18', 1, '2025-08-30 14:34:13'),
(26, 2, 'Новая сделка DEAL-000019', '/deals/view.php?id=19', 1, '2025-08-30 14:35:04'),
(27, 3, 'Новое сообщение в DEAL-000019', '/deals/view.php?id=19', 1, '2025-08-30 14:35:30'),
(28, 2, 'Новое сообщение в DEAL-000019', '/deals/view.php?id=19', 1, '2025-08-30 14:35:32'),
(29, 3, 'Новая сделка DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:35:56'),
(30, 3, 'Новое сообщение в DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:36:25'),
(31, 2, 'Новое сообщение в DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:36:45'),
(32, 2, 'Условия приняты по DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:38:36'),
(33, 3, 'Средства зарезервированы по DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:39:22'),
(34, 2, 'Новое сообщение в DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:39:49'),
(35, 2, 'Новое сообщение в DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:39:57'),
(36, 2, 'Новое сообщение в DEAL-000020', '/deals/view.php?id=20', 1, '2025-08-30 14:40:15'),
(37, 2, 'Новая сделка СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:41:49'),
(38, 3, 'Новое сообщение в СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:42:07'),
(39, 3, 'Новое сообщение в СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:42:12'),
(40, 2, 'Условия приняты по СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:42:57'),
(41, 3, 'Средства зарезервированы по СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:42:58'),
(42, 2, 'Работа отправлена на проверку (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:43:04'),
(43, 3, 'Работа отклонена (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:43:07'),
(44, 2, 'Открыт арбитраж (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:43:22'),
(45, 3, 'Открыт арбитраж (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:43:22'),
(46, 2, 'Новое сообщение в СДЕЛКА-000021', '/deals/view.php?id=21', 1, '2025-08-30 14:45:28'),
(47, 3, 'Выплата отклонена', '/payouts/history.php', 1, '2025-08-30 14:46:19'),
(48, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 14:47:00'),
(49, 3, 'Арбитраж: сделка успешна (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:47:42'),
(50, 2, 'Арбитраж завершён: успешная (СДЕЛКА-000021)', '/deals/view.php?id=21', 1, '2025-08-30 14:47:42'),
(51, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 14:48:10'),
(52, 2, 'Новая сделка СДЕЛКА-000022', '/deals/view.php?id=22', 1, '2025-08-30 14:48:25'),
(53, 2, 'Условия приняты по СДЕЛКА-000022', '/deals/view.php?id=22', 1, '2025-08-30 14:48:36'),
(54, 3, 'Средства зарезервированы по СДЕЛКА-000022', '/deals/view.php?id=22', 1, '2025-08-30 14:48:38'),
(55, 3, 'Новое сообщение в СДЕЛКА-000022', '/deals/view.php?id=22', 1, '2025-08-30 14:48:44'),
(56, 2, 'Работа отправлена на проверку (СДЕЛКА-000022)', '/deals/view.php?id=22', 1, '2025-08-30 14:48:51'),
(57, 2, 'Открыт арбитраж (СДЕЛКА-000022)', '/deals/view.php?id=22', 1, '2025-08-30 14:48:57'),
(58, 3, 'Открыт арбитраж (СДЕЛКА-000022)', '/deals/view.php?id=22', 1, '2025-08-30 14:48:57'),
(59, 3, 'Арбитраж завершён: отклонена (СДЕЛКА-000022)', '/deals/view.php?id=22', 1, '2025-08-30 14:49:09'),
(60, 2, 'Арбитраж завершён: отклонена (СДЕЛКА-000022)', '/deals/view.php?id=22', 1, '2025-08-30 14:49:09'),
(61, 2, 'Новая сделка СДЕЛКА-000023', '/deals/view.php?id=23', 1, '2025-08-30 15:03:22'),
(62, 2, 'Условия приняты по СДЕЛКА-000023', '/deals/view.php?id=23', 1, '2025-08-30 15:03:32'),
(63, 3, 'Средства зарезервированы по СДЕЛКА-000023', '/deals/view.php?id=23', 1, '2025-08-30 15:03:33'),
(64, 2, 'Работа отправлена на проверку (СДЕЛКА-000023)', '/deals/view.php?id=23', 1, '2025-08-30 15:03:37'),
(65, 2, 'Открыт арбитраж (СДЕЛКА-000023)', '/deals/view.php?id=23', 1, '2025-08-30 15:03:38'),
(66, 3, 'Открыт арбитраж (СДЕЛКА-000023)', '/deals/view.php?id=23', 1, '2025-08-30 15:03:38'),
(67, 3, 'Новое сообщение в СДЕЛКА-000023', '/deals/view.php?id=23', 1, '2025-08-30 15:03:43'),
(68, 2, 'Новое сообщение в СДЕЛКА-000023', '/deals/view.php?id=23', 1, '2025-08-30 15:03:44'),
(69, 3, 'Арбитраж завершён: отклонена (СДЕЛКА-000023)', '/deals/view.php?id=23', 1, '2025-08-30 15:03:59'),
(70, 2, 'Арбитраж завершён: отклонена (СДЕЛКА-000023)', '/deals/view.php?id=23', 1, '2025-08-30 15:03:59'),
(71, 2, 'Новая сделка СДЕЛКА-000024', '/deals/view.php?id=24', 1, '2025-08-30 15:05:02'),
(72, 2, 'Условия приняты по СДЕЛКА-000024', '/deals/view.php?id=24', 1, '2025-08-30 15:05:12'),
(73, 3, 'Средства зарезервированы по СДЕЛКА-000024', '/deals/view.php?id=24', 1, '2025-08-30 15:05:13'),
(74, 2, 'Работа отправлена на проверку (СДЕЛКА-000024)', '/deals/view.php?id=24', 1, '2025-08-30 15:05:17'),
(75, 2, 'Открыт арбитраж (СДЕЛКА-000024)', '/deals/view.php?id=24', 1, '2025-08-30 15:05:19'),
(76, 3, 'Открыт арбитраж (СДЕЛКА-000024)', '/deals/view.php?id=24', 1, '2025-08-30 15:05:19'),
(77, 3, 'Арбитраж завершён: отклонена (СДЕЛКА-000024)', '/deals/view.php?id=24', 1, '2025-08-30 15:05:27'),
(78, 2, 'Арбитраж завершён: отклонена, деньги возвращены (СДЕЛКА-000024)', '/deals/view.php?id=24', 1, '2025-08-30 15:05:27'),
(79, 2, 'Новая сделка СДЕЛКА-000025', '/deals/view.php?id=25', 1, '2025-08-30 15:07:25'),
(80, 2, 'Условия приняты по СДЕЛКА-000025', '/deals/view.php?id=25', 1, '2025-08-30 15:07:32'),
(81, 3, 'Средства зарезервированы по СДЕЛКА-000025', '/deals/view.php?id=25', 1, '2025-08-30 15:07:32'),
(82, 2, 'Работа отправлена на проверку (СДЕЛКА-000025)', '/deals/view.php?id=25', 1, '2025-08-30 15:07:35'),
(83, 2, 'Открыт арбитраж (СДЕЛКА-000025)', '/deals/view.php?id=25', 1, '2025-08-30 15:07:39'),
(84, 3, 'Открыт арбитраж (СДЕЛКА-000025)', '/deals/view.php?id=25', 1, '2025-08-30 15:07:39'),
(85, 3, 'Арбитраж: сделка успешна (СДЕЛКА-000025)', '/deals/view.php?id=25', 1, '2025-08-30 15:08:04'),
(86, 2, 'Арбитраж завершён: успешная (СДЕЛКА-000025)', '/deals/view.php?id=25', 1, '2025-08-30 15:08:04'),
(87, 2, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 15:08:21'),
(88, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 15:08:22'),
(89, 2, 'Новая сделка СДЕЛКА-000026', '/deals/view.php?id=26', 1, '2025-08-30 15:20:54'),
(90, 2, 'Новое сообщение в СДЕЛКА-000026', '/deals/view.php?id=26', 1, '2025-08-30 15:21:03'),
(91, 2, 'Новое сообщение в СДЕЛКА-000026', '/deals/view.php?id=26', 1, '2025-08-30 15:21:07'),
(92, 2, 'Новое сообщение в СДЕЛКА-000026', '/deals/view.php?id=26', 1, '2025-08-30 15:21:11'),
(93, 2, 'Новая сделка СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:27:55'),
(94, 2, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:28:00'),
(95, 2, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:28:03'),
(96, 2, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:32:53'),
(97, 2, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:33:09'),
(98, 3, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:33:14'),
(99, 3, 'Новое сообщение в СДЕЛКА-000027', '/deals/view.php?id=27', 1, '2025-08-30 15:33:22'),
(100, 2, 'Новая сделка СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:34:22'),
(101, 2, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:34:28'),
(102, 2, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:34:33'),
(103, 2, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:39:03'),
(104, 2, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:39:09'),
(105, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:39:18'),
(106, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:40:19'),
(107, 2, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:42:20'),
(108, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:55:24'),
(109, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:55:51'),
(110, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 15:58:10'),
(111, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 16:06:22'),
(112, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 16:12:38'),
(113, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 16:36:12'),
(114, 3, 'Новое сообщение в СДЕЛКА-000028', '/deals/view.php?id=28', 1, '2025-08-30 16:36:16'),
(115, 5, 'Новая сделка СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:08:46'),
(116, 4, 'Условия приняты по СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:09:38'),
(117, 4, 'Новое сообщение в СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:09:47'),
(118, 5, 'Новое сообщение в СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:09:55'),
(119, 5, 'Средства зарезервированы по СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:10:20'),
(120, 4, 'Новое сообщение в СДЕЛКА-000029', '/deals/view.php?id=29', 0, '2025-08-30 17:10:56'),
(121, 4, 'Работа отправлена на проверку (СДЕЛКА-000029)', '/deals/view.php?id=29', 0, '2025-08-30 17:10:58'),
(122, 5, 'Сделка завершена успешно (СДЕЛКА-000029)', '/deals/view.php?id=29', 0, '2025-08-30 17:11:41'),
(123, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 19:53:27'),
(124, 2, 'Новый отклик по объявлению: СДЕЛКА-000030', '/deals/view.php?id=30', 1, '2025-08-30 20:17:11'),
(125, 3, 'Новое сообщение по СДЕЛКА-000030', '/deals/view.php?id=30', 1, '2025-08-30 20:21:48'),
(126, 2, 'Новое сообщение по СДЕЛКА-000030', '/deals/view.php?id=30', 1, '2025-08-30 20:34:05'),
(127, 3, 'Условия по СДЕЛКА-000030 приняты', '/deals/view.php?id=30', 1, '2025-08-30 20:34:16'),
(128, 3, 'Сделка СДЕЛКА-000030 оплачена', '/deals/view.php?id=30', 1, '2025-08-30 20:40:31'),
(129, 2, 'Новое сообщение по СДЕЛКА-000030', '/deals/view.php?id=30', 1, '2025-08-30 20:45:28'),
(130, 2, 'Новое сообщение по СДЕЛКА-000030', '/deals/view.php?id=30', 1, '2025-08-30 20:49:41'),
(131, 2, 'Работа по СДЕЛКА-000030 отправлена на проверку', '/deals/view.php?id=30', 1, '2025-08-30 20:50:01'),
(132, 3, 'Продавец подтвердил выполнение по СДЕЛКА-000030 — начислено ₽ 500', '/deals/view.php?id=30', 1, '2025-08-30 21:01:13'),
(133, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-30 21:01:34'),
(134, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:28:26'),
(135, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:28:40'),
(136, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:32:26'),
(137, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:33:34'),
(138, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:33:44'),
(139, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:40:05'),
(140, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:40:09'),
(141, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:40:11'),
(142, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:40:14'),
(143, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:40:23'),
(144, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:40:35'),
(145, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:41:08'),
(146, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:45:45'),
(147, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:46:03'),
(148, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:46:08'),
(149, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:59:00'),
(150, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 21:59:15'),
(151, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:59:20'),
(152, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 21:59:42'),
(153, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:17:31'),
(154, 2, 'Объявление: статус обновлён', '/seller/listings/index.php', 1, '2025-08-30 22:25:08'),
(155, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 22:25:19'),
(156, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 22:25:22'),
(157, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:25:26'),
(158, 2, 'Объявление: статус обновлён', '/seller/listings/index.php', 1, '2025-08-30 22:25:30'),
(159, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:29:03'),
(160, 2, 'Объявление: статус обновлён', '/seller/listings/index.php', 1, '2025-08-30 22:29:09'),
(161, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:34:13'),
(162, 2, 'Объявление: статус обновлён', '/seller/listings/index.php', 1, '2025-08-30 22:34:19'),
(163, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:34:38'),
(164, 2, 'Объявление: статус обновлён', '/seller/listings/index.php', 1, '2025-08-30 22:34:42'),
(165, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:42:21'),
(166, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 22:42:29'),
(167, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:48:07'),
(168, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 22:48:13'),
(169, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 22:59:45'),
(170, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 22:59:50'),
(171, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:05:19'),
(172, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:05:22'),
(173, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:10:59'),
(174, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:11:04'),
(175, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:14:55'),
(176, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:14:59'),
(177, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:17:56'),
(178, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:17:59'),
(179, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:21:29'),
(180, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:21:31'),
(181, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:22:23'),
(182, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:22:26'),
(183, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:25:33'),
(184, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:25:37'),
(185, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:30:26'),
(186, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:30:29'),
(187, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:42:38'),
(188, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:42:45'),
(189, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:42:48'),
(190, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:42:52'),
(191, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:05'),
(192, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:09'),
(193, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:12'),
(194, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:16'),
(195, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:19'),
(196, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:23'),
(197, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:43:26'),
(198, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:44:15'),
(199, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:44:21'),
(200, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-30 23:45:52'),
(201, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:45:58'),
(202, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:46:02'),
(203, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-30 23:48:30'),
(204, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-30 23:48:52'),
(205, 2, 'Новая заявка по объявлению', '/deals/view.php?id=31', 1, '2025-08-30 23:51:02'),
(206, 3, 'Заявка создана', '/deals/view.php?id=31', 1, '2025-08-30 23:51:02'),
(207, 2, 'Объявление перенесено в архив', '/seller/listings/index.php?tab=archived', 1, '2025-08-31 11:11:43'),
(208, 3, 'Условия по СДЕЛКА-000031 приняты', '/deals/view.php?id=31', 1, '2025-08-31 11:22:15'),
(209, 3, 'Сделка СДЕЛКА-000031 оплачена', '/deals/view.php?id=31', 1, '2025-08-31 11:22:27'),
(210, 2, 'Работа по СДЕЛКА-000031 отправлена на проверку', '/deals/view.php?id=31', 1, '2025-08-31 11:22:32'),
(211, 3, 'Продавец подтвердил выполнение по СДЕЛКА-000031 — начислено ₽ 500', '/deals/view.php?id=31', 1, '2025-08-31 11:22:36'),
(212, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-08-31 11:22:55'),
(213, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 11:51:08'),
(214, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 11:51:38'),
(215, 2, 'Новая заявка по объявлению', '/deals/view.php?id=32', 1, '2025-08-31 11:51:44'),
(216, 3, 'Заявка создана', '/deals/view.php?id=32', 1, '2025-08-31 11:51:44'),
(217, 2, 'Новая заявка по объявлению', '/deals/view.php?id=33', 1, '2025-08-31 12:28:34'),
(218, 3, 'Заявка создана', '/deals/view.php?id=33', 1, '2025-08-31 12:28:34'),
(219, 2, 'Новое сообщение по СДЕЛКА-000033', '/deals/view.php?id=33#chat', 1, '2025-08-31 12:28:37'),
(220, 3, 'Новое сообщение по СДЕЛКА-000033', '/deals/view.php?id=33#chat', 1, '2025-08-31 12:28:45'),
(221, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 13:41:05'),
(222, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 13:41:08'),
(223, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 15:21:10'),
(224, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 15:21:14'),
(225, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 15:21:45'),
(226, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 15:21:48'),
(227, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 15:21:55'),
(228, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 15:21:57'),
(229, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-08-31 15:22:20'),
(230, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-08-31 15:22:22'),
(231, 3, 'Новая сделка СДЕЛКА-000034', '/deals/view.php?id=34', 1, '2025-09-01 08:53:33'),
(232, 2, 'Новое сообщение по СДЕЛКА-000034', '/deals/view.php?id=34#chat', 1, '2025-09-01 08:53:51'),
(233, 3, 'Новое сообщение по СДЕЛКА-000034', '/deals/view.php?id=34#chat', 1, '2025-09-01 08:53:54'),
(234, 2, 'Новое сообщение по СДЕЛКА-000034', '/deals/view.php?id=34#chat', 1, '2025-09-01 08:54:07'),
(235, 2, 'Условия по СДЕЛКА-000034 приняты', '/deals/view.php?id=34', 1, '2025-09-01 08:54:21'),
(236, 3, 'Сделка СДЕЛКА-000034 оплачена', '/deals/view.php?id=34', 1, '2025-09-01 08:54:47'),
(237, 2, 'Работа по СДЕЛКА-000034 отправлена на проверку', '/deals/view.php?id=34', 1, '2025-09-01 08:54:57'),
(238, 3, 'Отклонение работы по СДЕЛКА-000034 — требуется доработка', '/deals/view.php?id=34', 1, '2025-09-01 08:55:07'),
(239, 2, 'Новое сообщение по СДЕЛКА-000034', '/deals/view.php?id=34#chat', 1, '2025-09-01 08:55:47'),
(240, 2, 'Открыт арбитраж по СДЕЛКА-000034', '/deals/view.php?id=34', 1, '2025-09-01 09:05:42'),
(241, 3, 'Новая сделка СДЕЛКА-000035', '/deals/view.php?id=35', 1, '2025-09-01 09:19:30'),
(242, 2, 'Условия по СДЕЛКА-000035 приняты', '/deals/view.php?id=35', 1, '2025-09-01 09:19:37'),
(243, 2, 'Новое сообщение по СДЕЛКА-000035', '/deals/view.php?id=35#chat', 1, '2025-09-01 09:19:42'),
(244, 3, 'Сделка СДЕЛКА-000035 оплачена', '/deals/view.php?id=35', 1, '2025-09-01 09:19:49'),
(245, 2, 'Работа по СДЕЛКА-000035 отправлена на проверку', '/deals/view.php?id=35', 1, '2025-09-01 09:19:59'),
(246, 3, 'Отклонение работы по СДЕЛКА-000035 — требуется доработка', '/deals/view.php?id=35', 1, '2025-09-01 09:20:06'),
(247, 2, 'Открыт арбитраж по СДЕЛКА-000035', '/deals/view.php?id=35', 1, '2025-09-01 09:20:10'),
(248, 3, 'Решение по спору СДЕЛКА-000035', '/deals/view.php?id=35#chat', 1, '2025-09-01 09:20:19'),
(249, 2, 'Решение по спору СДЕЛКА-000035', '/deals/view.php?id=35#chat', 1, '2025-09-01 09:20:19'),
(250, 2, 'Новая сделка СДЕЛКА-000036', '/deals/view.php?id=36', 1, '2025-09-01 09:20:45'),
(251, 2, 'Условия по СДЕЛКА-000036 приняты', '/deals/view.php?id=36', 1, '2025-09-01 09:21:11'),
(252, 2, 'Новая сделка СДЕЛКА-000037', '/deals/view.php?id=37', 1, '2025-09-01 09:33:40'),
(253, 3, 'Условия по СДЕЛКА-000037 приняты', '/deals/view.php?id=37', 1, '2025-09-01 09:33:48'),
(254, 2, 'Новое сообщение по СДЕЛКА-000037', '/deals/view.php?id=37#chat', 1, '2025-09-01 09:33:51'),
(255, 3, 'Новое сообщение по СДЕЛКА-000037', '/deals/view.php?id=37#chat', 1, '2025-09-01 09:33:53'),
(256, 3, 'Новое сообщение по СДЕЛКА-000037', '/deals/view.php?id=37#chat', 1, '2025-09-01 09:33:57'),
(257, 2, 'Новое сообщение по СДЕЛКА-000037', '/deals/view.php?id=37#chat', 1, '2025-09-01 09:34:01'),
(258, 3, 'Сделка СДЕЛКА-000037 оплачена', '/deals/view.php?id=37', 1, '2025-09-01 09:34:18'),
(259, 2, 'Работа по СДЕЛКА-000037 отправлена на проверку', '/deals/view.php?id=37', 1, '2025-09-01 09:34:25'),
(260, 3, 'Продавец подтвердил выполнение по СДЕЛКА-000037 — начислено ₽ 400', '/deals/view.php?id=37', 1, '2025-09-01 09:34:30'),
(261, 3, 'Новая сделка СДЕЛКА-000038', '/deals/view.php?id=38', 1, '2025-09-01 09:34:41'),
(262, 2, 'Условия по СДЕЛКА-000038 приняты', '/deals/view.php?id=38', 1, '2025-09-01 09:34:46'),
(263, 3, 'Сделка СДЕЛКА-000038 оплачена', '/deals/view.php?id=38', 1, '2025-09-01 09:34:48'),
(264, 2, 'Работа по СДЕЛКА-000038 отправлена на проверку', '/deals/view.php?id=38', 1, '2025-09-01 09:34:51'),
(265, 3, 'Отклонение работы по СДЕЛКА-000038 — требуется доработка', '/deals/view.php?id=38', 1, '2025-09-01 09:34:54'),
(266, 3, 'Новая сделка СДЕЛКА-000039', '/deals/view.php?id=39', 1, '2025-09-01 09:37:49'),
(267, 2, 'Новое сообщение по СДЕЛКА-000039', '/deals/view.php?id=39#chat', 1, '2025-09-01 09:37:53'),
(268, 3, 'Новое сообщение по СДЕЛКА-000039', '/deals/view.php?id=39#chat', 1, '2025-09-01 09:38:03'),
(269, 2, 'Условия по СДЕЛКА-000039 приняты', '/deals/view.php?id=39', 1, '2025-09-01 09:38:52'),
(270, 3, 'Сделка СДЕЛКА-000039 оплачена', '/deals/view.php?id=39', 1, '2025-09-01 09:38:55'),
(271, 2, 'Работа по СДЕЛКА-000039 отправлена на проверку', '/deals/view.php?id=39', 1, '2025-09-01 09:38:57'),
(272, 3, 'Новая сделка СДЕЛКА-000040', '/deals/view.php?id=40', 1, '2025-09-01 09:44:46'),
(273, 2, 'Условия по СДЕЛКА-000040 приняты', '/deals/view.php?id=40', 1, '2025-09-01 09:44:50'),
(274, 3, 'Сделка СДЕЛКА-000040 оплачена', '/deals/view.php?id=40', 1, '2025-09-01 09:44:51'),
(275, 2, 'Работа по СДЕЛКА-000040 отправлена на проверку', '/deals/view.php?id=40', 1, '2025-09-01 09:44:57'),
(276, 3, 'Отклонение работы по СДЕЛКА-000040 — требуется доработка', '/deals/view.php?id=40', 1, '2025-09-01 09:45:00'),
(277, 2, 'Работа по СДЕЛКА-000040 отправлена на проверку', '/deals/view.php?id=40', 1, '2025-09-01 09:45:04'),
(278, 3, 'Отклонение работы по СДЕЛКА-000040 — требуется доработка', '/deals/view.php?id=40', 1, '2025-09-01 09:45:06'),
(279, 2, 'Работа по СДЕЛКА-000040 отправлена на проверку', '/deals/view.php?id=40', 1, '2025-09-01 09:45:08'),
(280, 3, 'Продавец подтвердил выполнение по СДЕЛКА-000040 — начислено ₽ 400', '/deals/view.php?id=40', 1, '2025-09-01 09:45:12'),
(281, 2, 'Новая сделка СДЕЛКА-000041', '/deals/view.php?id=41', 1, '2025-09-01 10:12:24'),
(282, 3, 'Условия по СДЕЛКА-000041 приняты', '/deals/view.php?id=41', 1, '2025-09-01 10:12:34'),
(283, 3, 'Сделка СДЕЛКА-000041 оплачена', '/deals/view.php?id=41', 1, '2025-09-01 10:12:36'),
(284, 2, 'Работа по СДЕЛКА-000041 отправлена на проверку', '/deals/view.php?id=41', 1, '2025-09-01 10:12:43'),
(285, 3, 'Открыт арбитраж по СДЕЛКА-000041', '/deals/view.php?id=41', 1, '2025-09-01 10:12:47'),
(286, 3, 'Решение по спору СДЕЛКА-000041', '/deals/view.php?id=41#chat', 1, '2025-09-01 10:12:56'),
(287, 2, 'Решение по спору СДЕЛКА-000041', '/deals/view.php?id=41#chat', 1, '2025-09-01 10:12:56'),
(288, 3, 'Выплата одобрена', '/payouts/history.php', 1, '2025-09-01 10:13:22'),
(289, 2, 'Выплата одобрена', '/payouts/history.php', 1, '2025-09-01 10:13:23'),
(290, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 1, '2025-09-01 10:47:54'),
(291, 2, 'Обновление статуса объявления', '/seller/listings/index.php', 1, '2025-09-01 10:48:03'),
(292, 3, 'Новая сделка СДЕЛКА-000042', '/deals/view.php?id=42', 0, '2025-09-01 12:42:41'),
(293, 2, 'Условия по СДЕЛКА-000042 приняты', '/deals/view.php?id=42', 1, '2025-09-01 12:42:47'),
(294, 2, 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending', 0, '2025-09-01 15:35:26');

-- --------------------------------------------------------

--
-- Структура таблицы `payout_requests`
--

CREATE TABLE IF NOT EXISTS `payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `admin_comment` varchar(500) DEFAULT NULL,
  `comment_admin` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `payout_requests`
--

INSERT INTO `payout_requests` (`id`, `user_id`, `amount`, `status`, `admin_comment`, `comment_admin`, `created_at`, `processed_at`) VALUES
(1, 3, 300, 'REJECTED', NULL, NULL, '2025-08-30 12:14:16', '2025-08-30 12:24:36'),
(2, 3, 300, 'REJECTED', NULL, NULL, '2025-08-30 12:14:49', '2025-08-30 12:24:35'),
(3, 3, 900, 'APPROVED', NULL, NULL, '2025-08-30 12:25:18', '2025-08-30 12:31:14'),
(4, 3, 1000, 'REJECTED', NULL, NULL, '2025-08-30 13:21:47', '2025-08-30 13:21:57'),
(5, 3, 500, 'APPROVED', NULL, NULL, '2025-08-30 13:22:58', '2025-08-30 13:23:02'),
(6, 3, 500, 'APPROVED', NULL, NULL, '2025-08-30 13:24:01', '2025-08-30 13:24:06'),
(7, 3, 1000, 'APPROVED', NULL, NULL, '2025-08-30 14:23:07', '2025-08-30 14:23:17'),
(8, 3, 1000, 'REJECTED', 'Нет основавний', NULL, '2025-08-30 14:46:05', '2025-08-30 14:46:19'),
(9, 3, 1000, 'APPROVED', NULL, NULL, '2025-08-30 14:46:51', '2025-08-30 14:47:00'),
(10, 3, 1000, 'APPROVED', NULL, NULL, '2025-08-30 14:47:50', '2025-08-30 14:48:10'),
(11, 3, 1000, 'APPROVED', NULL, NULL, '2025-08-30 15:08:08', '2025-08-30 15:08:22'),
(12, 2, 1000, 'APPROVED', NULL, NULL, '2025-08-30 15:08:13', '2025-08-30 15:08:21'),
(13, 3, 500, 'APPROVED', NULL, NULL, '2025-08-30 21:01:28', '2025-08-30 21:01:34'),
(14, 3, 500, 'APPROVED', NULL, NULL, '2025-08-31 11:22:46', '2025-08-31 11:22:55'),
(15, 2, 400, 'APPROVED', NULL, NULL, '2025-09-01 10:13:05', '2025-09-01 10:13:23'),
(16, 3, 1600, 'APPROVED', NULL, NULL, '2025-09-01 10:13:11', '2025-09-01 10:13:22');

-- --------------------------------------------------------

--
-- Структура таблицы `profiles`
--

CREATE TABLE IF NOT EXISTS `profiles` (
  `user_id` int(11) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `telegram` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `profiles`
--

INSERT INTO `profiles` (`user_id`, `display_name`, `telegram`) VALUES
(1, NULL, NULL),
(2, NULL, NULL),
(3, 'Антон', 'buyer1'),
(4, NULL, NULL),
(5, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `requisites`
--

CREATE TABLE IF NOT EXISTS `requisites` (
  `user_id` int(11) NOT NULL,
  `sbp_phone` varchar(30) NOT NULL,
  `sbp_bank` varchar(100) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `requisites`
--

INSERT INTO `requisites` (`user_id`, `sbp_phone`, `sbp_bank`, `full_name`, `updated_at`) VALUES
(2, '+79989182912', 'Т-Банк', 'Валенок Вячеслав Андреевич', '2025-08-30 14:50:08'),
(3, '+79850033831', 'Альфа Банк', 'Фролов Е.В.', '2025-08-30 13:12:11');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `pass_hash` varchar(255) NOT NULL,
  `role` enum('SELLER','BUYER','ADMIN') NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `email`, `pass_hash`, `role`, `is_blocked`, `created_at`, `last_login`) VALUES
(1, 'ADMIN', 'zerguud88@gmail.com', '$2y$10$zQGZld32np4/r56Fti/C8.ePDnOIHkiHXrkNOxojUXl/ZBOhJmPam', 'ADMIN', 0, '2025-08-30 11:27:16', '2025-09-01 08:51:46'),
(2, 'seller1', 'seller1@mail.ru', '$2y$10$JjLFj3hwfjTJpuSqbxJD8.63VFHELqU4XC/spi0aAeaDohm2AQCpC', 'SELLER', 0, '2025-08-30 11:28:50', '2025-09-01 16:09:04'),
(3, 'buyer1', 'buyer1@mail.ru', '$2y$10$8Zi0Q4iICHunCBX16PSC7eLbSrvB8zg7KDQnxzo09ZQ3p31DmFOOS', 'BUYER', 0, '2025-08-30 11:29:09', '2025-09-01 15:13:25'),
(4, 'seller2', 'seller2@sss.ru', '$2y$10$XErMxip9J6IRDgUZmOz3T.pwZX7j5LndNc5NddZCMQ.29o9fi6OH6', 'SELLER', 0, '2025-08-30 17:07:09', NULL),
(5, 'buyer2', 'buyer2@sds.ru', '$2y$10$ecmP5ByaVAsJAyIPH9x7KuhwbzkouyMXv0aTLlCjNYgAyaFAC6LLe', 'BUYER', 0, '2025-08-30 17:07:33', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_reviews`
--

CREATE TABLE IF NOT EXISTS `user_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `status` enum('PENDING','PUBLISHED','DELETED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `author_id` (`author_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `wallet_entries`
--

CREATE TABLE IF NOT EXISTS `wallet_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `direction` enum('CREDIT','DEBIT') NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `deal_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `wallet_entries`
--

INSERT INTO `wallet_entries` (`id`, `user_id`, `amount`, `direction`, `memo`, `deal_id`, `created_at`) VALUES
(1, 3, 300, 'CREDIT', 'Кэшбэк по сделке DEAL-000001', 1, '2025-08-30 12:12:19'),
(2, 3, 300, 'CREDIT', 'Возврат по отклоненной заявке на вывод', NULL, '2025-08-30 12:24:35'),
(3, 3, 300, 'CREDIT', 'Возврат по отклоненной заявке на вывод', NULL, '2025-08-30 12:24:36'),
(4, 3, 900, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 12:25:18'),
(5, 3, 0, 'DEBIT', 'Выплата отправлена (финализация)', NULL, '2025-08-30 12:31:14'),
(6, 3, 1000, 'CREDIT', 'Кэшбэк по сделке DEAL-000005', 5, '2025-08-30 13:21:36'),
(7, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 13:21:47'),
(8, 3, 1000, 'CREDIT', 'Возврат по отклоненной заявке на вывод', NULL, '2025-08-30 13:21:57'),
(9, 3, 500, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 13:22:58'),
(10, 3, 500, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 13:24:01'),
(11, 3, 1000, 'CREDIT', 'Кэшбэк по сделке DEAL-000009', 9, '2025-08-30 13:50:34'),
(12, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 14:23:07'),
(13, 3, 1000, 'CREDIT', 'Кэшбэк по сделке DEAL-000016 (решение админа)', 16, '2025-08-30 14:29:44'),
(14, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 14:46:05'),
(15, 3, 1000, 'CREDIT', 'Возврат по отклоненной заявке на вывод', NULL, '2025-08-30 14:46:19'),
(16, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 14:46:51'),
(17, 3, 1000, 'CREDIT', 'Кэшбэк по сделке СДЕЛКА-000021 (решение админа)', 21, '2025-08-30 14:47:42'),
(18, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 14:47:50'),
(19, 2, 1000, 'CREDIT', 'Возврат кэшбэка продавцу по сделке СДЕЛКА-000024 (решение админа)', 24, '2025-08-30 15:05:27'),
(20, 3, 1000, 'CREDIT', 'Кэшбэк по сделке СДЕЛКА-000025 (решение админа)', 25, '2025-08-30 15:08:04'),
(21, 3, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 15:08:08'),
(22, 2, 1000, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 15:08:13'),
(23, 5, 300, 'CREDIT', 'Кэшбэк по сделке СДЕЛКА-000029', 29, '2025-08-30 17:11:41'),
(24, 3, 500, 'CREDIT', NULL, NULL, '2025-08-30 21:01:13'),
(25, 3, 500, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-30 21:01:28'),
(26, 3, 500, 'CREDIT', NULL, NULL, '2025-08-31 11:22:36'),
(27, 3, 500, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-08-31 11:22:46'),
(28, 3, 400, 'CREDIT', 'Кэшбэк по спору СДЕЛКА-000034', 34, '2025-09-01 09:12:26'),
(29, 3, 400, 'CREDIT', 'Кэшбэк по спору СДЕЛКА-000035', 35, '2025-09-01 09:20:19'),
(30, 3, 400, 'CREDIT', NULL, NULL, '2025-09-01 09:34:30'),
(31, 3, 400, 'CREDIT', 'Выплата по сделке СДЕЛКА-000040', 40, '2025-09-01 09:45:12'),
(32, 2, 400, 'CREDIT', 'Возврат по спору СДЕЛКА-000041', 41, '2025-09-01 10:12:56'),
(33, 2, 400, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-09-01 10:13:05'),
(34, 3, 1600, 'DEBIT', 'Заявка на вывод (заморозка)', NULL, '2025-09-01 10:13:11');

-- --------------------------------------------------------

--
-- Структура таблицы `yk_payments`
--

CREATE TABLE IF NOT EXISTS `yk_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deal_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `yk_payment_id` varchar(64) NOT NULL,
  `amount_rub` int(11) NOT NULL,
  `status` varchar(32) NOT NULL,
  `idempotence_key` varchar(64) NOT NULL,
  `confirmation_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_yk_payment` (`yk_payment_id`),
  KEY `deal_idx` (`deal_id`),
  KEY `seller_idx` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `balances`
--
ALTER TABLE `balances`
  ADD CONSTRAINT `balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `deals`
--
ALTER TABLE `deals`
  ADD CONSTRAINT `deals_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deals_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deals_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `deal_attachments`
--
ALTER TABLE `deal_attachments`
  ADD CONSTRAINT `deal_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `deal_messages` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `deal_messages`
--
ALTER TABLE `deal_messages`
  ADD CONSTRAINT `deal_messages_ibfk_1` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deal_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disputes_winner` FOREIGN KEY (`winner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `listing_photos`
--
ALTER TABLE `listing_photos`
  ADD CONSTRAINT `listing_photos_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payout_requests`
--
ALTER TABLE `payout_requests`
  ADD CONSTRAINT `payout_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `requisites`
--
ALTER TABLE `requisites`
  ADD CONSTRAINT `requisites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD CONSTRAINT `fk_user_reviews_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `wallet_entries`
--
ALTER TABLE `wallet_entries`
  ADD CONSTRAINT `wallet_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
