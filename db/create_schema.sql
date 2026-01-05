-- Create database and tables for test_series
CREATE DATABASE IF NOT EXISTS `test_series` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `test_series`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student','admin','author') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exams (categories)
CREATE TABLE IF NOT EXISTS `exams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Test papers
CREATE TABLE IF NOT EXISTS `test_papers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` INT UNSIGNED NOT NULL,
  `author_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255),
  `description` TEXT,
  `duration_minutes` SMALLINT UNSIGNED DEFAULT 0,
  `total_marks` DECIMAL(8,2) DEFAULT 0,
  `passing_marks` DECIMAL(8,2) DEFAULT 0,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exam` (`exam_id`),
  CONSTRAINT `fk_testpapers_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_testpapers_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Questions
CREATE TABLE IF NOT EXISTS `questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id` BIGINT UNSIGNED DEFAULT NULL,
  `question_text` TEXT NOT NULL,
  `explanation` TEXT,
  `question_type` ENUM('mcq','multi-select','numeric','descriptive') NOT NULL DEFAULT 'mcq',
  `default_marks` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `negative_marks` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_questions_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Options for multiple choice questions
CREATE TABLE IF NOT EXISTS `options` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `option_label` VARCHAR(8) DEFAULT NULL,
  `option_text` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_options_question` (`question_id`),
  CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mapping questions to test papers
CREATE TABLE IF NOT EXISTS `test_paper_questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_paper_id` BIGINT UNSIGNED NOT NULL,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `position` INT UNSIGNED NOT NULL DEFAULT 0,
  `marks` DECIMAL(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_paper_position` (`test_paper_id`,`position`),
  KEY `idx_tpq_paper` (`test_paper_id`),
  CONSTRAINT `fk_tpq_paper` FOREIGN KEY (`test_paper_id`) REFERENCES `test_papers`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpq_question` FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User attempts (one per test session)
CREATE TABLE IF NOT EXISTS `user_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `test_paper_id` BIGINT UNSIGNED NOT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `finished_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
  `score` DECIMAL(8,2) DEFAULT NULL,
  `correct_count` INT UNSIGNED DEFAULT 0,
  `wrong_count` INT UNSIGNED DEFAULT 0,
  `duration_seconds` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_user` (`user_id`),
  KEY `idx_attempts_test` (`test_paper_id`),
  CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_paper` FOREIGN KEY (`test_paper_id`) REFERENCES `test_papers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User answers (one row per selected option or submitted answer)
CREATE TABLE IF NOT EXISTS `user_answers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `selected_option_id` BIGINT UNSIGNED DEFAULT NULL,
  `answer_text` TEXT DEFAULT NULL,
  `is_correct` TINYINT(1) DEFAULT NULL,
  `marks_obtained` DECIMAL(6,2) DEFAULT NULL,
  `answered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_answers_attempt` (`attempt_id`),
  KEY `idx_answers_question` (`question_id`),
  CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `user_attempts`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `options`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Fulltext indexes for search
ALTER TABLE `questions` ADD FULLTEXT KEY `ft_question_text` (`question_text`);
ALTER TABLE `options` ADD FULLTEXT KEY `ft_option_text` (`option_text`);

-- End of migration
