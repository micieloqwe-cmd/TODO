-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 10:50 AM
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
-- Database: `todo_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

CREATE TABLE `todos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `todos`
--

INSERT INTO `todos` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(2, 1, 'พัฒนาหน้าเว็บระบบ E-commerce', 'กำลังพัฒนา', 'done', 'medium', '2026-03-30', '2026-03-30 08:15:04', '2026-03-30 08:15:13'),
(3, 1, 'พัฒนา Feature ระบบจัดการสินค้า (Product Management)', 'กำลังทำ', 'pending', 'medium', '2026-04-01', '2026-03-30 08:16:17', '2026-03-30 08:16:17'),
(4, 1, 'พัฒนา Module ระบบสั่งซื้อ (Order System)', 'เขียนฟังก์ชันเพิ่มสินค้าในตะกร้า และคำนวณยอดรวม', 'pending', 'high', '2026-03-30', '2026-03-30 08:16:58', '2026-03-30 08:16:58'),
(5, 1, 'ออกแบบหน้าจอเว็บไซต์ (Website UI Design)', 'ออกแบบ Wireframe และ Prototype พร้อมปรับปรุง UX จาก User Flow เพื่อเพิ่มความสะดวกในการใช้งาน', 'pending', 'high', '2026-04-21', '2026-03-30 08:17:53', '2026-03-30 08:17:53'),
(6, 1, 'สร้าง Wireframe สำหรับหน้าเว็บ', '', 'pending', 'low', '2026-03-30', '2026-03-30 08:18:19', '2026-03-30 08:18:19'),
(7, 1, 'ปรับ Layout ให้รองรับ Mobile (Responsive Design)', '', 'pending', 'high', '2026-03-31', '2026-03-30 08:18:40', '2026-03-30 08:18:40'),
(8, 1, 'วิเคราะห์พฤติกรรมผู้ใช้งาน (User Flow)', '', 'pending', 'medium', '2026-04-16', '2026-03-30 08:18:57', '2026-03-30 08:18:57'),
(9, 1, 'ปรับปรุงประสบการณ์ผู้ใช้งาน (UX Improvement)', '', 'pending', 'high', '2026-04-23', '2026-03-30 08:19:17', '2026-03-30 08:19:17'),
(10, 1, 'พัฒนา API สำหรับระบบเว็บไซต์', '', 'pending', 'low', '2026-03-31', '2026-03-30 08:19:46', '2026-03-30 08:19:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'PLA PLA', 'micieloqwe@gmail.com', '$2y$12$xwnDc/cpXwQoazeIuOGMIOF342fZt.afgDI3hiaQMEPAZMSIU1c9G', 'user', '2026-03-30 08:01:49'),
(2, 'Admin', 'Admin@gmail.com', '$2y$12$dkfNwG8E1.iAOruorhneMuiLtUZWoko0AomMOMC7WkDZsEOyAQb8e', 'admin', '2026-03-30 08:21:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `todos`
--
ALTER TABLE `todos`
  ADD PRIMARY KEY (`id`),
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
-- AUTO_INCREMENT for table `todos`
--
ALTER TABLE `todos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `todos`
--
ALTER TABLE `todos`
  ADD CONSTRAINT `todos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
