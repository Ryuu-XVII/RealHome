-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2024 at 08:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `real_estate`;

-- Select the database for use
USE `real_estate`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

-- Table structure for table `agents`

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` char(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `agents`

INSERT INTO `agents` (`id`, `name`, `username`, `password_hash`, `email`, `phone`, `photo`, `created_at`, `password`) VALUES
(1, 'Adnaan Hoosen', 'Adnaan', '', 'adnaanhoosen51@gmail.com', '0635192284', 'uploads/Screenshot 2024-05-21 143556.png', '2024-09-18 15:51:56', '$2y$10$z.xoBzJlwNXyNN3GWf53m.77DVWHG.1cEwKa0ILQqhNSgD9We5OvC'),
(4, 'Adnaan Hoosen', 'Adnaan2', '', 'adnaanhoosen52@gmail.com', '0635192284', 'uploads/Screenshot 2024-05-21 141735.png', '2024-09-18 16:10:16', '$2y$10$109Ih2tn51A1m9NANsAO5uzFZ18w1cqzOGF6NuEct3mGF93a/0AeW');

-- --------------------------------------------------------

-- Table structure for table `properties`

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `agent_username` varchar(50) NOT NULL,
  `property_type` enum('house','apartment','land','commercial') NOT NULL,
  `listing_type` enum('rent','buy') NOT NULL,
  `address` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `images` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `properties`

INSERT INTO `properties` (`id`, `agent_username`, `property_type`, `listing_type`, `address`, `price`, `images`, `created_at`) VALUES
(1, 'Adnaan', 'house', 'buy', '13 road', 99999999.99, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-21 140421.png\";}', '2024-09-18 17:10:08'),
(2, 'Adnaan', 'house', 'rent', '13 road', 99999999.99, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-02 114110.png\";}', '2024-09-18 17:11:44'),
(3, 'Adnaan', 'house', 'rent', '13 road', 99999999.99, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-07 125150.png\";}', '2024-09-18 17:13:58'),
(4, 'Adnaan', 'house', 'rent', '13 road', 99999999.99, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-02 082340.png\";}', '2024-09-18 17:15:54'),
(5, 'Adnaan', 'house', 'rent', '13 road', 99999999.99, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-07 125150.png\";}', '2024-09-18 17:17:37'),
(6, 'Adnaan', 'house', 'rent', '13 road', 64.00, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-02 082340.png\";}', '2024-09-18 17:26:22'),
(7, 'Adnaan', 'house', 'rent', '13 road', 64.00, 'a:1:{i:0;s:40:\"uploads/Screenshot 2024-05-07 125150.png\";}', '2024-09-18 17:29:58');

-- --------------------------------------------------------

-- Table structure for table `sessions`

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_id` char(64) NOT NULL,
  `username` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `users`

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` char(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for dumped tables

-- Indexes for table `agents`
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

-- Indexes for table `properties`
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_username` (`agent_username`);

-- Indexes for table `sessions`
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `username` (`username`);

-- Indexes for table `users`
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

-- AUTO_INCREMENT for dumped tables

-- AUTO_INCREMENT for table `agents`
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- AUTO_INCREMENT for table `properties`
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- AUTO_INCREMENT for table `sessions`
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `users`
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Constraints for dumped tables

-- Constraints for table `properties`
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`agent_username`) REFERENCES `agents` (`username`);

-- Constraints for table `sessions`
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
