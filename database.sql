-- ========================================================
-- Database: aceassignmenthelp_db
-- Exported on: 2026-09-22 19:32:38
-- Standalone MySQL Schema & Seed Data
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `admins`
INSERT INTO `admins` (`id`, `admin_id`, `name`, `email`, `phone`, `password`, `status`) VALUES ('3', 'ADM-002', 'Gokul Marwal', 'gokulmarwal16@gmail.com', '8619513493', '$2y$10$5T5Dk1wv8iJ4MASmkqUEnOuAIvq2uA.ff99p3PbmlC6zbwXF8Ifje', 'Active');

-- --------------------------------------------------------
-- Table structure for `allocators`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `allocators`;
CREATE TABLE `allocators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `allocator_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `performance_score` decimal(5,2) DEFAULT '100.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocator_id` (`allocator_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `allocators`
INSERT INTO `allocators` (`id`, `allocator_id`, `name`, `email`, `phone`, `password`, `status`, `performance_score`) VALUES ('1', 'ALL-501', 'David Vance', 'allocator@aceassign.com', '+1 (555) 987-6543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.RAWefReD2', 'Active', '98.40');
INSERT INTO `allocators` (`id`, `allocator_id`, `name`, `email`, `phone`, `password`, `status`, `performance_score`) VALUES ('5', 'ALL-502', 'Emma Watson', 'emma.allocator@aceassign.com', '+1 (555) 444-3322', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.RAWefReD2', 'Active', '96.10');

-- --------------------------------------------------------
-- Table structure for `experts`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `experts`;
CREATE TABLE `experts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expert_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `subjects` text,
  `rating` decimal(3,2) DEFAULT '5.00',
  `completed_count` int DEFAULT '0',
  `status` varchar(50) DEFAULT 'Available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `expert_id` (`expert_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `experts`
INSERT INTO `experts` (`id`, `expert_id`, `name`, `email`, `phone`, `subjects`, `rating`, `completed_count`, `status`) VALUES ('1', 'EXP-301', 'Dr. Robert Vance (PhD)', 'robert.vance@experts.com', '+1 555 999 8888', '[\"Computer Science\",\"Artificial Intelligence\",\"Python\"]', '4.90', '142', 'Available');
INSERT INTO `experts` (`id`, `expert_id`, `name`, `email`, `phone`, `subjects`, `rating`, `completed_count`, `status`) VALUES ('2', 'EXP-302', 'Prof. Elena Rostova', 'elena.rostova@experts.com', '+44 7700 900123', '[\"Business Management\",\"Finance\",\"Economics\"]', '4.85', '98', 'Available');
INSERT INTO `experts` (`id`, `expert_id`, `name`, `email`, `phone`, `subjects`, `rating`, `completed_count`, `status`) VALUES ('3', 'EXP-303', 'Dr. Michael Zhang', 'michael.zhang@experts.com', '+61 400 123 999', '[\"Nursing & Healthcare\",\"Biology\",\"Medical Sciences\"]', '4.95', '210', 'Busy');
INSERT INTO `experts` (`id`, `expert_id`, `name`, `email`, `phone`, `subjects`, `rating`, `completed_count`, `status`) VALUES ('4', 'EXP-304', 'Clara Higgins (LLM)', 'clara.h@experts.com', '+1 555 333 4444', '[\"Law & Legal Studies\",\"Corporate Governance\"]', '4.78', '76', 'Available');

-- --------------------------------------------------------
-- Table structure for `students`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `university` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `students`
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `password`, `country`, `university`, `course`, `status`, `created_at`) VALUES ('1', 'STU-1001', 'Sarah Jenkins', 'sarah.jenkins@stanford.edu', '+1 (555) 234-5678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.RAWefReD2', 'United States', 'Stanford University', 'Computer Science', 'Active', '2026-08-15 10:30:00');
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `password`, `country`, `university`, `course`, `status`, `created_at`) VALUES ('2', 'STU-1002', 'Liam Hemsworth', 'liam.h@oxford.ac.uk', '+44 7911 123456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.RAWefReD2', 'United Kingdom', 'University of Oxford', 'Business Management', 'Active', '2026-08-20 14:15:00');
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `password`, `country`, `university`, `course`, `status`, `created_at`) VALUES ('3', 'STU-1003', 'Aarav Sharma', 'aarav.sharma@sydney.edu.au', '+61 412 345 678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.RAWefReD2', 'Australia', 'University of Sydney', 'Nursing & Healthcare', 'Active', '2026-08-28 09:00:00');

