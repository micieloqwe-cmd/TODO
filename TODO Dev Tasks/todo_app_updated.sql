-- ==========================================
-- todo_app_updated.sql
-- Run this to set up / update your database
-- ==========================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `todo_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `todo_app`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL,
  `password`   VARCHAR(255)  NOT NULL,
  `role`       ENUM('user','admin') DEFAULT 'user',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Todos table (updated with priority)
CREATE TABLE IF NOT EXISTS `todos` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)       NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `status`      ENUM('pending','done') DEFAULT 'pending',
  `priority`    ENUM('low','medium','high') DEFAULT 'medium',
  `due_date`    DATE          DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `todos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add priority column if upgrading from old schema
ALTER TABLE `todos`
  ADD COLUMN IF NOT EXISTS `priority` ENUM('low','medium','high') DEFAULT 'medium' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `created_at`;

COMMIT;
