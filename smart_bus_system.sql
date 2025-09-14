-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2025 at 05:16 PM
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
-- Database: `smart_bus_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `bus_number` varchar(20) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT 50,
  `current_passengers` int(11) DEFAULT 0,
  `current_latitude` decimal(10,8) DEFAULT NULL,
  `current_longitude` decimal(11,8) DEFAULT NULL,
  `speed_kmh` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive','maintenance','delayed') DEFAULT 'active',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_number`, `route_id`, `capacity`, `current_passengers`, `current_latitude`, `current_longitude`, `speed_kmh`, `status`, `last_updated`, `driver_name`, `driver_phone`) VALUES
(1, 'BANGALORE-001', 2, 50, 45, 12.96960000, 77.56860000, 22.00, 'delayed', '2025-09-13 13:49:55', 'Driver 1 (Bangalore)', '9669689439'),
(2, 'BANGALORE-002', 3, 50, 7, 12.97560000, 77.60960000, 22.00, 'active', '2025-09-13 13:49:55', 'Driver 2 (Bangalore)', '9327127935'),
(3, 'BANGALORE-003', 3, 50, 19, 13.00660000, 77.61960000, 45.00, 'delayed', '2025-09-13 13:49:55', 'Driver 3 (Bangalore)', '9968202963'),
(4, 'BANGALORE-004', 2, 50, 26, 12.94660000, 77.54460000, 26.00, 'active', '2025-09-13 13:49:55', 'Driver 4 (Bangalore)', '9945269879'),
(5, 'BANGALORE-005', 2, 50, 10, 12.98660000, 77.60660000, 37.00, 'active', '2025-09-13 13:49:55', 'Driver 5 (Bangalore)', '9517018738'),
(6, 'BANGALORE-006', 2, 50, 13, 13.01760000, 77.55660000, 21.00, 'delayed', '2025-09-13 13:49:55', 'Driver 6 (Bangalore)', '9333587314'),
(7, 'BANGALORE-007', 2, 50, 8, 13.01860000, 77.58960000, 20.00, 'delayed', '2025-09-13 13:49:55', 'Driver 7 (Bangalore)', '9294265658'),
(8, 'BANGALORE-008', 3, 50, 26, 12.94860000, 77.55160000, 32.00, 'delayed', '2025-09-13 13:49:55', 'Driver 8 (Bangalore)', '9358784684'),
(9, 'DELHI-001', 6, 50, 13, 28.62990000, 77.18100000, 36.00, 'active', '2025-09-13 13:49:55', 'Driver 1 (Delhi)', '9570645073'),
(10, 'DELHI-002', 6, 50, 12, 28.57890000, 77.19800000, 19.00, 'active', '2025-09-13 13:49:55', 'Driver 2 (Delhi)', '9639113786'),
(11, 'DELHI-003', 5, 50, 34, 28.60990000, 77.20500000, 29.00, 'delayed', '2025-09-13 13:49:55', 'Driver 3 (Delhi)', '9760863113'),
(12, 'DELHI-004', 4, 50, 36, 28.63390000, 77.21000000, 22.00, 'active', '2025-09-13 13:49:55', 'Driver 4 (Delhi)', '9470952154'),
(13, 'DELHI-005', 6, 50, 28, 28.64990000, 77.16500000, 16.00, 'delayed', '2025-09-13 13:49:55', 'Driver 5 (Delhi)', '9673679207'),
(14, 'DELHI-006', 4, 50, 10, 28.59390000, 77.25800000, 26.00, 'active', '2025-09-13 13:49:55', 'Driver 6 (Delhi)', '9277555493'),
(15, 'DELHI-007', 4, 50, 36, 28.58090000, 77.16700000, 32.00, 'active', '2025-09-13 13:49:55', 'Driver 7 (Delhi)', '9750837044'),
(16, 'DELHI-008', 5, 50, 36, 28.64390000, 77.16800000, 24.00, 'delayed', '2025-09-13 13:49:55', 'Driver 8 (Delhi)', '9679902217'),
(17, 'PUNE-001', 8, 50, 34, 18.47340000, 73.88970000, 18.00, 'active', '2025-09-13 13:49:55', 'Driver 1 (Pune)', '9639154438'),
(18, 'PUNE-002', 8, 50, 25, 18.52840000, 73.87870000, 35.00, 'delayed', '2025-09-13 13:49:55', 'Driver 2 (Pune)', '9578749232'),
(19, 'PUNE-003', 7, 50, 25, 18.53540000, 73.87870000, 17.00, 'active', '2025-09-13 13:49:55', 'Driver 3 (Pune)', '9909288498'),
(20, 'PUNE-004', 7, 50, 28, 18.53040000, 73.82270000, 23.00, 'active', '2025-09-13 13:49:55', 'Driver 4 (Pune)', '9201569034'),
(21, 'PUNE-005', 8, 50, 7, 18.56440000, 73.85070000, 42.00, 'active', '2025-09-13 13:49:55', 'Driver 5 (Pune)', '9406976543'),
(22, 'PUNE-006', 8, 50, 24, 18.54740000, 73.80770000, 16.00, 'active', '2025-09-13 13:49:55', 'Driver 6 (Pune)', '9539205172'),
(23, 'PUNE-007', 7, 50, 5, 18.56940000, 73.85670000, 33.00, 'delayed', '2025-09-13 13:49:55', 'Driver 7 (Pune)', '9419333670'),
(24, 'PUNE-008', 9, 50, 10, 18.48940000, 73.81670000, 22.00, 'active', '2025-09-13 13:49:55', 'Driver 8 (Pune)', '9730534975');