-- --------------------------------------------------------
-- Table structure for `assignments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `assignment_type` varchar(100) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `word_count` int DEFAULT '250',
  `pages` int DEFAULT '1',
  `reference_style` varchar(50) DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'Normal',
  `language` varchar(50) DEFAULT 'English (US)',
  `instructions` text,
  `currency` varchar(10) DEFAULT 'USD',
  `price` decimal(10,2) DEFAULT '0.00',
  `discount_code` varchar(50) DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'New',
  `allocator_id` varchar(50) DEFAULT NULL,
  `expert_id` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `university` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_id` (`assignment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `assignments`
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('19', 'ACE-2026-000101', 'STU-1001', 'Web Test 1', 'Data Science & Applied Statistics', 'Dissertation', '2026-09-26 12:10:36', 'EST (UTC-5)', '12000', '48', 'APA 7th', 'Normal', 'English (US)', 'Web Test 1', 'USD', '132.00', 'SUPER30', '92.40', 'Completed', 'ALL-501', 'EXP-304', 'United States', 'Stanford University', '2026-09-21 12:10:38', '2026-09-22 09:45:23');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('20', 'ACE-2026-000102', 'STU-1002', 'Global Supply Chain Disruptions Case Study', 'Business Management', 'Case Study', '2026-09-05 12:00:00', 'GMT (UTC+0)', '1800', '7', 'Harvard', 'Urgent', 'English (UK)', 'Analyze post-2024 maritime logistics bottlenecks in EU ports and recommend mitigation strategies.', 'USD', '140.00', '', '140.00', 'Quality Check', 'ALL-501', 'EXP-302', 'United Kingdom', 'University of Oxford', '2026-09-02 14:30:00', '2026-09-04 08:20:00');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('21', 'ACE-2026-000103', 'STU-1003', 'Patient Care Plan & Clinical Risk Assessment', 'Nursing & Healthcare', 'Coursework', '2026-09-08 23:59:00', 'AEST (UTC+10)', '3000', '12', 'APA 7th', 'Normal', 'English (UK)', 'Formulate a comprehensive care pathway for acute respiratory distress syndrome in geriatric patients.', 'USD', '210.00', 'FIRST15', '178.50', 'Confirmed', '', '', 'Australia', 'University of Sydney', '2026-09-03 16:45:00', '2026-09-18 12:15:17');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('22', 'ACE-2026-000104', 'STU-1001', 'Database Schema Design & SQL Queries', 'Computer Science', 'Programming / Code', '2026-09-10 17:00:00', 'EST (UTC-5)', '1000', '4', 'None', 'Normal', 'English (US)', 'Design ER diagram and write normalized SQL DDL and DML scripts for an e-commerce platform.', 'USD', '120.00', '', '120.00', 'Completed', 'ALL-502', 'EXP-301', 'United States', 'Stanford University', '2026-08-25 09:10:00', '2026-09-22 06:21:17');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('23', 'ACE-2026-000105', 'STU-1002', 'International Trade Law & Tariff Regulation', 'Law & Legal Studies', 'Essay', '2026-09-04 20:00:00', 'GMT (UTC+0)', '2000', '8', 'OSCOLA', 'Urgent', 'English (UK)', 'Critique recent WTO dispute settlement decisions regarding digital tariffs.', 'USD', '160.00', '', '160.00', 'Allocated', 'ALL-502', '', 'United Kingdom', 'University of Oxford', '2026-09-04 07:30:00', '2026-09-18 10:27:35');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('24', 'ACE-2026-000106', 'STU-8454', 'Test Tiered Pricing Assignment', 'General', 'Essay', '2026-09-11 08:56:00', 'EST (UTC-5)', '1000', '4', 'APA 7th', 'Normal', 'English (US)', '', 'INR', '1500.00', '', '1500.00', 'Confirmed', '', '', 'United States', 'Stanford University', '2026-09-09 08:56:01', '2026-09-21 08:59:02');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('25', 'ACE-2026-000111', 'STU-1001', 'test', 'Data Analytics & Business Intelligence', 'Dissertation', '2026-10-01 09:08:10', 'EST (UTC-5)', '750', '3', 'APA 7th', 'Normal', 'English (US)', 'test 1', 'INR', '750.00', 'ACE20', '487.50', 'Confirmed', '', '', 'United States', 'Stanford University', '2026-09-21 09:08:11', '2026-09-21 09:09:12');
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`) VALUES ('26', 'ACE-TEST-PERM-100', 'STU-1001', 'Browser Test for Permanent Delete InPage Modal', 'Cloud Computing Architecture', 'Research Paper', '2026-09-25 11:10:12', NULL, '1200', '4', NULL, 'Normal', 'English (US)', NULL, 'INR', '1200.00', NULL, '1000.00', 'Pending Review', NULL, NULL, 'United States', 'Stanford University', '2026-09-21 11:10:12', '2026-09-21 11:14:02');

