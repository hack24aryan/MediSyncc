-- ============================================================
--  MediSyncc Database Schema
--  Version: 1.0
--  Description: Full schema for the MediSyncc web application.
--               Run this file in phpMyAdmin or MySQL CLI to
--               set up the database from scratch.
--
--  Usage:
--    mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `medisyncc_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `medisyncc_db`;

-- ============================================================
--  Table: users
--  Stores patient accounts, subscription info, and profile.
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `full_name`             VARCHAR(150)    NOT NULL,
    `email`                 VARCHAR(200)    NOT NULL,
    `phone`                 VARCHAR(20)     DEFAULT NULL,
    `gender`                ENUM('Male','Female','Other') DEFAULT NULL,
    `password`              VARCHAR(255)    NOT NULL,
    `plan_type`             ENUM('Free','Basic','Premium') NOT NULL DEFAULT 'Free',
    `subscription_status`   ENUM('trial','active','expired') NOT NULL DEFAULT 'trial',
    `trial_start_date`      DATETIME        DEFAULT NULL,
    `subscription_end`      DATE            DEFAULT NULL,
    `two_factor_enabled`    TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: user_medicines
--  Tracks each medicine added by a patient.
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_medicines` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL,
    `medicine_name`     VARCHAR(200)    NOT NULL,
    `dosage`            VARCHAR(100)    DEFAULT NULL,
    `type`              VARCHAR(50)     DEFAULT NULL,
    `purpose`           TEXT            DEFAULT NULL,
    `frequency`         VARCHAR(50)     DEFAULT NULL,
    `start_date`        DATE            NOT NULL,
    `end_date`          DATE            DEFAULT NULL,
    `repeat_hours`      INT             DEFAULT NULL,
    `snooze_duration`   INT             NOT NULL DEFAULT 5,
    `food_instruction`  VARCHAR(100)    DEFAULT NULL,
    `doctor_id`         INT UNSIGNED    DEFAULT NULL,
    `doctor_note`       TEXT            DEFAULT NULL,
    `notify_nominee`    ENUM('YES','NO') NOT NULL DEFAULT 'NO',
    `medicine_image`    VARCHAR(300)    DEFAULT NULL,
    `status`            ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_um_user_id` (`user_id`),
    KEY `idx_um_status`  (`status`),
    CONSTRAINT `fk_um_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: medicine_times
--  Stores each scheduled reminder time for a medicine.
-- ============================================================
CREATE TABLE IF NOT EXISTS `medicine_times` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `medicine_id`   INT UNSIGNED    NOT NULL,
    `reminder_time` TIME            NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mt_medicine_id` (`medicine_id`),
    CONSTRAINT `fk_mt_medicine`
        FOREIGN KEY (`medicine_id`) REFERENCES `user_medicines` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: medicine_logs
--  Records when a patient marks a dose as taken.
-- ============================================================
CREATE TABLE IF NOT EXISTS `medicine_logs` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL,
    `medicine_time_id`  INT UNSIGNED    NOT NULL,
    `log_date`          DATE            NOT NULL,
    `taken_time`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ml_entry` (`medicine_time_id`, `log_date`),
    KEY `idx_ml_user_date` (`user_id`, `log_date`),
    CONSTRAINT `fk_ml_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ml_time`
        FOREIGN KEY (`medicine_time_id`) REFERENCES `medicine_times` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: user_doctors
--  Stores doctor details linked to patient medicines.
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_doctors` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `name`          VARCHAR(150)    NOT NULL,
    `specialty`     VARCHAR(100)    DEFAULT NULL,
    `phone`         VARCHAR(20)     DEFAULT NULL,
    `clinic_name`   VARCHAR(200)    DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ud_user_id` (`user_id`),
    CONSTRAINT `fk_ud_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: nominees
--  Caregiver/nominee accounts linked to a patient.
-- ============================================================
CREATE TABLE IF NOT EXISTS `nominees` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`                   INT UNSIGNED    NOT NULL,
    `nominee_name`              VARCHAR(150)    NOT NULL,
    `relationship`              VARCHAR(80)     DEFAULT NULL,
    `email`                     VARCHAR(200)    NOT NULL,
    `phone`                     VARCHAR(20)     DEFAULT NULL,
    `password`                  VARCHAR(255)    NOT NULL,
    `notification_preference`   VARCHAR(50)     DEFAULT NULL,
    `alert_sensitivity`         VARCHAR(50)     DEFAULT NULL,
    `alert_rule_once`           TINYINT(1)      NOT NULL DEFAULT 0,
    `alert_rule_twice`          TINYINT(1)      NOT NULL DEFAULT 0,
    `alert_rule_high_risk`      TINYINT(1)      NOT NULL DEFAULT 0,
    `alert_rule_adherence`      TINYINT(1)      NOT NULL DEFAULT 0,
    `is_active`                 TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nominees_email` (`email`),
    KEY `idx_nominees_user_id` (`user_id`),
    CONSTRAINT `fk_nom_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: notifications
--  Alerts sent to nominees when doses are missed.
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `nominee_id`    INT UNSIGNED    DEFAULT NULL,
    `message`       TEXT            NOT NULL,
    `status`        ENUM('PENDING','ACKNOWLEDGED') NOT NULL DEFAULT 'PENDING',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user`    (`user_id`),
    KEY `idx_notif_nominee` (`nominee_id`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_nominee`
        FOREIGN KEY (`nominee_id`) REFERENCES `nominees` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: activity_logs
--  Audit trail of key user actions (profile updates, etc.).
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `activity`      VARCHAR(255)    NOT NULL,
    `status`        VARCHAR(50)     NOT NULL DEFAULT 'Success',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_user_id` (`user_id`),
    CONSTRAINT `fk_al_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Table: site_stats
--  Public-facing counters displayed on the landing page.
-- ============================================================
CREATE TABLE IF NOT EXISTS `site_stats` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `total_users`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `reminders_sent`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `on_time_percent`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `rating`            TINYINT UNSIGNED NOT NULL DEFAULT 5,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Seed Data
-- ============================================================

-- Initial site stats row (required by index.php)
INSERT IGNORE INTO `site_stats` (`id`, `total_users`, `reminders_sent`, `on_time_percent`, `rating`)
VALUES (1, 0, 0, 99, 5);
