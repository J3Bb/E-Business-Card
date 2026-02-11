-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 11, 2026 at 03:31 AM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Table structure for table `ecard_logs`
--

CREATE TABLE `ecard_logs` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `visitor_ip` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ecard_logs`
--

INSERT INTO `ecard_logs` (`id`, `manager_id`, `visitor_ip`, `city`, `region`, `country`, `device_info`, `accessed_at`) VALUES
(1, 14, '127.0.0.1', 'Unknown', 'Unknown', 'Unknown', 'Mozilla/5.0 (X11; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-10 08:46:52'),
(2, 14, '127.0.0.1', 'Local', 'Host', 'ID', 'Mozilla/5.0 (X11; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-10 08:46:52'),
(3, 13, '127.0.0.1', 'Unknown', 'Unknown', 'Unknown', 'Mozilla/5.0 (X11; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-10 08:46:54'),
(4, 13, '127.0.0.1', 'Local', 'Host', 'ID', 'Mozilla/5.0 (X11; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-10 08:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `managers`
--

CREATE TABLE `managers` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `phone_personal` varchar(20) DEFAULT NULL,
  `phone_office` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `last_log_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `managers`
--

INSERT INTO `managers` (`id`, `slug`, `name`, `title`, `phone_personal`, `phone_office`, `email`, `photo`, `views`, `last_log_time`) VALUES
(13, 'budi-herianto', 'Budi Herianto', 'Director of Marketing', '08123213123', '+62 123123321', 'budi@gmail.com', 'Budi.png', 31, NULL),
(14, 'bro-ahmad', 'Bro Ahmad', 'Magang', '0817123123', '+62 812381237', 'Ahmad@gmail.com', 'Budi.png', 39, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `manager_stats`
--

CREATE TABLE `manager_stats` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `click_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager_stats`
--

INSERT INTO `manager_stats` (`id`, `manager_id`, `visit_date`, `click_count`) VALUES
(1, 13, '2026-02-09', 4),
(4, 14, '2026-02-09', 3),
(8, 14, '2026-02-10', 9),
(10, 13, '2026-02-10', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ecard_logs`
--
ALTER TABLE `ecard_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `manager_stats`
--
ALTER TABLE `manager_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `manager_id` (`manager_id`,`visit_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ecard_logs`
--
ALTER TABLE `ecard_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `managers`
--
ALTER TABLE `managers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `manager_stats`
--
ALTER TABLE `manager_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ecard_logs`
--
ALTER TABLE `ecard_logs`
  ADD CONSTRAINT `ecard_logs_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
