-- ============================================
-- Hotel & Resort Management System
-- Custom Fields Engine Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `custom_field_values`;
DROP TABLE IF EXISTS `custom_fields`;

-- ============================================
-- Custom Fields Table
-- ============================================
CREATE TABLE `custom_fields` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(100) NOT NULL,
    `field_type` VARCHAR(20) NOT NULL,
    `description` TEXT NULL,
    `placeholder` VARCHAR(255) NULL,
    `default_value` TEXT NULL,
    `options` JSON NULL,
    `validation_rules` JSON NULL,
    `conditional_logic` JSON NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_custom_fields_uuid` (`uuid`),
    KEY `idx_custom_fields_module` (`module`),
    KEY `idx_custom_fields_field_name` (`field_name`),
    KEY `idx_custom_fields_field_type` (`field_type`),
    KEY `idx_custom_fields_display_order` (`display_order`),
    KEY `idx_custom_fields_is_active` (`is_active`),
    KEY `idx_custom_fields_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Custom Field Values Table
-- ============================================
CREATE TABLE `custom_field_values` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `field_id` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_custom_field_values_uuid` (`uuid`),
    UNIQUE KEY `uk_custom_field_values_field_entity` (`field_id`, `entity_id`, `entity_type`),
    KEY `idx_custom_field_values_field_id` (`field_id`),
    KEY `idx_custom_field_values_entity` (`entity_id`, `entity_type`),
    KEY `idx_custom_field_values_entity_type` (`entity_type`),
    CONSTRAINT `fk_custom_field_values_field` FOREIGN KEY (`field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
