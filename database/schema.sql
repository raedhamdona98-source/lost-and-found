
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildings` (
  `building_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `building_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`building_id`),
  UNIQUE KEY `building_name` (`building_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `claims` (
  `claim_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lost_id` int(10) unsigned NOT NULL,
  `found_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `ownership_details` text NOT NULL,
  `verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `claim_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`claim_id`),
  UNIQUE KEY `unique_claim` (`lost_id`,`found_id`,`user_id`),
  KEY `fk_claim_found` (`found_id`),
  KEY `fk_claim_user` (`user_id`),
  KEY `fk_claim_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_claim_found` FOREIGN KEY (`found_id`) REFERENCES `found_items` (`found_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_claim_lost` FOREIGN KEY (`lost_id`) REFERENCES `lost_items` (`lost_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_claim_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_claim_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `found_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `found_items` (
  `found_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `finder_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `building_id` int(10) unsigned DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `specific_location` varchar(255) DEFAULT NULL,
  `found_date` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `status` enum('submitted','under_review','available','matched','ready_for_collection','returned','closed','rejected') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`found_id`),
  KEY `fk_found_user` (`finder_id`),
  KEY `fk_found_category` (`category_id`),
  KEY `fk_found_building` (`building_id`),
  CONSTRAINT `fk_found_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_found_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_found_user` FOREIGN KEY (`finder_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lost_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lost_items` (
  `lost_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `building_id` int(10) unsigned DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `specific_location` varchar(255) DEFAULT NULL,
  `lost_date` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','under_review','matched','ready_for_collection','returned','closed','rejected') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`lost_id`),
  KEY `fk_lost_user` (`user_id`),
  KEY `fk_lost_category` (`category_id`),
  KEY `fk_lost_building` (`building_id`),
  CONSTRAINT `fk_lost_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lost_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lost_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `fk_notification_user` (`user_id`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `returns` (
  `return_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmation_notes` text DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `claim_id` (`claim_id`),
  KEY `fk_return_student` (`student_id`),
  KEY `fk_return_staff` (`staff_id`),
  CONSTRAINT `fk_return_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_return_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','staff','admin') NOT NULL DEFAULT 'student',
  `account_status` enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT IGNORE INTO `categories` (`category_name`) VALUES
('Phones'), ('Laptops'), ('Keys'), ('Wallets'), ('Bags'),
('Documents'), ('Accessories'), ('Other');

INSERT IGNORE INTO `buildings` (`building_name`) VALUES
('Building A'), ('Building B'), ('Building C'),
('Library'), ('Cafeteria'), ('Main Administration');

