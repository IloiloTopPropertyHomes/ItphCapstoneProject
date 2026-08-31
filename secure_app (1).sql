-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2026 at 05:41 PM
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
-- Database: `secure_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `gmail`, `password`, `created_at`, `last_login`, `verified`) VALUES
(6, 'ITPH Admin', 'itph934@gmail.com', '$2y$10$gdg1U/bCPgsWfBHldjPkp.YJ4.2c5RJA7BJNahSJ1DMN5WvARTTX.', '2026-04-15 12:37:20', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `verified` tinyint(1) DEFAULT 0,
  `login_attempts` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `fullname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `username`, `gmail`, `password`, `created_at`, `last_login`, `phone`, `status`, `verified`, `login_attempts`, `last_attempt`, `fullname`) VALUES
(4, 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', '$2y$10$huCatnHp/zKEVg0mK2Gd8u.bjFXCiNqK.sVxH9s9ERSvRkMjUnkZ2', '2026-03-31 11:58:48', '2026-07-24 19:59:02', '09123454321', 'Active', 0, 0, '2026-04-01 21:30:06', ''),
(5, 'akitoshi', 'balingasa@gmail.com', '$2y$10$Oe/jiQKJD7QEr7g//AAYn.HwUUms9cDzH8L4FnCWmCoh3.6sicKga', '2026-05-11 14:37:22', NULL, '09164639837', 'Active', 0, 0, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `auth_logs`
--

CREATE TABLE `auth_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('customer','agent','admin') NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `login_status` enum('success','failed') NOT NULL,
  `login_method` enum('email','google') NOT NULL,
  `session_status` enum('online','offline') NOT NULL DEFAULT 'online',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_logs`
--

INSERT INTO `auth_logs` (`id`, `user_id`, `role`, `fullname`, `email`, `login_status`, `login_method`, `session_status`, `ip_address`, `user_agent`, `activity_time`, `last_activity`) VALUES
(34, 0, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 11:17:11', NULL),
(35, 22, 'customer', 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '', 'email', 'online', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 14:18:30', '2026-08-11 23:41:12'),
(36, 0, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 12:02:06', NULL),
(37, 22, 'customer', 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', 'success', 'email', 'online', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 14:20:30', '2026-08-11 23:41:12'),
(38, 22, 'customer', 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '', 'email', 'online', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 14:21:17', '2026-08-11 23:41:12'),
(39, 22, 'customer', 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', 'success', 'email', 'online', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-08-11 15:41:12', '2026-08-11 23:41:12'),
(40, 0, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-13 15:22:12', NULL),
(41, NULL, 'customer', 'Alexander John Caligan', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:31:21', NULL),
(42, NULL, 'customer', 'Alexander John Caligan', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:31:27', NULL),
(43, 0, 'customer', 'Alexander John Caligan', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:32:16', NULL),
(44, 0, 'customer', 'Alexander John Caligan', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 01:33:13', NULL),
(45, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 02:13:13', NULL),
(46, 0, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-18 11:49:08', NULL),
(47, 0, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-18 11:49:13', NULL),
(48, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', '', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-18 11:50:05', NULL),
(49, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-18 12:08:18', NULL),
(50, NULL, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 05:30:34', NULL),
(51, NULL, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 05:45:04', NULL),
(52, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 05:48:31', NULL),
(53, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 05:55:46', NULL),
(54, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 06:01:41', NULL),
(55, 0, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'failed', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:33:15', NULL),
(56, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:33:28', NULL),
(57, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:33:28', NULL),
(58, 24, 'customer', 'test123333', 'vistoalexanderjohn1@gmail.com', '', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:33:59', NULL),
(59, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:49:18', NULL),
(60, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-30 01:54:53', NULL),
(61, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-01 02:40:01', NULL),
(62, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 02:24:24', NULL),
(63, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 03:14:54', NULL),
(64, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 03:19:04', NULL),
(65, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 03:20:33', NULL),
(66, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 03:23:18', NULL),
(67, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-03 03:24:24', NULL),
(68, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-10 00:54:50', NULL),
(69, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 01:23:49', NULL),
(70, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 05:25:13', NULL),
(71, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-17 08:15:29', NULL),
(72, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 03:38:01', NULL),
(73, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 03:46:51', NULL),
(74, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 12:45:16', NULL),
(75, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 02:47:24', NULL),
(76, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 02:48:19', NULL),
(77, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 04:06:09', NULL),
(78, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 11:40:10', NULL),
(79, 4, 'agent', 'AgentAlex123', 'vistoalexanderjohn1@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 11:59:02', NULL),
(80, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-05 13:50:17', NULL),
(81, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-08-05 14:11:49', NULL),
(82, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-08 14:09:08', NULL),
(83, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'offline', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-11 07:12:17', NULL),
(84, 6, 'admin', 'ITPH Admin', 'itph934@gmail.com', 'success', 'email', 'online', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', '2026-08-11 15:36:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_logs`
--

CREATE TABLE `chat_logs` (
  `id` int(11) NOT NULL,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_logs`
--

INSERT INTO `chat_logs` (`id`, `user_message`, `bot_response`, `created_at`) VALUES
(1, 'hi', 'I am currently offline. Please try again later.', '2026-03-19 05:43:53'),
(2, 'company', 'I am currently offline. Please try again later.', '2026-03-19 05:44:53'),
(3, 'Company', 'I am currently offline. Please try again later.', '2026-03-19 05:45:00'),
(4, 'agents', 'I am currently offline. Please try again later.', '2026-03-20 14:20:33'),
(5, 'hi', 'I am currently offline. Please try again later.', '2026-03-20 14:20:38'),
(6, 'hi', 'I am currently offline. Please try again later.', '2026-03-25 07:53:13'),
(7, 'Contact', 'I am currently offline. Please try again later.', '2026-03-26 00:57:09'),
(8, 'See Property', 'You can view our available properties by clicking the button below.', '2026-03-26 02:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `message`, `created_at`) VALUES
(5, 'test 123', 'test123@gmail.com', '', 'mbbbbbbb', '2026-03-27 06:41:02'),
(6, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '', 'qeqweqw', '2026-05-11 15:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Other',
  `location` enum('Northern Iloilo','Central Iloilo','Southern Iloilo') NOT NULL,
  `status` enum('Local','OFW') NOT NULL,
  `otp` int(6) DEFAULT NULL,
  `otp_verified` tinyint(1) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `google_id` varchar(255) DEFAULT NULL,
  `profile_picture` text DEFAULT NULL,
  `login_provider` varchar(50) DEFAULT 'email',
  `google_login` tinyint(1) DEFAULT 0,
  `phone` varchar(30) DEFAULT NULL,
  `secondary_email` varchar(255) DEFAULT NULL,
  `suspicious_account` tinyint(1) DEFAULT 0,
  `admin_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `fullname`, `email`, `password`, `created_at`, `gender`, `location`, `status`, `otp`, `otp_verified`, `verified`, `google_id`, `profile_picture`, `login_provider`, `google_login`, `phone`, `secondary_email`, `suspicious_account`, `admin_note`) VALUES
(15, 'Christille Laurente', 'christillelaurente20@gmail.com', '$2y$10$jW7OdyCqAK4WimUgbXNlw.SOxSWdBbCyvwnnR7KtrZcrIB5A2HAbO', '2026-03-30 10:17:17', 'Female', 'Northern Iloilo', '', NULL, 1, 0, NULL, NULL, 'email', 0, NULL, NULL, 0, NULL),
(17, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '$2y$10$iAsdV2kbjhfL0CRBY71HAOZzPdY519BIW3.bf.sd7HhB5ipwe1r9e', '2026-04-03 11:47:14', 'Male', 'Southern Iloilo', 'OFW', NULL, 0, 0, '109312321595096701704', 'https://lh3.googleusercontent.com/a/ACg8ocIp5Y8kJvgDFqYx-4A9s88YFiZ_vUt0JkUvic0rqVsne_YVRGc=s96-c', 'email', 0, '09465963189', 'vistoalexanderjohn1@gmail.com', 0, NULL),
(18, 'IT Ph', 'itph934@gmail.com', '$2y$10$YygNxxJwpAMItDLZzdJN1eqcKpdLOpNG9kFvOYRoELL/orPxSRTFK', '2026-04-05 12:27:05', 'Male', 'Northern Iloilo', '', NULL, 0, 0, '115773682786715569560', 'https://lh3.googleusercontent.com/a/ACg8ocLsok_eSFyHRuDJYjlRXbwotq8ahmsGs8jo7r_mFpMSOt84CA=s96-c', 'email', 0, '091234567890', 'almo.caligan.ui@phinmaed.com', 0, NULL),
(22, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '$2y$10$J7SS0tF4A8MqgZudJbg5peI49ZpoJRfBO474pgCam0HZIXOgIc51e', '2026-05-05 14:31:21', 'Male', 'Northern Iloilo', 'Local', NULL, 0, 0, '112065847053683868281', 'https://lh3.googleusercontent.com/a/ACg8ocLxkT4m3lLKdA1DS4dx2cnnRT10AOP0Kq3XkWlSD-50-BeAd_PV=s96-c', 'email', 0, '09164639837', 'phmalvar@gmail.com', 0, NULL),
(23, 'Akitoshi', 'nikkiachrae@gmail.com', '$2y$10$Oi2yNcO61CHs.zNgoS6AjeYBaSel4zFQs1f2bv2JWgIEbPxO9jA82', '2026-05-11 14:27:04', 'Male', 'Central Iloilo', 'OFW', NULL, 0, 0, NULL, NULL, 'email', 0, NULL, NULL, 0, NULL),
(24, 'test123333', 'vistoalexanderjohn1@gmail.com', '$2y$10$o4uuSEUOUaVbDYFOkWYWX.SBjS1NHp7dc3OViPvMumgaoa96q18HO', '2026-05-16 01:35:46', 'Male', 'Southern Iloilo', 'Local', NULL, 0, 0, NULL, NULL, 'email', 0, '091234567892', 'almo.caligan.ui@phinmaed.com', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_auth_logs`
--

CREATE TABLE `customer_auth_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `role` varchar(20) DEFAULT 'customer',
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `login_status` varchar(20) DEFAULT NULL,
  `login_method` varchar(20) DEFAULT NULL,
  `session_status` varchar(20) DEFAULT 'online',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_bans`
--

CREATE TABLE `customer_bans` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('active','banned') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_bans`
--

INSERT INTO `customer_bans` (`id`, `customer_id`, `status`, `updated_at`) VALUES
(1, 18, 'active', '2026-04-25 00:39:30'),
(2, 17, 'active', '2026-04-25 00:39:33'),
(3, 24, 'banned', '2026-08-05 14:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'News',
  `author` varchar(100) DEFAULT 'Admin',
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'published',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `excerpt`, `content`, `image_path`, `category`, `author`, `featured`, `status`, `views`, `created_at`, `updated_at`) VALUES
(3, 'New Branch In ILOILO', 'new-branch-in-iloilo', 'ADS', 'New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO', NULL, 'Announcement', 'Admin', 0, 'published', 1, '2026-08-11 15:39:22', '2026-08-11 15:39:29'),
(4, 'New Branch In ILOILO New Branch In ILOILO', 'new-branch-in-iloilo-new-branch-in-iloilo', 'ADS', 'New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO New Branch In ILOILO', '1786462802_6a7b42521327b.png', 'Update', 'Admin', 0, 'published', 1, '2026-08-11 15:40:02', '2026-08-11 15:40:05');

-- --------------------------------------------------------

--
-- Table structure for table `news_likes`
--

CREATE TABLE `news_likes` (
  `id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `liker_key` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_likes`
--

INSERT INTO `news_likes` (`id`, `news_id`, `liker_key`, `created_at`) VALUES
(9, 2, 'user_22', '2026-08-05 13:10:44'),
(10, 3, 'user_22', '2026-08-11 15:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `reservation_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-29 14:18:55'),
(2, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-29 14:20:27'),
(3, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 03:43:17'),
(4, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 04:05:23'),
(5, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 04:13:41'),
(6, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 10:19:03'),
(7, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 11:48:00'),
(8, 1, NULL, 'Your booking is now confirmed, please look for the agent.', 0, '2026-03-30 15:01:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `type` enum('admin','agent','user') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `propertiies`
--

CREATE TABLE `propertiies` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `display_image` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `location` text NOT NULL,
  `description` text NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` int(11) NOT NULL,
  `image` text NOT NULL,
  `view_images` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `property_page` varchar(50) NOT NULL,
  `branch` enum('iloilo','bacolod') DEFAULT NULL,
  `reserved` int(11) DEFAULT 0,
  `available_units` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `propertiies`
--

INSERT INTO `propertiies` (`id`, `title`, `display_image`, `type`, `price`, `location`, `description`, `bedrooms`, `bathrooms`, `image`, `view_images`, `created_at`, `views`, `view_count`, `property_page`, `branch`, `reserved`, `available_units`) VALUES
(41, 'PHIRST', '', '', 1000000.00, 'Pavia', 'PHIRST HOUSE LOCATED AT ILOILO CITY', 2, 3, '1779773692_672210446_1669491534503898_918429277348776007_n.jpg', NULL, '2026-05-26 05:34:52', 29, 0, 'phrst', NULL, 0, 1),
(42, 'Phirst Iloilo Branch Lt. 123', '', '', 100000.00, 'San Miguel', 'This beautiful residential property offers a comfortable and peaceful living environment for families and individuals. It features spacious areas suitable for everyday activities, relaxation, and family gatherings. Located in a convenient neighborhood, the property provides easy access to nearby establishments and essential services. With its welcoming atmosphere and practical design, this home is an excellent choice for those looking for a comfortable place to live.', 2, 1, '1786434585_daniella_i.png', NULL, '2026-08-11 07:49:45', 8, 0, 'phrst', 'iloilo', 0, 12);

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image`) VALUES
(156, 41, '+mzJPGE0qDlcVkGARh9LRy74JRTDk0IiSWnKoDnss/h3IOCATIbz9JC/QGH143x7evAAvHNMd515XNRPi7iUUQ=='),
(157, 42, 'N7+Ne+FkvtOAKCu6W6i5+qrfYHG26Uv/Rbv+e/vFEBA='),
(158, 42, 'q1qHlSNA4mmLUEQ5+RdAO/KBf7BvHX5cBgmpPGAtVdk=');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `property` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Other',
  `location` enum('Northern Iloilo','Central Iloilo','Southern Iloilo') NOT NULL,
  `stats` enum('Local','OFW') NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `meeting_type` varchar(50) DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `payment_type` enum('Cash','Installment') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `fullname`, `email`, `phone`, `property`, `date`, `time`, `created_at`, `status`, `seen`, `notification_sent`, `gender`, `location`, `stats`, `agent_id`, `meeting_type`, `admin_message`, `payment_type`) VALUES
(62, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '09465963189', 'Alice Town House - monticello', '2026-04-26', '00:00:00', '2026-04-25 01:13:21', 'Pending', 0, 0, 'Male', 'Southern Iloilo', 'OFW', NULL, 'Office', NULL, NULL),
(63, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'Alice Town House - monticello', '2026-05-12', '00:00:00', '2026-05-11 13:22:58', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Office', NULL, NULL),
(64, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'Alice Town House - monticello', '2026-05-12', '00:00:00', '2026-05-11 13:24:06', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Office', NULL, NULL),
(65, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'AMANI TOWN HOUSE - amani', '2026-05-21', '00:00:00', '2026-05-11 15:02:47', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(66, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'AMANI TOWN HOUSE - amani', '2026-05-21', '00:00:00', '2026-05-11 15:04:07', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(67, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'Alice Town House - monticello', '2026-05-16', '00:00:00', '2026-05-12 17:35:01', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(68, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'Alice Town House - monticello', '2026-05-21', '00:00:00', '2026-05-12 17:35:25', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Office', NULL, NULL),
(69, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'Alice Town House - monticello', '2026-05-27', '00:00:00', '2026-05-12 17:42:10', 'Confirmed', 1, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(70, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'joshui - monticello', '2026-05-14', '00:00:00', '2026-05-13 12:03:57', 'Done', 1, 1, 'Male', 'Northern Iloilo', 'Local', 4, 'Onsite', NULL, 'Installment'),
(71, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'AMANI TOWN HOUSE - amani', '2026-05-14', '00:00:00', '2026-05-13 15:47:06', 'Done', 1, 1, 'Male', 'Northern Iloilo', 'Local', 4, 'Onsite', NULL, 'Installment'),
(72, 'test123333', 'vistoalexanderjohn1@gmail.com', '091234567892', 'PHIRST - phrst', '2026-05-27', '00:00:00', '2026-05-26 06:03:14', 'Done', 1, 1, 'Male', 'Southern Iloilo', 'Local', 4, 'Outside', NULL, 'Cash'),
(73, 'test123333', 'vistoalexanderjohn1@gmail.com', '091234567892', 'PHIRST - phrst', '2026-05-29', '00:00:00', '2026-05-26 06:10:36', 'Done', 0, 1, 'Male', 'Southern Iloilo', 'Local', 4, 'Onsite', NULL, 'Installment'),
(74, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '09465963189', 'PHIRST - phrst', '2026-07-05', '00:00:00', '2026-07-01 02:46:02', 'Confirmed', 0, 0, 'Male', 'Southern Iloilo', 'OFW', 4, 'Onsite', NULL, NULL),
(75, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '09465963189', 'PHIRST - phrst', '2026-07-02', '00:00:00', '2026-07-01 02:54:41', 'Done', 0, 1, 'Male', 'Southern Iloilo', 'OFW', 4, 'Outside', NULL, 'Cash'),
(76, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '09465963189', 'PHIRST - phrst', '2026-07-07', '00:00:00', '2026-07-03 02:41:21', 'Done', 0, 1, 'Male', 'Southern Iloilo', 'OFW', 6, 'Outside', NULL, 'Installment'),
(77, 'Alexander John Morento Caligan', 'almo.caligan.ui@phinmaed.com', '09465963189', 'PHIRST - phrst', '2026-07-22', '00:00:00', '2026-07-21 12:50:39', 'Confirmed', 0, 0, 'Male', 'Southern Iloilo', 'OFW', 6, 'Onsite', NULL, NULL),
(78, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:19:09', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(79, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:19:09', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(80, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:19:19', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(81, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:19:19', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Onsite', NULL, NULL),
(82, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:21:34', 'Pending', 0, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Outside', NULL, NULL),
(83, 'Jerecho Earl Balingasa', 'phmalvar@gmail.com', '09164639837', 'PHIRST - phrst', '2026-08-05', '08:00:00', '2026-08-05 13:21:34', 'Confirmed', 1, 0, 'Male', 'Northern Iloilo', 'Local', NULL, 'Outside', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_logs`
--

CREATE TABLE `transaction_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','agent') NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode` enum('online','offline') DEFAULT 'online'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_login`
--

CREATE TABLE `user_login` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vlogs`
--

CREATE TABLE `vlogs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'tour',
  `description` text DEFAULT NULL,
  `video_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gmail` (`gmail`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`gmail`);

--
-- Indexes for table `auth_logs`
--
ALTER TABLE `auth_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_auth_logs`
--
ALTER TABLE `customer_auth_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_bans`
--
ALTER TABLE `customer_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `news_likes`
--
ALTER TABLE `news_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_like` (`news_id`,`liker_key`),
  ADD KEY `idx_news` (`news_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `propertiies`
--
ALTER TABLE `propertiies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaction_logs`
--
ALTER TABLE `transaction_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_login`
--
ALTER TABLE `user_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `vlogs`
--
ALTER TABLE `vlogs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `auth_logs`
--
ALTER TABLE `auth_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `customer_auth_logs`
--
ALTER TABLE `customer_auth_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_bans`
--
ALTER TABLE `customer_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `news_likes`
--
ALTER TABLE `news_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `propertiies`
--
ALTER TABLE `propertiies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `transaction_logs`
--
ALTER TABLE `transaction_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_login`
--
ALTER TABLE `user_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `vlogs`
--
ALTER TABLE `vlogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_bans`
--
ALTER TABLE `customer_bans`
  ADD CONSTRAINT `customer_bans_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `propertiies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_login`
--
ALTER TABLE `user_login`
  ADD CONSTRAINT `user_login_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
