-- ============================================
-- Hotel & Resort Management System
-- Room Pricing Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `room_rates`;
DROP TABLE IF EXISTS `rate_plans`;

-- ============================================
-- Rate Plans Table
-- ============================================
CREATE TABLE `rate_plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rate_plans_uuid` (`uuid`),
    UNIQUE KEY `uk_rate_plans_code` (`code`),
    KEY `idx_rate_plans_is_active` (`is_active`),
    KEY `idx_rate_plans_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Rates Table
-- ============================================
CREATE TABLE `room_rates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `rate_plan_id` INT UNSIGNED NOT NULL,
    `base_price` DECIMAL(10, 2) NOT NULL,
    `weekend_price` DECIMAL(10, 2) NULL,
    `extra_adult_price` DECIMAL(10, 2) NULL,
    `extra_child_price` DECIMAL(10, 2) NULL,
    `tax_included` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_rates_uuid` (`uuid`),
    UNIQUE KEY `uk_room_rates_room_plan` (`room_id`, `rate_plan_id`),
    KEY `idx_room_rates_room_id` (`room_id`),
    KEY `idx_room_rates_rate_plan_id` (`rate_plan_id`),
    KEY `idx_room_rates_is_active` (`is_active`),
    KEY `idx_room_rates_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_room_rates_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_room_rates_rate_plan` FOREIGN KEY (`rate_plan_id`) REFERENCES `rate_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
