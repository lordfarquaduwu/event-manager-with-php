-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 29, 2025 at 04:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `event_id`, `user_id`, `email`, `rating`, `comment`, `created_at`, `deleted_at`, `deleted_by`) VALUES
(4, 7, 6, 'rahimi@gmail.com', 5, '', '2025-10-27 13:30:31', NULL, NULL),
(5, 3, 6, 'rahimi@gmail.com', 5, '', '2025-10-27 13:30:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments_audit`
--

CREATE TABLE `comments_audit` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `action` varchar(32) DEFAULT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments_audit`
--

INSERT INTO `comments_audit` (`id`, `comment_id`, `action`, `admin_email`, `details`, `created_at`) VALUES
(1, 2, 'delete', 'admin@gmail.com', 'soft delete by admin@gmail.com', '2025-10-27 13:13:58'),
(2, 1, 'delete', 'admin@gmail.com', 'soft delete by admin@gmail.com', '2025-10-27 13:14:00'),
(3, 3, 'delete', 'admin@gmail.com', 'soft delete by admin@gmail.com', '2025-10-27 13:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `venue`, `created_by`, `created_at`, `image`) VALUES
(1, '🎪🌟 UTB Carnival 🌟🎪', 'Annual technology conference with industry experts and workshops', '2025-11-10', 'Multipurpose Hall', 1, '2025-10-19 12:44:20', 'includes/events/1761561146_mpputb_1760750754_3745771276238541428_335949465.webp'),
(2, 'Inno-Faith 2025', 'The event shone in its diverse array of booths. Guests explored enlightening displays from Islamic scholar booths, gaining deeper insights into the synergy between Islamic principles and modern progress. ', '2025-10-20', 'Multipurpose Hall', 1, '2025-10-19 12:44:20', ''),
(3, 'BYTE WALK🏃🏻💙', 'The \"Byte Walk\", held as a prelude to our convocation ceremony, served as a meaningful and unifying event.', '2025-10-23', 'Taman Mahkota Jubli Emas, Bandar Seri Begawan', 1, '2025-10-19 12:44:20', 'includes/events/1761560929_Screenshot_2025-10-27_182841.png'),
(4, 'UTB On The Move', 'Celebrating diversity with performances, food, and art', '2025-12-12', 'Lobby Academic Building', 1, '2025-10-19 12:44:20', 'includes/events/1761560774_WhatsApp_Image_2025-10-14_at_20.29.53_d0ef2f83.jpg'),
(5, '⛱️🍃UTB HEALING DAY🍃⛱️', 'Our UTB Healing event at the Multipurpose Hall was a beautiful and meaningful day with the goal was to provide a peaceful space for our community to de-stress, connect, and find a sense of well-being.', '2025-10-30', 'Multipurpose Hall', 1, '2025-10-19 12:44:20', 'includes/events/1761561444_mpputb_1760754265_3745800731459816956_335949465.webp'),
(6, 'Solat Hajat', 'Solat Hajat sebelum peperiksaan', '2025-11-06', 'Lobby Academic Building', 1, '2025-10-19 12:44:20', 'includes/events/1761560864_WhatsApp_Image_2025-10-24_at_10.55.08_24a2b7f8.jpg'),
(7, 'Israk Mikraj', 'Sambutan Israk Mikraj', '2025-11-03', 'Multipurpose Hall', 1, '2025-10-19 12:44:20', 'includes/events/1761560599_Screenshot_2025-10-27_181734.png'),
(8, 'Raya AidilFitri', 'Sambutan Hari Raya Aidilfitri', '2025-05-09', 'Multipurpose Hall', 1, '2025-10-19 12:44:20', 'includes/events/1761560821_Teks-Ucapan-Hari-Raya-Aidilfitri-2206072618.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `user_id`, `fullname`, `email`, `dob`, `active`, `registered_at`) VALUES
(3, 2, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 13:54:35'),
(4, 3, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 13:55:04'),
(5, 5, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 13:55:06'),
(8, 6, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 14:06:23'),
(9, 8, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 14:06:25'),
(10, 7, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-20 14:06:26'),
(15, 1, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-25 12:54:39'),
(16, 4, 6, 'rahimi', 'rahimi@gmail.com', '2025-10-08', 1, '2025-10-27 10:16:22'),
(17, 4, 8, 'salam', 'B20240235@student.utb.edu.bn', '2025-10-02', 1, '2025-10-29 14:55:27'),
(18, 1, 8, 'salam', 'B20240235@student.utb.edu.bn', '2025-10-02', 1, '2025-10-29 14:55:28'),
(19, 6, 8, 'salam', 'B20240235@student.utb.edu.bn', '2025-10-02', 1, '2025-10-29 14:55:29'),
(21, 5, 8, 'salam', 'B20240235@student.utb.edu.bn', '2025-10-02', 1, '2025-10-29 14:55:32'),
(22, 3, 8, 'salam', 'B20240235@student.utb.edu.bn', '2025-10-02', 1, '2025-10-29 14:55:34');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 4, 'cd4d2b13111a200639397e40af8eeb05474fb8f33725a457fe12366ac7068c58', '2025-10-20 11:28:48', 0, '2025-10-20 08:28:48'),
(2, 4, '4556ed0040cfb42b5c46978248aeb7944a600ac6812a3b4087fac19c768f263d', '2025-10-20 11:30:46', 0, '2025-10-20 08:30:46'),
(3, 4, '099ab9ca27e104d59a04ea3a9e7d3085a66d5cee87f262a7d91f0c4d96bc29f3', '2025-10-20 11:34:05', 0, '2025-10-20 08:34:05'),
(4, 4, '3300d9e7338da2981c2ddf771635e48226f49a02df3c2a135774369aad435da4', '2025-10-20 11:36:44', 0, '2025-10-20 08:36:44'),
(5, 4, '977979d815ed883208639c7ab121ffcdb974774b748100e760e0efbb5d0df079', '2025-10-20 11:36:54', 0, '2025-10-20 08:36:54'),
(6, 4, 'ab56a58e82e8f3c23383765b033126ade1566756387b01f78059ba35cbe76607', '2025-10-20 11:36:57', 0, '2025-10-20 08:36:57'),
(7, 4, '8b49eea1b1af23384f08b6964a304bd3218d520a774c656776076e18b66e3a7d', '2025-10-20 11:40:44', 0, '2025-10-20 08:40:44'),
(8, 4, '2f0d6c4b08bc6d24f3131c7c73efcdd9ad60bf8d48abd44b94b67333311da03d', '2025-10-20 11:43:21', 0, '2025-10-20 08:43:21'),
(9, 4, '2d605e918181f6ff797d76e94c30acf7dc2f40f1635ab7c4792a1d59c93474e5', '2025-10-20 11:45:21', 1, '2025-10-20 08:45:21'),
(10, 4, '951f7bf3664895908ba461556d4ad96718f8b802d8462cb5939cbc6ab09085ad', '2025-10-20 12:06:04', 0, '2025-10-20 09:06:04'),
(11, 4, 'bbd00a57dabe812a60625fd90325e541ee6751d387b4957d443bbd533727dec8', '2025-10-20 12:06:17', 1, '2025-10-20 09:06:17'),
(12, 4, 'c5f9e97f39924f1ae3a13eae8ed74694a51e8d0fe057c9b42397d4c331c0dd80', '2025-10-20 12:25:14', 1, '2025-10-20 09:25:14'),
(13, 1, 'e54d9df128b7113dc2b893830dd7c75aa26519f2cd12549b1a9312a3806afbc9', '2025-10-20 12:27:01', 1, '2025-10-20 09:27:01'),
(14, 3, 'a12d8c2d3bc4a22fbe3181744a28f1f36035bfce16791a5dbd5a67cb3ea1a78e', '2025-10-20 12:31:00', 0, '2025-10-20 09:31:00'),
(15, 3, '1fb0cca21f99f12ac493ef413d4cde4df3a158fa15bb6669e1de08ba18fa46f2', '2025-10-20 12:34:17', 1, '2025-10-20 09:34:17'),
(16, 5, 'a0665ac70ffb1271b355b1334e723ab30f32bc00b752ca4a5ac491fb72df7942', '2025-10-20 12:38:05', 1, '2025-10-20 09:38:05'),
(17, 6, 'fc9140db5ab962b1dd35da03ebbe112f5d54511a96e1da08f10869509e0e129d', '2025-10-29 16:54:42', 0, '2025-10-29 14:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fullname` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `created_at`, `fullname`, `dob`, `profile_pic`) VALUES
(1, 'asyraaf@gmail.com', '$2y$10$d9QetDwN0CAcis1jkwnH.OOvOamcf3sfYMW3lbfxU.oRhv5zifitK', 'admin', '2025-10-19 12:41:43', 'Md. Asyraaf Bin Hj Yusof', '2003-04-04', NULL),
(3, 'hadihisyam@gmail.com', '$2y$10$U2ysvT41zn9Ub9Mc4LigXeVtfCerGZFZnImTBrjTyeSxOriy77Pem', 'student', '2025-10-20 08:15:11', 'Muhammad Hisyam Masyhadi Bin Hj Hishamddin', '2006-02-09', NULL),
(4, 'hadihisham72@gmail.com', '$2y$10$sRZs7kmGPEJVyuGA.FtcEO2LtN3389rjhZM0hRh3M2w2BYZ1lh6FG', 'student', '2025-10-20 08:27:41', 'Asyraaf Hadi', '2003-01-08', NULL),
(5, 'arif@gmail.com', '$2y$10$WsQeeMawUHMdC.tWKxgF8ORKHi7U3YuMVxJaju/dlzwk2Eu1ohmWu', 'admin', '2025-10-20 09:35:46', 'Arif Ahmad Bin Sofyan', '2001-10-10', NULL),
(6, 'rahimi@gmail.com', '$2y$10$YJJV50x0WLDRTfXhGVGcOO9aHCDtwgXTA6tVC/zU8JcIe0F/A7qZy', 'student', '2025-10-20 13:53:04', 'rahimi', '2025-10-08', 'includes/profile_pics/6_profile.png'),
(7, 'admin@gmail.com', '$2y$10$GCcUns.JLtESFxpnsK1brO18ib4NZnCe38wmSTxls0bsRRDXzDMyu', 'admin', '2025-10-21 12:26:41', 'admin', '2025-10-03', 'includes/profile_pics/7_profile.jpg'),
(8, 'B20240235@student.utb.edu.bn', '$2y$10$z7F0OsOvbo5aKTwIicrP6eoudA.RQNKaoSH1TdXNnl2HXsnyrtWTC', 'student', '2025-10-29 14:55:01', 'salam', '2025-10-02', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments_audit`
--
ALTER TABLE `comments_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_user` (`event_id`,`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments_audit`
--
ALTER TABLE `comments_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