-- --------------------------------------------------------
-- Table structure for `courses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Academic Discipline',
  `icon` varchar(100) DEFAULT 'fa-book-open',
  `description` text,
  `topics` text,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `courses`
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('1', 'CRS-101', 'Computer Science & Artificial Intelligence', 'Engineering & Technology', 'fa-laptop-code', 'Advanced computing assistance covering AI, algorithm optimization, cloud infrastructure, and full-stack software development.', '[\"Machine Learning & AI\",\"Data Structures & Algorithms\",\"Full-Stack Web Development\",\"Database Schema SQL\\/NoSQL\",\"Cyber Security & Encryption\",\"Python \\/ Java \\/ C++\"]', 'Active', '2026-09-18 09:17:09');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('2', 'CRS-102', 'Business Administration & MBA', 'Business & Management', 'fa-chart-pie', 'Executive business coursework support including financial modelling, strategic audits, supply chain, and global marketing.', '[\"Strategic Management\",\"Global Supply Chain Logistics\",\"Corporate Finance & Auditing\",\"Digital Marketing Strategy\",\"Human Resource Management\",\"Executive Leadership\"]', 'Active', '2026-09-18 09:17:10');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('3', 'CRS-103', 'Nursing & Clinical Healthcare', 'Health & Medical Sciences', 'fa-user-nurse', 'Comprehensive medical and nursing writing covering evidence-based care plans, pharmacological reviews, and clinical safety.', '[\"Evidence-Based Nursing\",\"Clinical Risk Assessment\",\"Pathophysiology & Pharmacology\",\"Public Health Policy\",\"Healthcare Ethics\",\"SOAP Notes & Care Plans\"]', 'Active', '2026-09-18 09:17:10');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('4', 'CRS-104', 'Law & Legal Studies', 'Law & Jurisprudence', 'fa-scale-balanced', 'Rigorous legal research and case analysis strictly styled to OSCOLA, Bluebook, and Harvard legal referencing frameworks.', '[\"International Trade Law\",\"Corporate Governance\",\"Criminal & Constitutional Law\",\"Intellectual Property Rights\",\"OSCOLA Legal Briefs & Case Critiques\"]', 'Active', '2026-09-18 09:17:10');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('5', 'CRS-105', 'Data Science & Applied Statistics', 'Mathematics & Analytics', 'fa-chart-line', 'Statistical computing and data analytics assistance using R Studio, SPSS, MATLAB, STATA, and Python pandas.', '[\"Predictive Modelling & Regression\",\"R & Python Statistical Analysis\",\"SPSS & STATA Datasets\",\"Hypothesis Testing & ANOVA\",\"Big Data Analytics & BI\"]', 'Active', '2026-09-18 09:17:11');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('6', 'CRS-106', 'Engineering & Physical Sciences', 'Engineering & Technology', 'fa-gear', 'Technical calculations, CAD schematics, simulation reports, and laboratory writeups for Civil, Mechanical, and Electrical disciplines.', '[\"Thermodynamics & Heat Transfer\",\"Structural Mechanics & FEA\",\"MATLAB & Simulink\",\"AutoCAD Design Projects\",\"Circuit Analysis & Embedded Systems\"]', 'Active', '2026-09-18 09:17:11');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('8', 'CRS-107', 'Data Analytics & Business Intelligence', 'Mathematics & Analytics', 'fa-book-open', 'Applied data visualization and enterprise business intelligence coursework help.', '[\"Power BI\",\"Tableau\",\"SQL Analytics\",\"Python Pandas\",\"Data Warehousing\"]', 'Active', '2026-09-18 10:01:09');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('9', 'CRS-108', 'Robotics & Embedded Systems', 'Engineering & Technology', 'fa-book-open', 'Robotics and embedded systems co Advanced modules.ursework.', '[\"Arduino\",\"ROS\",\"Microcontrollers\"]', 'Inactive', '2026-09-18 10:33:20');
INSERT INTO `courses` (`id`, `course_id`, `title`, `category`, `icon`, `description`, `topics`, `status`, `created_at`) VALUES ('10', 'CRS-109', 'Verification Test Course CRS-802', 'Engineering & Technology', 'fa-robot', 'Test course description', '[\"Robotics\",\"Automation\"]', 'Active', '2026-09-18 11:40:25');

-- --------------------------------------------------------
-- Table structure for `blogs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `excerpt` text,
  `content` longtext,
  `category` varchar(100) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `published_at` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `blogs`
