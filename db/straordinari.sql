-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 24, 2025 at 03:56 PM
-- Server version: 9.1.0
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `straordinari`
--

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `color` varchar(7) NOT NULL DEFAULT '#6c757d',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `created_at`, `color`) VALUES
(11, 'Defenda', '2025-07-04 16:04:36', '#353fb5'),
(12, 'Italian Luxury Villas', '2025-07-04 16:04:57', '#e4cb18'),
(13, 'Euroansa', '2025-07-04 16:05:12', '#2227ff');

-- --------------------------------------------------------

--
-- Table structure for table `extra_hours`
--

DROP TABLE IF EXISTS `extra_hours`;
CREATE TABLE IF NOT EXISTS `extra_hours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(4,2) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_date` (`company_id`,`date`),
  KEY `idx_extra_hours_company` (`company_id`),
  KEY `fk_user` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `extra_hours`
--

INSERT INTO `extra_hours` (`id`, `company_id`, `date`, `hours`, `description`, `created_at`, `updated_at`, `user_id`) VALUES
(15, 11, '2025-07-01', 1.00, 'Primo giorno in Defenda', '2025-07-04 16:05:52', '2025-07-24 08:59:09', 1),
(16, 12, '2025-07-02', 0.50, 'Whatsapp API - Integrazione download pdf', '2025-07-04 16:06:19', '2025-07-24 08:59:11', 1),
(17, 12, '2025-07-03', 1.00, 'Whatsapp API - Integrazione download media e catch della lingua dei templates dall\'API', '2025-07-04 16:06:40', '2025-07-24 08:59:12', 1),
(18, 11, '2025-07-04', 0.50, 'Tool Referrer HTTP', '2025-07-04 16:08:41', '2025-07-24 08:59:14', 1),
(19, 11, '2025-07-07', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-07 10:43:34', '2025-07-24 08:59:15', 1),
(20, 11, '2025-07-08', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-08 15:50:55', '2025-07-24 08:59:16', 1),
(22, 12, '2025-07-09', 0.50, 'Aggiornamento CRM PHP8', '2025-07-09 13:39:57', '2025-07-24 08:59:17', 1),
(23, 11, '2025-07-10', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-10 13:37:06', '2025-07-24 08:59:18', 1),
(24, 11, '2025-07-11', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-11 09:44:34', '2025-07-24 08:59:19', 1),
(25, 11, '2025-07-14', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-14 08:30:57', '2025-07-24 08:59:19', 1),
(26, 11, '2025-07-15', 0.50, 'Backend - progetto anti-phishing gif', '2025-07-15 15:55:57', '2025-07-24 08:59:20', 1),
(27, 13, '2025-07-15', 0.50, 'EuroansaJobs - Grafici report nel CRM', '2025-07-15 15:57:26', '2025-07-24 08:59:40', 1),
(28, 11, '2025-07-16', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-16 13:08:25', '2025-07-24 08:59:42', 1),
(29, 11, '2025-07-18', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-18 11:00:07', '2025-07-24 08:59:43', 1),
(30, 11, '2025-07-21', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-21 10:26:28', '2025-07-24 08:59:44', 1),
(31, 11, '2025-07-22', 0.50, 'Progetto referer HTTP GIF antiphishing', '2025-07-22 15:52:33', '2025-07-24 08:59:51', 1),
(32, 11, '2025-07-23', 1.00, 'Progetto referer HTTP GIF antiphishing', '2025-07-23 16:14:42', '2025-07-24 08:59:57', 1),
(33, 13, '2025-07-24', 1.00, 'EuroansaJobs - Grafici report nel CRM', '2025-07-24 15:54:07', '2025-07-24 15:54:39', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'Piccio', '$2y$10$fFSpB3/BdAQqLujClqEToeeJMzha1o/SPB3sOmOxhGC3oNI6C5RjC', 'admin');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
