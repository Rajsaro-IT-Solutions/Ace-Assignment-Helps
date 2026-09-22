-- ========================================================
-- Database: aceassignmenthelp_db
-- Exported on: 2026-09-22 22:40:25
-- Standalone MySQL Schema & Seed Data
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `experts`
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `students`
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `password`, `country`, `university`, `course`, `status`, `created_at`) VALUES ('9', 'STU-1001', 'Gokul Marwal', 'gokulmarwal1627@gmail.com', '+91 8233432123', '$2y$10$Xx0RF.bR1iIK96hYXH9SnO5plzItgDt.JFD7.7MBy7ufjfmZCaue6', 'United Kingdom', 'oxford university', 'computer science', 'Active', '2026-09-22 20:23:50');

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
  `payment_status` varchar(50) DEFAULT 'Unpaid',
  `payment_plan` varchar(50) DEFAULT '100%',
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `remaining_balance` decimal(10,2) DEFAULT '0.00',
  `refund_reason` text,
  `revision_notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_id` (`assignment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `assignments`
INSERT INTO `assignments` (`id`, `assignment_id`, `student_id`, `title`, `subject`, `assignment_type`, `deadline`, `timezone`, `word_count`, `pages`, `reference_style`, `priority`, `language`, `instructions`, `currency`, `price`, `discount_code`, `final_price`, `status`, `allocator_id`, `expert_id`, `country`, `university`, `created_at`, `updated_at`, `payment_status`, `payment_plan`, `paid_amount`, `remaining_balance`, `refund_reason`, `revision_notes`) VALUES ('27', 'ACE-2026-000101', 'STU-1001', 'advance science', 'Engineering & Physical Sciences', 'Dissertation', '2026-09-23 08:25:47', 'EST (UTC-5)', '12500', '50', 'APA 7th', 'Normal', 'English (US)', 'rules and markes', 'INR', '25000.00', 'ACE20', '20000.00', 'Revision Requested', 'ALL-501', 'EXP-304', 'United Kingdom', 'Stanford University', '2026-09-22 20:25:48', '2026-09-22 18:51:46', 'Paid', '100%', '20000.00', '0.00', NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `coupons`
INSERT INTO `coupons` (`id`, `coupon_id`, `code`, `discount_percent`, `max_uses`, `current_uses`, `expires_at`, `status`) VALUES ('1', 'CPN-1', 'ACE20', '20.00', '100', '30', '2026-12-31', 'Active');
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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('41', 'NTF-1', '', 'Allocator', 'New Assignment Received', 'Assignment ACE-2026-000101 (advance science) has been submitted and awaits allocation.', '0', '2026-09-22 20:25:49');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('42', 'NTF-2', 'ALL-501', 'Allocator', 'Solution Submitted - ACE-2026-000101', 'Expert Clara Higgins (LLM) submitted the final solution for order ACE-2026-000101. Ready for QA review.', '0', '2026-09-22 20:32:29');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('43', 'NTF-3', '', 'Admin', 'Solution Submitted - ACE-2026-000101', 'Expert Clara Higgins (LLM) uploaded final solution for order ACE-2026-000101. Ready for Quality Check review.', '0', '2026-09-22 20:32:30');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('44', 'NTF-4', '', 'Admin', 'QA Approved - ACE-2026-000101', 'Allocator David Vance has QA-approved the solution for order ACE-2026-000101. Final Admin sign-off is required to release to student.', '0', '2026-09-22 20:34:12');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('45', 'NTF-5', 'EXP-304', 'Expert', 'QA Passed - ACE-2026-000101', 'Your submitted solution for order ACE-2026-000101 has passed QA review and is awaiting final administrative sign-off.', '0', '2026-09-22 20:34:12');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('46', 'NTF-6', 'EXP-304', 'Expert', 'Solution Approved - ACE-2026-000101', 'Your solution for order ACE-2026-000101 has been approved and marked Completed!', '0', '2026-09-22 20:35:08');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('47', 'NTF-7', 'STU-1001', 'Student', 'Solution Approved & Released - ACE-2026-000101', 'Great news! Your assignment ACE-2026-000101 has received final administrative approval and is now released in your portal.', '0', '2026-09-22 20:35:08');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('48', 'NTF-8', '', 'Allocator', 'Order Confirmed & Paid: ACE-2026-000101', 'Payment of ₹20,000 (INR) received via Stripe Credit Card. Assignment is ready for expert allocation.', '0', '2026-09-22 20:36:31');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('49', 'NTF-9', 'STU-1001', 'Student', 'Payment Confirmed - Order ACE-2026-000101', 'Thank you! Your payment of ₹20,000 has been verified. Your assignment is now Confirmed and allocated to a subject specialist.', '0', '2026-09-22 20:36:31');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('50', 'NTF-10', '', 'Allocator', 'Revision Requested: ACE-2026-000101', 'Student Gokul Marwal requested revisions [1 file(s) attached]', '0', '2026-09-22 20:40:36');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('51', 'NTF-11', '', 'Allocator', 'Order Confirmed & Paid: TEST-PAY-1790108900', 'Payment of $200.00 (USD) received via Stripe Credit Card [Part Payment: 20% Deposit, Remaining: $800.00]. Assignment is ready for expert allocation.', '0', '2026-09-22 22:28:21');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('52', 'NTF-12', 'STU-1001', 'Student', 'Payment Confirmed - Order TEST-PAY-1790108900', 'Thank you! Your payment of $200.00 has been verified. Status: Partially Paid [Part Payment: 20% Deposit, Remaining: $800.00].', '0', '2026-09-22 22:28:21');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('53', 'NTF-13', '', 'Allocator', 'Order Confirmed & Paid: TEST-PAY-1790108915', 'Payment of $200.00 (USD) received via Stripe Credit Card [Part Payment: 20% Deposit, Remaining: $800.00]. Assignment is ready for expert allocation.', '0', '2026-09-22 22:28:36');
INSERT INTO `notifications` (`id`, `notification_id`, `user_id`, `user_role`, `title`, `message`, `is_read`, `created_at`) VALUES ('54', 'NTF-14', 'STU-1001', 'Student', 'Payment Confirmed - Order TEST-PAY-1790108915', 'Thank you! Your payment of $200.00 has been verified. Status: Partially Paid [Part Payment: 20% Deposit, Remaining: $800.00].', '0', '2026-09-22 22:28:36');

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
  `payment_plan` varchar(50) DEFAULT '100%',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` (`id`, `payment_id`, `assignment_id`, `student_id`, `amount`, `currency`, `status`, `payment_method`, `transaction_id`, `payment_date`, `payment_plan`) VALUES ('15', 'PAY-9028', 'ACE-2026-000101', 'STU-1001', '20000.00', 'INR', 'Paid', 'Stripe Credit Card', 'pi_stripe_f9e51fc8300535', '2026-09-22 20:36:30', '100%');

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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `files`
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('19', 'FILE-977', 'ACE-2026-000101', 'Gokul.pdf', 'assets/uploads/1790101548_670_Gokul.pdf', 'pdf', 'Student', '2026-09-22 20:25:48', '0');
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('20', 'FILE-9602', 'ACE-2026-000101', 'p0447.docx', 'assets/uploads/1790101948_SOLUTION_915_p0447.docx', 'docx', 'Expert (Clara Higgins (LLM)) Solution', '2026-09-22 20:32:28', '0');
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('21', 'FILE-9429', 'ACE-2026-000101', 'Turnitin_Report_Subhash.pdf', 'assets/uploads/1790101949_TURNITIN_881_Subhash.pdf', 'pdf', 'Expert (Clara Higgins (LLM)) Turnitin', '2026-09-22 20:32:29', '0');
INSERT INTO `files` (`id`, `file_id`, `assignment_id`, `file_name`, `path`, `file_type`, `uploaded_by`, `upload_date`, `is_internal`) VALUES ('22', 'FILE-997', 'ACE-2026-000101', 'Saroj.pdf', 'assets/uploads/1790102435_159_Saroj.pdf', 'pdf', 'Gokul Marwal (Revision Request)', '2026-09-22 20:40:35', '0');

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
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `audit_logs`
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('200', 'LOG-1001', 'Admin', 'ADM-002', 'User Login', 'Logged into portal', '2026-09-22 20:09:51');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('201', 'LOG-1002', 'Expert', 'EXP-304', 'User Login', 'Logged into portal', '2026-09-22 20:12:45');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('202', 'LOG-1003', 'Expert', 'EXP-304', 'Solution Upload', 'Expert Clara Higgins (LLM) uploaded solution for order ACE-2026-000101 (2 files)', '2026-09-22 20:13:46');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('203', 'LOG-1004', 'Allocator', 'ALL-501', 'User Login', 'Logged into portal', '2026-09-22 20:17:11');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('204', 'LOG-1005', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-TEST-PERM-100 from everywhere (database, history, files, payments)', '2026-09-22 20:19:35');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('205', 'LOG-1006', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000111 from everywhere (database, history, files, payments)', '2026-09-22 20:19:42');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('206', 'LOG-1007', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000106 from everywhere (database, history, files, payments)', '2026-09-22 20:19:48');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('207', 'LOG-1008', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000105 from everywhere (database, history, files, payments)', '2026-09-22 20:19:54');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('208', 'LOG-1009', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000104 from everywhere (database, history, files, payments)', '2026-09-22 20:20:00');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('209', 'LOG-1010', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000103 from everywhere (database, history, files, payments)', '2026-09-22 20:20:05');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('210', 'LOG-1011', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000102 from everywhere (database, history, files, payments)', '2026-09-22 20:20:10');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('211', 'LOG-1012', 'Admin', 'ADM-002', 'Permanent Delete Assignment', 'Permanently wiped assignment ACE-2026-000101 from everywhere (database, history, files, payments)', '2026-09-22 20:20:15');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('212', 'LOG-1013', 'Admin', 'ADM-002', 'Delete Student', 'Permanently deleted student STU-1003', '2026-09-22 20:21:15');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('213', 'LOG-1014', 'Admin', 'ADM-002', 'Delete Student', 'Permanently deleted student STU-1002', '2026-09-22 20:21:18');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('214', 'LOG-1015', 'Admin', 'ADM-002', 'Delete Student', 'Permanently deleted student STU-1001', '2026-09-22 20:21:24');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('215', 'LOG-1016', 'Admin', 'ADM-002', 'Delete Expert', 'Permanently deleted expert EXP-302', '2026-09-22 20:21:36');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('216', 'LOG-1017', 'Admin', 'ADM-002', 'Delete Expert', 'Permanently deleted expert EXP-301', '2026-09-22 20:21:40');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('217', 'LOG-1018', 'Student', 'STU-1001', 'Student Registration', 'Self-registered student account', '2026-09-22 20:23:51');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('218', 'LOG-1019', 'Student', 'STU-1001', 'Submit Assignment', 'Created ACE-2026-000101: advance science (INR 20000)', '2026-09-22 20:25:48');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('219', 'LOG-1020', 'Allocator', 'ALL-501', 'User Login', 'Logged into portal', '2026-09-22 20:28:58');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('220', 'LOG-1021', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 20:29:35');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('221', 'LOG-1022', 'Allocator', 'ALL-501', 'Allocate Expert', 'Allocated ACE-2026-000101 to EXP-304', '2026-09-22 20:30:02');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('222', 'LOG-1023', 'Expert', 'EXP-304', 'User Login', 'Logged into portal', '2026-09-22 20:31:53');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('223', 'LOG-1024', 'Expert', 'EXP-304', 'Solution Upload', 'Expert Clara Higgins (LLM) uploaded solution for order ACE-2026-000101 (2 files)', '2026-09-22 20:32:29');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('224', 'LOG-1025', 'Allocator', 'ALL-501', 'Approve QA', 'Allocator David Vance gave QA Approval for order ACE-2026-000101 and forwarded to Admin for final release.', '2026-09-22 20:34:12');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('225', 'LOG-1026', 'Admin', 'ADM-002', 'Final Admin Approval', 'Admin granted final approval and released solution for order ACE-2026-000101 to student', '2026-09-22 20:35:08');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('226', 'LOG-1027', 'Student', 'STU-1001', 'Payment Received', 'Paid ₹20,000 (INR) via Stripe Credit Card for ACE-2026-000101 (TX: pi_stripe_f9e51fc8300535)', '2026-09-22 20:36:31');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('227', 'LOG-1028', 'Student', 'STU-1001', 'Request Revision', 'Requested revision for ACE-2026-000101 [1 file(s) attached]', '2026-09-22 20:40:36');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('228', 'LOG-1029', 'Student', 'STU-1001', 'Payment Received', 'Paid $200.00 (USD) via Stripe Credit Card for TEST-PAY-1790108900 (TX: pi_stripe_c6def075703900) [Part Payment: 20% Deposit, Remaining: $800.00]', '2026-09-22 22:28:21');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('229', 'LOG-1030', 'Student', 'STU-1001', 'Payment Received', 'Paid $200.00 (USD) via Stripe Credit Card for TEST-PAY-1790108915 (TX: pi_stripe_d89681391e4215) [Part Payment: 20% Deposit, Remaining: $800.00]', '2026-09-22 22:28:36');
INSERT INTO `audit_logs` (`id`, `log_id`, `user_role`, `user_id`, `action`, `details`, `timestamp`) VALUES ('230', 'LOG-1031', 'Student', 'STU-1001', 'Payment Received', 'Paid $800.00 (USD) via Stripe Credit Card for TEST-REM-1790108989 (TX: pi_stripe_3e4ffc3de5c01a) [Paid in Full]', '2026-09-22 22:29:50');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notes`
INSERT INTO `notes` (`id`, `note_id`, `assignment_id`, `user_id`, `user_role`, `user_name`, `message`, `visibility`, `created_at`) VALUES ('5', 'NOTE-653', 'ACE-2026-000101', 'STU-1001', 'Student', 'Gokul Marwal', 'Revision Request: in relevent topic [1 file(s) attached]', 'Internal', '2026-09-22 20:40:35');

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `allocation`
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('9', 'ALC-3406', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 20:29:35', '2026-09-24 20:29:35', 'Active');
INSERT INTO `allocation` (`id`, `allocation_id`, `assignment_id`, `expert_id`, `allocator_id`, `allocated_date`, `deadline`, `status`) VALUES ('10', 'ALC-4711', 'ACE-2026-000101', 'EXP-304', 'ALL-501', '2026-09-22 20:30:02', '2026-09-24 20:30:02', 'Active');

SET FOREIGN_KEY_CHECKS = 1;
