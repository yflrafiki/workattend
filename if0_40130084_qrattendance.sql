-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Oct 29, 2025 at 05:54 AM
-- Server version: 10.6.22-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40130084_qrattendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `username`, `password`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'sarah', 'sarah', '$2y$10$xdQPB8G9LpWLUfVSZ9503O18/QNtG0XWXNAl.YWmfxujX03JM/vlq', 'justjhoey060@gmail.com', 'admin', 1, '2025-10-29 09:16:18', '2025-10-29 09:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `departure_time` time DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `user_latitude` decimal(10,8) DEFAULT NULL,
  `user_longitude` decimal(11,8) DEFAULT NULL,
  `location_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `name`, `status`, `date`, `time`, `departure_time`, `device_id`, `user_latitude`, `user_longitude`, `location_status`, `created_at`) VALUES
(1, 1, 'taylor', 'Intern', '2025-10-28', '10:32:42', NULL, 'device_69007eedae9bd_464333d534c820ed', '5.69777110', '-0.17650550', 'verified', '2025-10-28 10:32:42'),
(2, 2, 'test name', 'Staff', '2025-10-28', '11:24:22', NULL, 'device_6900a72797422_d8faa5c34adcd1f4', '5.69777710', '-0.17651300', 'verified', '2025-10-28 11:24:22'),
(3, 3, 'Anita', 'Intern', '2025-10-28', '11:38:21', NULL, 'device_6900aada2a97c_e379a660925ce7d3', '5.69776970', '-0.17649370', 'verified', '2025-10-28 11:38:21'),
(4, 4, 'Joseph Tinel', 'Intern', '2025-10-28', '11:40:38', '17:00:54', 'device_6900ab8507d05_e9d30d79c16fde8e', '5.69775580', '-0.17647480', 'verified', '2025-10-28 11:40:38'),
(5, 4, 'Joseph Tinel', 'Intern', '2025-10-29', '09:16:58', NULL, 'device_6900ab8507d05_e9d30d79c16fde8e', '5.69776920', '-0.17649640', 'verified', '2025-10-29 09:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Staff','Intern') NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `status`, `password_hash`, `device_id`, `is_approved`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'taylor', 'rafikitaylor828@gmail.com', '0501161755', 'Intern', '$2y$10$bEBq0Frj3w9VM0rdrS2icOYTOQlLh1IMTV1SA2AjtRRwoFg/ElbFK', 'device_69007eedae9bd_464333d534c820ed', 1, 1, '2025-10-28 08:30:24', '2025-10-28 08:29:33', '2025-10-28 08:30:24'),
(2, 'test name', 'kylumiry@forexzig.com', '05599522807', 'Staff', '$2y$10$4oXUEgMM4TzEUKiKJspEm.ZfP1UeSWvHxm5HTiraIQJQmtLI4xvmu', 'device_6900a72797422_d8faa5c34adcd1f4', 1, 1, '2025-10-28 11:21:54', '2025-10-28 11:21:11', '2025-10-28 11:21:54'),
(3, 'Anita', 'ratdanger828@gmail.com', '0202750533', 'Intern', '$2y$10$Le5V7/xkj8jssl4/l1cVHufMrRJ41Lakq.oA4obGcPaN63AYIOqUS', 'device_6900aada2a97c_e379a660925ce7d3', 1, 1, '2025-10-28 11:37:45', '2025-10-28 11:36:58', '2025-10-28 11:37:45'),
(4, 'Joseph Tinel', 'tarrymae4423@gmail.com', '501161755', 'Intern', '$2y$10$JyVJvtBO6yiHY8ZJjEwVbeoyMH.v9dqHzyvkVLnr/G5cDqZ6c4xBi', 'device_6900ab8507d05_e9d30d79c16fde8e', 1, 1, '2025-10-28 11:40:12', '2025-10-28 11:39:49', '2025-10-28 11:40:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