INSERT INTO `blogs` (`id`, `title`, `excerpt`, `content`, `category`, `author`, `published_at`, `image`) VALUES ('1', '10 Master Strategies for Structuring a Top-Grade University Dissertation', 'Discover how leading researchers organize literature reviews, methodology sections, and empirical findings for maximum academic impact.', NULL, 'Academic Writing', 'Dr. Robert Vance', '2026-08-20', 'blog-dissertation.jpg');
INSERT INTO `blogs` (`id`, `title`, `excerpt`, `content`, `category`, `author`, `published_at`, `image`) VALUES ('2', 'Demystifying Scikit-Learn Hyperparameter Tuning in Machine Learning', 'A practical guide to cross-validation, grid search, and Bayesian optimization in Python.', NULL, 'Computer Science', 'Dr. Michael Zhang', '2026-08-25', 'blog-ml.jpg');
INSERT INTO `blogs` (`id`, `title`, `excerpt`, `content`, `category`, `author`, `published_at`, `image`) VALUES ('3', 'Mastering OSCOLA & APA 7th Referencing Standards Without Headaches', 'Avoid plagiarism flags and citation errors with our comprehensive referencing quick-reference chart.', NULL, 'Study Tips', 'Clara Higgins', '2026-09-01', 'blog-referencing.jpg');

-- --------------------------------------------------------
-- Table structure for `coupons`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `max_uses` int DEFAULT '100',
  `current_uses` int DEFAULT '0',
  `expires_at` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_id` (`coupon_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `coupons`
