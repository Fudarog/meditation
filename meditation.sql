-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 26 2026 г., 22:46
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `meditation`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `achievements`
--

INSERT INTO `achievements` (`id`, `code`, `title`, `description`, `icon`, `created_at`) VALUES
(1, 'first_session', 'Первая сессия', 'Вы провели первую практику', 'bi-star', '2026-04-30 12:41:59'),
(2, 'three_sessions', '3 сессии', 'Вы провели три сессии', 'bi-lightning', '2026-04-30 12:41:59'),
(3, 'ten_sessions', '10 сессий', 'Вы провели десять сессий', 'bi-award', '2026-04-30 12:41:59'),
(4, 'hundred_minutes', '100 минут', 'Вы набрали 100 минут практики', 'bi-stopwatch', '2026-04-30 12:41:59'),
(5, 'first_incense', 'Первое благовоние', 'Вы зажгли благовоние', 'bi-fire', '2026-04-30 12:41:59'),
(6, 'week_streak', 'Неделя практики', 'Вы занимались 7 дней подряд', 'bi-calendar-check', '2026-04-30 12:41:59');

-- --------------------------------------------------------

--
-- Структура таблицы `incense`
--

CREATE TABLE `incense` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `incense`
--

INSERT INTO `incense` (`id`, `user_id`, `message`, `created_at`, `count`) VALUES
(3, 1, 'Зажёг благовоние', '2026-04-30 13:33:55', 1),
(5, 1, 'Зажёг благовоние', '2026-04-30 13:36:29', 1),
(6, 1, 'Зажёг благовоние', '2026-04-30 15:29:07', 1),
(10, 1, 'Зажёг благовоние', '2026-04-30 16:03:24', 1),
(11, 1, 'Зажёг благовоние', '2026-04-30 16:03:27', 1),
(12, 1, 'Зажёг благовоние', '2026-05-01 11:29:07', 1),
(13, 1, 'Зажёг благовоние', '2026-05-01 11:29:09', 1),
(14, 1, 'Зажёг благовоние', '2026-05-01 11:29:13', 1),
(15, 1, 'Зажёг благовоние', '2026-05-01 11:29:15', 1),
(16, 1, 'Зажёг благовоние', '2026-05-06 15:34:55', 1),
(17, 1, 'Зажёг благовоние', '2026-05-09 13:03:13', 1),
(18, 1, 'Зажёг благовоние', '2026-05-09 13:03:26', 1),
(19, 1, 'Зажёг благовоние', '2026-05-09 17:10:09', 1),
(20, 1, 'Зажёг благовоние', '2026-05-09 17:24:43', 1),
(21, 1, 'Зажёг благовоние', '2026-05-09 17:24:56', 1),
(22, 1, 'Зажёг благовоние', '2026-05-10 12:37:49', 1),
(23, 1, 'Зажёг благовоние', '2026-05-10 12:41:59', 1),
(24, 1, 'Зажёг благовоние', '2026-05-10 13:05:23', 1),
(25, 1, 'Зажёг благовоние', '2026-05-10 13:05:29', 1),
(26, 1, 'Зажёг благовоние', '2026-05-11 13:26:39', 1),
(27, 2, 'Зажёг благовоние', '2026-05-11 22:23:19', 1),
(29, 10, 'Зажёг благовоние', '2026-05-12 17:26:52', 1),
(30, 1, 'Зажёг благовоние', '2026-05-13 00:55:08', 1),
(31, 1, 'Зажёг благовоние', '2026-05-13 11:02:07', 1),
(32, 19, 'Зажёг благовоние', '2026-05-13 11:24:21', 1),
(33, 1, 'Зажёг благовоние', '2026-05-13 19:02:55', 1),
(34, 1, 'Зажёг благовоние', '2026-05-13 19:04:20', 1),
(36, 23, 'Зажёг благовоние', '2026-05-26 19:16:02', 1),
(37, 1, 'Зажёг благовоние', '2026-05-26 19:29:29', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `user_agent` text,
  `attempt_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `user_id`, `email`, `success`, `user_agent`, `attempt_at`) VALUES
(1, '127.0.0.1', NULL, '7.daria.tim.7@gmail.com', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 22:48:24');

-- --------------------------------------------------------

--
-- Структура таблицы `password_logs`
--

CREATE TABLE `password_logs` (
  `id` int NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `action` varchar(20) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `password_logs`
--

INSERT INTO `password_logs` (`id`, `email`, `reset_code`, `action`, `ip`, `created_at`) VALUES
(5, 'ivan@gmail.com', NULL, 'reset_success', '127.0.0.1', '2026-05-10 09:35:13'),
(7, 'petrova@gmail.com', '229174', NULL, '127.0.0.1', '2026-05-12 10:36:43'),
(8, 'petrova@gmail.com', NULL, 'reset_success', '127.0.0.1', '2026-05-12 10:36:55'),
(9, 'petrova@gmail.com', '751008', NULL, '127.0.0.1', '2026-05-12 15:41:59'),
(10, 'petrova@gmail.com', '435032', NULL, '127.0.0.1', '2026-05-12 16:58:00'),
(11, 'petrova@gmail.com', '616202', NULL, '127.0.0.1', '2026-05-12 21:52:23'),
(12, 'katya@gmail.com', '263772', NULL, '127.0.0.1', '2026-05-13 08:07:29'),
(13, 'katya@gmail.com', NULL, 'reset_success', '127.0.0.1', '2026-05-13 08:07:44'),
(14, 'katya@gmail.com', '335056', NULL, '127.0.0.1', '2026-05-13 08:11:43'),
(15, 'katya@gmail.com', '996222', NULL, '127.0.0.1', '2026-05-13 08:22:33'),
(16, 'katya@gmail.com', NULL, 'reset_success', '127.0.0.1', '2026-05-13 08:22:51'),
(17, 'ivan@gmail.com', '817599', NULL, '127.0.0.1', '2026-05-23 11:06:50'),
(18, 'nikita@gmail.com', '815831', NULL, '127.0.0.1', '2026-05-26 16:31:42'),
(19, 'nikita@gmail.com', NULL, 'reset_success', '127.0.0.1', '2026-05-26 16:31:59');

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `duration` int NOT NULL,
  `notes` text,
  `date` datetime NOT NULL,
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `type`, `duration`, `notes`, `date`, `status`) VALUES
(2, 1, 'vipassana', 14, '', '2026-04-23 13:11:38', 'completed'),
(3, 1, 'vipassana', 10, '', '2026-04-23 14:59:44', 'completed'),
(4, 1, 'samatha', 120, '', '2026-04-29 16:25:33', 'completed'),
(5, 1, 'vipassana', 1800, '', '2026-04-30 12:37:37', 'completed'),
(6, 1, 'metta', 1800, '', '2026-04-30 12:42:41', 'completed'),
(7, 1, 'metta', 26, '', '2026-04-30 12:45:24', 'completed'),
(8, 1, 'metta', 1200, NULL, '2026-04-28 15:51:40', 'completed'),
(9, 1, 'samatha', 9, '', '2026-04-30 15:55:46', 'completed'),
(10, 1, 'vipassana', 12, '', '2026-05-01 11:27:40', 'completed'),
(17, 1, 'samatha', 18, '', '2026-05-10 17:08:50', 'completed'),
(21, 2, 'vipassana', 6, '', '2026-05-12 13:34:52', 'completed'),
(22, 10, 'metta', 11, '', '2026-05-12 13:39:01', 'completed'),
(23, 10, 'samatha', 13, '', '2026-05-12 13:39:48', 'completed'),
(24, 10, 'vipassana', 12, '', '2026-05-12 13:40:07', 'completed'),
(25, 10, 'vipassana', 2, '', '2026-05-12 13:43:19', 'completed'),
(26, 10, 'samatha', 2, '', '2026-05-12 13:43:27', 'completed'),
(27, 10, 'metta', 2, '', '2026-05-12 13:43:35', 'completed'),
(28, 2, 'samatha', 1, '', '2026-05-12 14:02:28', 'completed'),
(29, 2, 'metta', 1, '', '2026-05-12 14:02:35', 'completed'),
(31, 1, 'samatha', 120, '', '2026-05-13 00:54:00', 'completed'),
(32, 1, 'vipassana', 3, '', '2026-05-13 11:02:02', 'completed'),
(33, 19, 'samatha', 5, '123', '2026-05-13 11:24:11', 'completed'),
(34, 23, 'vipassana', 19, '123 :)', '2026-05-26 15:04:04', 'completed'),
(36, 23, 'vipassana', 2, '', '2026-05-26 15:05:05', 'completed'),
(37, 23, 'vipassana', 1, '', '2026-05-26 15:06:39', 'completed'),
(38, 23, 'vipassana', 4, '123 :)', '2026-05-26 15:08:30', 'completed'),
(39, 23, 'samatha', 1, '', '2026-05-26 19:15:57', 'completed');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `status` enum('pending','active','blocked') DEFAULT 'pending',
  `avatar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'avatar1.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` tinyint(1) DEFAULT '0',
  `verify_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `city`, `password_hash`, `role`, `status`, `avatar`, `created_at`, `verified`, `verify_token`) VALUES
(1, 'Кирилл', 'ivan@gmail.com', '+78005553535', 'Омск', '$2y$10$RoSXG16eVn29uhYO9F1PbeF5S53PZm.ZJqQVTpiKGNa6suTXYQsSi', 'user', 'pending', 'avatar6.jpg', '2026-05-11 16:04:42', 1, NULL),
(2, 'Админ', 'admin@gmail.com', '+79999999999', 'Москва', '$2y$10$fLgMNMbJHqwdKS5ZitXDpu/ISWVMh7an6XRQ0RazA9mfcnWeFu4Zy', 'admin', 'pending', 'avatar1.jpg', '2026-05-11 16:04:42', 1, NULL),
(10, 'Ольга', 'petrova@gmail.com', '+79999999999', 'Москва', '$2y$10$bccZPB8AXFMqE72YcQE.sudP6PnjWIhwayNDzQjtfqN7y9Q02fP9y', 'user', 'pending', 'avatar1.jpg', '2026-05-12 10:35:32', 1, 'ef0d60f7bc417af6d417dfd07d40c313a5b9780018593d85567fedaaac2849c9'),
(19, 'Екатерина', 'katya@gmail.com', '+7 (555) 555-55-54', 'Москва', '$2y$10$CAEwuyvK5iQFpQEyYb6kbuS7s45R7lRzgRPtLKtdyrlWVZvzKxI7.', 'user', 'active', 'avatar3.jpg', '2026-05-13 08:22:11', 1, NULL),
(21, 'Ольга', 'olga@gmail.com', '+7 (888) 888-88-88', 'Москва', '$2y$10$vOhCe3cTjvX4sTH7Ixb8zOnerRtfF9lE73PwFv.2UJzpzxGKzHUie', 'user', 'pending', 'avatar1.jpg', '2026-05-23 14:21:43', 1, NULL),
(22, 'Кирилл', 'kirill@gmail.com', '+7 (000) 000-00-00', 'Москва', '$2y$10$8vbkKqMXP2NMpEUm8MjciOknmCTHkUML6G8Okaunq4dy4ATYplNOW', 'user', 'pending', 'avatar1.jpg', '2026-05-25 12:58:18', 0, NULL),
(23, 'Никита', 'nikita@gmail.com', '+7 (999) 999-99-98', 'Архангельск', '$2y$10$aI.XBiVyoMJ5e/AnpS6gHOpydTO3BBPxpWHFiYWxjhv1egIBCzhHi', 'user', 'pending', 'avatar2.jpg', '2026-05-26 12:02:38', 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `achievement_id` int NOT NULL,
  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `unlocked_at`) VALUES
(1, 1, 1, '2026-04-30 12:55:46'),
(2, 1, 2, '2026-05-10 10:10:41'),
(3, 1, 6, '2026-05-10 10:11:03'),
(4, 2, 1, '2026-05-12 10:34:52'),
(7, 10, 1, '2026-05-12 10:43:19'),
(8, 10, 2, '2026-05-12 10:43:19'),
(9, 2, 2, '2026-05-12 11:02:35'),
(11, 1, 3, '2026-05-12 21:54:38'),
(13, 19, 1, '2026-05-13 08:24:11'),
(14, 23, 1, '2026-05-26 12:08:30'),
(15, 23, 2, '2026-05-26 12:08:30');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `incense`
--
ALTER TABLE `incense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Индексы таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_ip_success` (`ip_address`,`success`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_at`),
  ADD KEY `idx_email_time` (`email`,`attempt_at`),
  ADD KEY `idx_success` (`success`);

--
-- Индексы таблицы `password_logs`
--
ALTER TABLE `password_logs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `incense`
--
ALTER TABLE `incense`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `password_logs`
--
ALTER TABLE `password_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `incense`
--
ALTER TABLE `incense`
  ADD CONSTRAINT `incense_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `user_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

DELIMITER $$
--
-- События
--
CREATE DEFINER=`root`@`%` EVENT `cleanup_login_attempts` ON SCHEDULE EVERY 1 DAY STARTS '2026-05-12 21:41:57' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM login_attempts WHERE attempt_at < DATE_SUB(NOW(), INTERVAL 30 DAY)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
