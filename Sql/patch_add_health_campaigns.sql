-- Table structure for table `health_campaigns`
CREATE TABLE `health_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `details` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optionally, insert a sample campaign for testing
INSERT INTO `health_campaigns` (`title`, `details`, `start_date`, `end_date`) VALUES
('Dengue Awareness Drive', 'Community-wide dengue prevention and awareness campaign.', '2026-02-01', '2026-02-28');