INSERT INTO `coupons` (`id`, `coupon_id`, `code`, `discount_percent`, `max_uses`, `current_uses`, `expires_at`, `status`) VALUES ('1', 'CPN-1', 'ACE20', '20.00', '100', '29', '2026-12-31', 'Active');
INSERT INTO `coupons` (`id`, `coupon_id`, `code`, `discount_percent`, `max_uses`, `current_uses`, `expires_at`, `status`) VALUES ('2', 'CPN-2', 'FIRST15', '80.00', '500', '112', '2026-12-31', 'Active');
INSERT INTO `coupons` (`id`, `coupon_id`, `code`, `discount_percent`, `max_uses`, `current_uses`, `expires_at`, `status`) VALUES ('3', 'CPN-3', 'SUPER30', '30.00', '100', '51', '2026-09-30', 'Active');

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` varchar(50) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_id` (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('25', 'NTF-1', '', 'Allocator', 'New Assignment Received', 'Assignment ACE-2026-000101 (Web Test 1) has been submitted and awaits allocation.', '0', '2026-09-21 12:10:40');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('26', 'NTF-2', '', 'Allocator', 'Order Confirmed & Paid: ACE-2026-000101', 'Payment of $92.40 (USD) received via Stripe Credit Card. Assignment is ready for expert allocation.', '0', '2026-09-21 13:04:22');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('27', 'NTF-3', 'STU-1001', 'Student', 'Payment Confirmed - Order ACE-2026-000101', 'Thank you! Your payment of $92.40 has been verified. Your assignment is now Confirmed and allocated to a subject specialist.', '0', '2026-09-21 13:04:23');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('28', 'NTF-4', 'ALL-501', 'Allocator', 'Expert Started Order ACE-2026-000101', 'Expert Clara Higgins (LLM) has accepted and started work on order ACE-2026-000101.', '0', '2026-09-21 13:30:11');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('29', 'NTF-5', 'ALL-501', 'Allocator', 'Solution Submitted - ACE-2026-000101', 'Expert Clara Higgins (LLM) submitted the final solution for order ACE-2026-000101. Ready for QA review.', '0', '2026-09-22 06:10:22');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('30', 'NTF-6', 'ADM-01', 'Admin', 'Solution Submitted - ACE-2026-000101', 'Expert Clara Higgins (LLM) uploaded solution for ACE-2026-000101.', '0', '2026-09-22 06:10:22');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('31', 'NTF-7', 'ALL-502', 'Allocator', 'Solution Submitted - ACE-2026-000104', 'Expert Dr. Robert Vance (PhD) submitted the final solution for order ACE-2026-000104. Ready for QA review.', '0', '2026-09-22 06:20:01');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('32', 'NTF-8', '', 'Admin', 'Solution Submitted - ACE-2026-000104', 'Expert Dr. Robert Vance (PhD) uploaded final solution for order ACE-2026-000104. Ready for Quality Check review.', '0', '2026-09-22 06:20:02');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('33', 'NTF-9', 'EXP-301', 'Expert', 'Solution Approved - ACE-2026-000104', 'Your solution for order ACE-2026-000104 has been approved and marked Completed!', '1', '2026-09-22 06:21:18');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('34', 'NTF-10', '', 'Admin', 'Expert Inquiry: Need clarification on dataset format', 'Expert Dr. Robert Vance (PhD) sent a message regarding order ACE-2026-000104', '0', '2026-09-22 06:23:46');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('35', 'NTF-11', 'EXP-304', 'Expert', 'Solution Approved - ACE-2026-000101', 'Your solution for order ACE-2026-000101 has been approved and marked Completed!', '0', '2026-09-22 09:45:24');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('36', 'NTF-12', 'STU-1001', 'Student', 'Solution Approved & Released - ACE-2026-000101', 'Great news! Your assignment ACE-2026-000101 has received final administrative approval and is now released in your portal.', '0', '2026-09-22 09:45:24');

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('1', 'site_name', 'Ace Assignment Helps', '2026-09-18 09:17:12');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('2', 'contact_email', 'support@aceassign.com', '2026-09-18 09:17:13');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('3', 'whatsapp_phone', '+91 8233432123', '2026-09-21 07:29:50');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('4', 'default_currency', 'USD', '2026-09-18 09:17:13');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('5', 'base_price', '15.00', '2026-09-18 11:41:32');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('6', 'tagline', '#1 University Assignment Assistance (UK, USA, Ireland, Australia, Canada & India)', '2026-09-18 11:41:35');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('7', 'payment_gateway_mode', 'test', '2026-09-21 08:52:42');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('8', 'stripe_enabled', '1', '2026-09-21 08:52:43');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('9', 'stripe_publishable_key', 'pk_test_51MockStripeKey123456789AceAssign', '2026-09-21 08:52:45');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('10', 'stripe_secret_key', 'sk_test_51MockStripeSecretKey123456789AceAssign', '2026-09-21 08:52:48');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('11', 'razorpay_enabled', '1', '2026-09-21 08:52:49');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('12', 'razorpay_key_id', 'rzp_test_Tecy1ILChaRE40', '2026-09-21 09:02:50');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('13', 'razorpay_key_secret', '01ZQdknc3c61eOg4DoZd7skA', '2026-09-21 09:02:50');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('14', 'razorpay_upi_id', 'aceassignment@okhdfcbank', '2026-09-21 08:52:54');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('15', 'paypal_enabled', '1', '2026-09-21 08:52:56');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('16', 'paypal_client_id', 'sb_mock_client_id_aceassignment', '2026-09-21 08:52:57');
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES ('17', 'paypal_mode', 'sandbox', '2026-09-21 08:52:57');

-- --------------------------------------------------------
-- Table structure for `payments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` varchar(50) DEFAULT 'Paid',
  `payment_method` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` (`id`, `payment_id`, `assignment_id`, `student_id`, `amount`, `currency`, `status`, `payment_method`, `transaction_id`, `payment_date`) VALUES ('14', 'PAY-9541', 'ACE-2026-000101', 'STU-1001', '92.40', 'USD', 'Paid', 'Stripe Credit Card', 'pi_stripe_68d999c3dff0c3', '2026-09-21 13:04:21');

-- --------------------------------------------------------
-- Table structure for `files`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_internal` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_id` (`file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `files`
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('14', 'FILE-601', 'ACE-2026-000101', 'Resume (1).pdf', 'assets/uploads/1789992639_224_Resume__1_.pdf', 'pdf', 'Student', '2026-09-21 12:10:39', '0');
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('15', 'FILE-9662', 'ACE-2026-000101', 'test_solution.pdf', 'assets/uploads/1790057420_SOLUTION_662_test_solution.pdf', 'pdf', 'Expert (Clara Higgins (LLM)) Solution', '2026-09-22 06:10:20', '0');
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('16', 'FILE-9266', 'ACE-2026-000104', 'vance_db_solution.docx', 'assets/uploads/1790058000_SOLUTION_289_vance_db_solution.docx', 'docx', 'Expert (Dr. Robert Vance (PhD)) Solution', '2026-09-22 06:20:00', '0');

-- --------------------------------------------------------
-- Table structure for `audit_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `log_id` varchar(50) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `log_id` (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `audit_logs`
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('155', 'LOG-1001', 'Student', 'STU-1001', 'User Login', 'Logged into portal', '2026-09-21 11:57:26');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('156', 'LOG-1002', 'Admin', 'ADM-002', 'Update Coupon', 'Updated coupon ACE20 (CPN-1)', '2026-09-21 12:09:17');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('157', 'LOG-1003', 'Admin', 'ADM-002', 'Toggle Coupon Status', 'Set coupon CPN-3 to Active', '2026-09-21 12:09:27');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('158', 'LOG-1004', 'Admin', 'ADM-002', 'Update Coupon', 'Updated coupon SUPER30 (CPN-3)', '2026-09-21 12:09:49');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('159', 'LOG-1005', 'Admin', 'ADM-002', 'Update Coupon', 'Updated coupon SUPER30 (CPN-3)', '2026-09-21 12:10:26');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('160', 'LOG-1006', 'Student', 'STU-1001', 'Submit Assignment', 'Created ACE-2026-000101: Web Test 1 (USD 92.4)', '2026-09-21 12:10:40');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('161', 'LOG-1007', 'Student', 'STU-1001', 'User Login', 'Logged into portal', '2026-09-21 12:15:07');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('162', 'LOG-1008', 'Student', 'STU-1001', 'User Login', 'Logged into portal', '2026-09-21 12:29:51');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('163', 'LOG-1009', 'Student', 'STU-1001', 'Payment Received', 'Paid $92.40 (USD) via Stripe Credit Card for ACE-2026-000101 (TX: pi_stripe_68d999c3dff0c3)', '2026-09-21 13:04:21');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('164', 'LOG-1010', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-21 13:08:52');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('165', 'LOG-1011', 'Allocator', 'ALL-501', 'User Login', 'Logged into portal', '2026-09-21 13:10:01');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('166', 'LOG-1012', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-21 13:10:39');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('167', 'LOG-1013', 'Expert', 'EXP-304', 'User Login', 'Logged into portal', '2026-09-21 13:26:50');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('168', 'LOG-1014', 'Expert', 'EXP-304', 'Start Assignment', 'Expert Clara Higgins (LLM) accepted and started order ACE-2026-000101', '2026-09-21 13:30:10');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('169', 'LOG-1015', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 07:43:43');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('170', 'LOG-1016', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 07:46:45');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('171', 'LOG-1017', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 07:52:44');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('172', 'LOG-1018', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 05:53:02');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('173', 'LOG-1019', 'Expert', 'EXP-301', 'User Login', 'Logged into portal', '2026-09-22 05:53:15');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('174', 'LOG-1020', 'Expert', 'EXP-301', 'User Login', 'Logged into portal', '2026-09-22 05:54:18');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('175', 'LOG-1021', 'Expert', 'EXP-301', 'User Login', 'Logged into portal', '2026-09-22 05:55:05');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('176', 'LOG-1022', 'Expert', 'EXP-304', 'User Login', 'Logged into portal', '2026-09-22 05:56:48');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('177', 'LOG-1023', 'Expert', 'EXP-304', 'User Login', 'Logged into portal', '2026-09-22 06:10:05');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('178', 'LOG-1024', 'Expert', 'EXP-304', 'Solution Upload', 'Expert Clara Higgins (LLM) uploaded solution for order ACE-2026-000101 (1 files)', '2026-09-22 06:10:21');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('179', 'LOG-1025', 'Expert', 'EXP-301', 'User Login', 'Logged into portal', '2026-09-22 06:19:59');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('180', 'LOG-1026', 'Expert', 'EXP-301', 'Solution Upload', 'Expert Dr. Robert Vance (PhD) uploaded solution for order ACE-2026-000104 (1 files)', '2026-09-22 06:20:01');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('181', 'LOG-1027', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 06:20:33');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('182', 'LOG-1028', 'Admin', 'ADM-002', 'Approve Solution', 'Admin approved solution and marked order ACE-2026-000104 Completed', '2026-09-22 06:21:17');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('183', 'LOG-1029', 'Expert', 'EXP-301', 'Update Profile', 'Expert updated profile details', '2026-09-22 06:23:29');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('184', 'LOG-1030', 'Expert', 'EXP-301', 'User Login', 'Logged into portal', '2026-09-22 06:27:09');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('185', 'LOG-1031', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 06:31:46');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('186', 'LOG-1032', 'Allocator', 'ALL-501', 'User Login', 'Logged into portal', '2026-09-22 08:43:27');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('187', 'LOG-1033', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 08:48:26');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('188', 'LOG-1034', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 08:49:55');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('189', 'LOG-1035', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 08:51:37');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('190', 'LOG-1036', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 08:52:58');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('191', 'LOG-1037', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 08:58:36');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('192', 'LOG-1038', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 09:16:54');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('193', 'LOG-1039', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 09:28:06');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('194', 'LOG-1040', 'Admin', 'ADM-002', 'Final Admin Approval', 'Admin granted final approval and released solution for order ACE-2026-000101 to student', '2026-09-22 09:45:25');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('195', 'LOG-1041', 'Student', 'STU-1001', 'User Login', 'Logged into portal', '2026-09-22 10:02:06');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('196', 'LOG-1042', 'Student', 'STU-1001', 'User Login', 'Logged into portal', '2026-09-22 10:02:52');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('197', 'LOG-1043', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 19:17:59');

-- --------------------------------------------------------
-- Table structure for `support_tickets`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text,
  `priority` varchar(50) DEFAULT 'Medium',
  `status` varchar(50) DEFAULT 'Open',
  `replies` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `support_tickets`
INSERT INTO `support_tickets` (`id`, `ticket_id`, `student_id`, `assignment_id`, `subject`, `message`, `priority`, `status`, `replies`, `created_at`) VALUES ('2', 'EXP-MSG-4293', 'EXP-301', 'ACE-2026-000104', '[Expert Inquiry] Need clarification on dataset format', 'Please confirm if CSV or Parquet is expected.', 'Medium', 'Open', '[]', '2026-09-22 06:23:45');
INSERT INTO `support_tickets` (`id`, `ticket_id`, `student_id`, `assignment_id`, `subject`, `message`, `priority`, `status`, `replies`, `created_at`) VALUES ('3', 'TCK-401', 'STU-1001', 'ACE-2026-000101', 'Request for additional dataset integration', 'Hi, I just uploaded an updated CSV file to the assignment attachments. Can the expert please include it?', 'Medium', 'Open', '[{\"message\": \"Thank you Sarah! We have notified Dr. Robert Vance and forwarded the new dataset.\", \"timestamp\": \"2026-09-02 17:05:00\", \"sender_name\": \"Support Desk\", \"sender_role\": \"Admin\"}]', '2026-09-02 16:20:00');

-- --------------------------------------------------------
-- Table structure for `notes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `note_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `message` text,
  `visibility` varchar(50) DEFAULT 'Internal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_id` (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notes`
INSERT INTO `notes` (`id`, `note_id`, `assignment_id`, `user_id`, `user_role`, `user_name`, `message`, `visibility`, `created_at`) VALUES ('4', 'NOTE-273', 'ACE-2026-000101', 'ALL-501', 'Allocator', 'David Vance', 'Internal Notes for Expert / QA Team', 'Internal', '2026-09-21 13:10:38');

-- --------------------------------------------------------
-- Table structure for `allocation`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `allocation`;
CREATE TABLE `allocation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `allocation_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) NOT NULL,
  `expert_id` varchar(50) NOT NULL,
  `allocator_id` varchar(50) NOT NULL,
  `allocated_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `deadline` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocation_id` (`allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `allocation`
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('1', 'ALC-901', 'ACE-2026-000101', 'EXP-301', 'ALL-501', '2026-09-01 14:30:00', '2026-09-06 12:00:00', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('2', 'ALC-902', 'ACE-2026-000102', 'EXP-302', 'ALL-501', '2026-09-02 15:00:00', '2026-09-05 09:00:00', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('3', 'ALC-3582', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-21 13:10:37', '2026-09-23 13:10:37', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('4', 'ALC-4967', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 08:48:24', '2026-09-24 08:48:24', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('5', 'ALC-3051', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 08:49:53', '2026-09-24 08:49:53', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('6', 'ALC-6026', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 08:51:35', '2026-09-24 08:51:35', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('7', 'ALC-2330', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 08:52:56', '2026-09-24 08:52:56', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('8', 'ALC-1251', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 08:58:34', '2026-09-24 08:58:34', 'Active');

SET FOREIGN_KEY_CHECKS = 1;
