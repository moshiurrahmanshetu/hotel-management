-- ============================================
-- Hotel & Resort Management System
-- Hotel Structure Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `floors`;
DROP TABLE IF EXISTS `buildings`;
DROP TABLE IF EXISTS `properties`;

-- ============================================
-- Properties Table
-- ============================================
CREATE TABLE `properties` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT NULL,
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country_id` INT UNSIGNED NULL,
    `postal_code` VARCHAR(20) NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `website` VARCHAR(255) NULL,
    `star_rating` TINYINT UNSIGNED NULL DEFAULT 0,
    `total_rooms` INT UNSIGNED NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_properties_uuid` (`uuid`),
    UNIQUE KEY `uk_properties_code` (`code`),
    KEY `idx_properties_name` (`name`),
    KEY `idx_properties_country_id` (`country_id`),
    KEY `idx_properties_is_active` (`is_active`),
    KEY `idx_properties_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_properties_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Buildings Table
-- ============================================
CREATE TABLE `buildings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `property_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT NULL,
    `address` VARCHAR(255) NULL,
    `total_floors` INT UNSIGNED NULL DEFAULT 0,
    `total_rooms` INT UNSIGNED NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_buildings_uuid` (`uuid`),
    UNIQUE KEY `uk_buildings_code` (`code`),
    KEY `idx_buildings_property_id` (`property_id`),
    KEY `idx_buildings_name` (`name`),
    KEY `idx_buildings_is_active` (`is_active`),
    KEY `idx_buildings_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_buildings_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Floors Table
-- ============================================
CREATE TABLE `floors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `building_id` INT UNSIGNED NOT NULL,
    `floor_number` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `total_rooms` INT UNSIGNED NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_floors_uuid` (`uuid`),
    UNIQUE KEY `uk_floors_building_floor` (`building_id`, `floor_number`),
    KEY `idx_floors_building_id` (`building_id`),
    KEY `idx_floors_floor_number` (`floor_number`),
    KEY `idx_floors_name` (`name`),
    KEY `idx_floors_is_active` (`is_active`),
    KEY `idx_floors_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_floors_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
