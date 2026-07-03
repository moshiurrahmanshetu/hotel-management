-- ============================================
-- Hotel & Resort Management System
-- Master Data Engine Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `master_items`;
DROP TABLE IF EXISTS `master_groups`;

-- ============================================
-- Master Groups Table
-- ============================================
CREATE TABLE `master_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `icon_class` VARCHAR(50) NULL,
    `color` VARCHAR(20) NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_master_groups_uuid` (`uuid`),
    UNIQUE KEY `uk_master_groups_slug` (`slug`),
    UNIQUE KEY `uk_master_groups_name` (`name`),
    KEY `idx_master_groups_display_order` (`display_order`),
    KEY `idx_master_groups_is_active` (`is_active`),
    KEY `idx_master_groups_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Master Items Table
-- ============================================
CREATE TABLE `master_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `group_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `icon_class` VARCHAR(50) NULL,
    `color` VARCHAR(20) NULL,
    `options` JSON NULL,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_master_items_uuid` (`uuid`),
    UNIQUE KEY `uk_master_items_group_code` (`group_id`, `code`),
    KEY `idx_master_items_group_id` (`group_id`),
    KEY `idx_master_items_name` (`name`),
    KEY `idx_master_items_display_order` (`display_order`),
    KEY `idx_master_items_is_active` (`is_active`),
    KEY `idx_master_items_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_master_items_group` FOREIGN KEY (`group_id`) REFERENCES `master_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
