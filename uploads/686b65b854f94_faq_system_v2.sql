-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jul 04, 2025 at 10:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `faq_system_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_faqs`
--

CREATE TABLE `admin_faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `status` varchar(50) DEFAULT 'not resolved',
  `topic` varchar(100) DEFAULT 'Others',
  `attachment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_faqs`
--

INSERT INTO `admin_faqs` (`id`, `question`, `answer`, `status`, `topic`, `attachment`, `created_at`) VALUES
(16, '2025 - Blaire', 'Test Blaire', 'not resolved', 'IT Procedures', 'uploads/686765f7d8810_admin php.txt', '2025-07-04 07:01:27');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_faqs`
--

CREATE TABLE `deleted_faqs` (
  `id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `status` enum('resolved','not resolved') DEFAULT 'not resolved',
  `topic` varchar(100) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `deleted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `origin` varchar(50) DEFAULT 'faqs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_faqs`
--

INSERT INTO `deleted_faqs` (`id`, `original_id`, `question`, `answer`, `status`, `topic`, `attachment`, `deleted_by`, `deleted_date`, `origin`) VALUES
(18, 11, 'd', 'd', 'resolved', 'User Guides', 'uploads/686763d00a232_admin php.txt', 'admin', '2025-07-04 05:25:49', 'admin_faqs');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `status` enum('resolved','not resolved') DEFAULT 'not resolved',
  `topic` varchar(100) DEFAULT NULL,
  `attachment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visibility` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `status`, `topic`, `attachment`, `created_at`, `visibility`) VALUES
(6, 'Test Concern 1', '.', 'resolved', 'Backup & Recovery', 'uploads/6864ed157e466_DEFECTS.xlsx', '2025-07-03 03:50:36', 'user'),
(8, 'Test Delete 1', '1', 'resolved', 'Troubleshooting', 'uploads/6865ded8a6445_NOC LCD MONITOR.txt', '2025-07-03 03:50:45', 'user'),
(9, 'vv', 'dfv', 'not resolved', 'IT Announcements', 'uploads/6865ffcd59bca_PF1GBQRN.txt', '2025-07-03 03:58:25', 'user'),
(10, 'Test Concern Latest 1', 'a', 'resolved', 'File Sharing & Drives', 'uploads/6865eff3d6af7_PF1GBQRN.txt', '2025-07-03 03:58:56', 'user'),
(14, 'aq', 'aq', 'not resolved', 'Troubleshooting', 'uploads/6865fdf85b931_index php.txt', '2025-07-03 23:48:27', 'user'),
(15, 'admin', 'admin', 'resolved', 'Backup & Recovery', 'uploads/6866008b4f86c_PF1GBQRN.txt', '2025-07-03 23:48:34', 'user'),
(16, 'd', 'd', 'resolved', 'IT Announcements', 'uploads/68662e39b4d41_DEFECTS.xlsx', '2025-07-03 23:48:40', 'user'),
(17, 'Test', '.', 'resolved', 'Device Setup', 'uploads/686716eaaf6eb_Service Invoice # 0003.jpg', '2025-07-03 23:49:09', 'user'),
(19, 'aa', 'a', 'resolved', 'Troubleshooting', NULL, '2025-07-04 05:17:29', 'user'),
(20, 'Beta', 'b', 'resolved', 'Troubleshooting', 'uploads/686766b20d470_index php.txt', '2025-07-04 05:29:22', 'user'),
(21, '1234', '123', 'not resolved', 'Account Issues', NULL, '2025-07-04 06:14:28', 'user'),
(22, 'blank', 'a', 'resolved', 'Account Issues', NULL, '2025-07-04 07:31:48', 'user'),
(23, 'ffff', 'ff', 'not resolved', 'Account Issues', NULL, '2025-07-04 07:33:55', 'user'),
(24, 'testestests', 'sas', 'not resolved', 'Device Setup', NULL, '2025-07-04 07:55:41', 'user'),
(25, 'tytyty', 'tytyt', 'not resolved', 'Account Issues', NULL, '2025-07-04 07:57:51', 'user'),
(26, 'bfbfgbf', 'gbgfbfbgfb', 'not resolved', 'Account Issues', NULL, '2025-07-04 08:01:24', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$IvUJMi79SFUeYdt3l2S6B.Jn9nghBqCgpzGWTa25ktVm4pYnuzrNS', 'kurtmacasling11@gmail.com', 'admin'),
(10, 'Denz', '$2y$10$Y6XjlULiMFaUw9aZNtvUneObqu1eii6RMFoqO4xoWMzNnZJ7kYrNC', 'denz@gmail.com', 'user'),
(11, 'Blaire', '$2y$10$LlnvXyi2h36a1WYf3vf0eO5UzW2xmv1dCztgBFgs8xp3b0i/EGiX.', 'blaire@gmail.com', 'admin'),
(12, 'Mawrin', '$2y$10$G/kpWX6bmCGzDWpHEifJCevbB7Cy5qokgpVD5.VZRum5tysngiGwK', 'marwin@gmail.com', 'admin'),
(13, 'Jasper', '$2y$10$8kHWKOK.2cuowCPx1SBrr.RWvw3ssa5zjLsw.y0Cey0/.9MNJHdea', 'jasper@gmail.com', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_faqs`
--
ALTER TABLE `admin_faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_faqs`
--
ALTER TABLE `deleted_faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_faqs`
--
ALTER TABLE `admin_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `deleted_faqs`
--
ALTER TABLE `deleted_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