-- --------------------------------------------------------

--
-- Table structure for table `bus_schedules`
--

CREATE TABLE `bus_schedules` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `scheduled_departure` datetime NOT NULL,
  `actual_departure` datetime DEFAULT NULL,
  `scheduled_arrival` datetime DEFAULT NULL,
  `actual_arrival` datetime DEFAULT NULL,
  `delay_minutes` int(11) DEFAULT 0,
  `status` enum('scheduled','running','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bus_stops`
--

CREATE TABLE `bus_stops` (
  `id` int(11) NOT NULL,
  `stop_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `city_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `code`, `latitude`, `longitude`, `is_active`) VALUES
(1, 'Bangalore', 'BLR', 12.97160000, 77.59460000, 1),
(2, 'Delhi', 'DEL', 28.61390000, 77.20900000, 1),
(3, 'Pune', 'PUN', 18.52040000, 73.85670000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `passenger_analytics`
--

CREATE TABLE `passenger_analytics` (
  `id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `stop_id` int(11) DEFAULT NULL,
  `hour_of_day` int(11) DEFAULT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `predicted_passengers` int(11) DEFAULT NULL,
  `actual_passengers` int(11) DEFAULT NULL,
  `date_recorded` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `city_id` int(11) DEFAULT NULL,
  `route_code` varchar(20) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `start_point` varchar(100) NOT NULL,
  `end_point` varchar(100) NOT NULL,
  `distance_km` decimal(5,2) DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `city_id`, `route_code`, `route_name`, `start_point`, `end_point`, `distance_km`, `estimated_duration`, `is_active`, `created_at`) VALUES
(1, 1, 'BLR-001', 'Whitefield-Majestic', 'Whitefield', 'Majestic', 25.50, 90, 1, '2025-09-13 10:26:27'),
(2, 1, 'BLR-002', 'Electronic City-KR Puram', 'Electronic City', 'KR Puram', 35.20, 110, 1, '2025-09-13 10:26:27'),
(3, 1, 'BLR-003', 'Hebbal-Banashankari', 'Hebbal', 'Banashankari', 28.80, 95, 1, '2025-09-13 10:26:27'),
(4, 2, 'DEL-001', 'ISBT-Connaught Place', 'ISBT Kashmere Gate', 'Connaught Place', 15.20, 45, 1, '2025-09-13 10:26:27'),
(5, 2, 'DEL-002', 'Rohini-India Gate', 'Rohini Sector 3', 'India Gate', 22.80, 75, 1, '2025-09-13 10:26:27'),
(6, 2, 'DEL-003', 'Dwarka-CP', 'Dwarka Sector 21', 'Connaught Place', 28.50, 85, 1, '2025-09-13 10:26:27'),
(7, 3, 'PUN-001', 'Katraj-Swargate', 'Katraj', 'Swargate', 12.50, 35, 1, '2025-09-13 10:26:27'),
(8, 3, 'PUN-002', 'Hinjewadi-Pune Station', 'Hinjewadi Phase 1', 'Pune Railway Station', 18.30, 55, 1, '2025-09-13 10:26:27'),
(9, 3, 'PUN-003', 'Kothrud-Camp', 'Kothrud Depot', 'Camp Area', 8.70, 25, 1, '2025-09-13 10:26:27');

-- --------------------------------------------------------

--
-- Table structure for table `route_stops`
--

CREATE TABLE `route_stops` (
  `id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `stop_id` int(11) DEFAULT NULL,
  `stop_sequence` int(11) NOT NULL,
  `estimated_time_from_start` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_alerts`
--

CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('info','warning','error','success') DEFAULT 'info',
  `message` text NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_alerts`
--

INSERT INTO `system_alerts` (`id`, `alert_type`, `message`, `bus_id`, `route_id`, `is_resolved`, `created_at`, `resolved_at`, `created_by`) VALUES
(1, 'info', 'User Activity: User registered. Username: ruchit, Email: ravalruchi@gmail.com', NULL, NULL, 0, '2025-09-13 10:42:53', NULL, 3),
(2, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 10:43:21', NULL, 3),
(3, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 10:44:00', NULL, 3),
(4, 'info', 'User Activity: User registered. Username: devid, Email: devid@gmail.com', NULL, NULL, 0, '2025-09-13 10:45:46', NULL, 4),
(5, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 10:46:33', NULL, 4),
(6, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 10:47:41', NULL, 4),
(7, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 10:47:56', NULL, 4),
(8, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 10:49:57', NULL, 4),
(9, 'info', 'User Activity: User registered. Username: RK, Email: ravalruchit@gmail.com', NULL, NULL, 0, '2025-09-13 11:03:43', NULL, 5),
(10, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 11:03:58', NULL, 5),
(11, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 12:35:54', NULL, 5),
(12, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 13:29:41', NULL, 5),
(13, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 13:32:08', NULL, 5),
(14, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 14:14:15', NULL, 20),
(15, 'info', 'User Activity: User logged out. ', NULL, NULL, 0, '2025-09-13 15:13:52', NULL, 20),
(16, 'info', 'User Activity: User logged in. IP: ::1', NULL, NULL, 0, '2025-09-13 15:13:55', NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('admin','operator','passenger') DEFAULT 'passenger',
  `city` varchar(50) DEFAULT 'bangalore',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `city`, `created_at`, `updated_at`, `is_active`) VALUES
(3, 'ruchit', 'ravalruchi@gmail.com', '$2y$10$96GvMZ7B7sKdpsjOX9J7K./bdrA2YJrIxH84EB2kQ9zTy0dPptQbe', 'R K Raval', '6354739398', 'passenger', 'DEL', '2025-09-13 10:42:53', '2025-09-13 10:43:21', 1),
(4, 'devid', 'devid@gmail.com', '$2y$10$K1nEZcaPHsRz/P5F9iJV3eOac.hJ9G323So3sJbrkAyKUOc.Mpwv6', 'Devid Patel', '7984222627', 'passenger', 'BLR', '2025-09-13 10:45:46', '2025-09-13 10:47:56', 1),
(5, 'RK', 'ravalruchit@gmail.com', '$2y$10$n0BmNRXSzpL17scJ6uh/OeaFRGNZgl5P0Y/z9R5bDeZ7IJIwe8IdO', 'R K', '6354739398', 'passenger', 'DEL', '2025-09-13 11:03:43', '2025-09-13 15:13:55', 1),
(20, 'admin', 'admin@smartbus.com', '$2y$10$dpWO3d.tZ3dE5sRyrNVqqeTJUG2.tPBg.BZteysMcF05HlPJVd3bm', 'System Administrator', NULL, 'admin', 'bangalore', '2025-09-13 14:09:56', '2025-09-13 14:14:15', 1),
(21, 'operator1', 'operator@smartbus.com', '$2y$10$sSDPbeaozwOiIHT7cYisVOw9lJB9G6g15ucp1sYeG4zRSKTb52kbK', 'Bus Operator', NULL, 'operator', 'bangalore', '2025-09-13 14:09:56', '2025-09-13 14:09:56', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(7, 5, '8321cc1771e3972bc6dff31cdc3be02a166a29fa6dfa62f0105df590f9a543d9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-14 15:13:55', '2025-09-13 15:13:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bus_number` (`bus_number`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `bus_stops`
--
ALTER TABLE `bus_stops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `passenger_analytics`
--
ALTER TABLE `passenger_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `stop_id` (`stop_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `route_stops`
--
ALTER TABLE `route_stops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `stop_id` (`stop_id`);

--
-- Indexes for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_stops`
--
ALTER TABLE `bus_stops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `passenger_analytics`
--
ALTER TABLE `passenger_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `route_stops`
--
ALTER TABLE `route_stops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_alerts`
--
ALTER TABLE `system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buses`
--
ALTER TABLE `buses`
  ADD CONSTRAINT `buses_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  ADD CONSTRAINT `bus_schedules_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`),
  ADD CONSTRAINT `bus_schedules_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `bus_stops`
--
ALTER TABLE `bus_stops`
  ADD CONSTRAINT `bus_stops_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`);

--
-- Constraints for table `passenger_analytics`
--
ALTER TABLE `passenger_analytics`
  ADD CONSTRAINT `passenger_analytics_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`),
  ADD CONSTRAINT `passenger_analytics_ibfk_2` FOREIGN KEY (`stop_id`) REFERENCES `bus_stops` (`id`);

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`);

--
-- Constraints for table `route_stops`
--
ALTER TABLE `route_stops`
  ADD CONSTRAINT `route_stops_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`),
  ADD CONSTRAINT `route_stops_ibfk_2` FOREIGN KEY (`stop_id`) REFERENCES `bus_stops` (`id`);

--
-- Constraints for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD CONSTRAINT `system_alerts_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`),
  ADD CONSTRAINT `system_alerts_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`),
  ADD CONSTRAINT `system_alerts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
