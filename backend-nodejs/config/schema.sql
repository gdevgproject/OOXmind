-- Database schema for OOXmind application
-- This script creates the database and tables needed for the application

-- Create database
CREATE DATABASE IF NOT EXISTS `openyourself` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `openyourself`;

-- Create activity_log table
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `activity_date` date NOT NULL,
  `vocab_reviewed_count` int DEFAULT '0',
  `total_time_spent` int DEFAULT '0',
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create content table
DROP TABLE IF EXISTS `content`;
CREATE TABLE IF NOT EXISTS `content` (
  `content_id` int NOT NULL AUTO_INCREMENT,
  `vocab` varchar(255) DEFAULT NULL,
  `part_of_speech` varchar(255) DEFAULT NULL,
  `ipa` varchar(255) DEFAULT NULL,
  `def` text,
  `ex` text,
  `question` text,
  `answer` text,
  `level` int DEFAULT '0',
  `correct_count` int DEFAULT '0',
  `incorrect_count` int DEFAULT '0',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_review` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `next_review` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_path` varchar(255) DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `response_time` int DEFAULT '0',
  `is_recovery` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create count table
DROP TABLE IF EXISTS `count`;
CREATE TABLE IF NOT EXISTS `count` (
  `count_id` int NOT NULL AUTO_INCREMENT,
  `count_name` varchar(255) NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`count_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create draft_content table
DROP TABLE IF EXISTS `draft_content`;
CREATE TABLE IF NOT EXISTS `draft_content` (
  `draft_id` int NOT NULL AUTO_INCREMENT,
  `vocab` varchar(255) DEFAULT NULL,
  `part_of_speech` varchar(255) DEFAULT NULL,
  `ipa` varchar(255) DEFAULT NULL,
  `def` text,
  `ex` text,
  `question` text,
  `answer` text,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_path` varchar(255) DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `accepted` int DEFAULT '0',
  PRIMARY KEY (`draft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
