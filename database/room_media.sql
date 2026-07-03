-- ============================================
-- Hotel & Resort Management System
-- Room Media Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `room_media`;

-- ============================================
-- Room Media Table
-- ============================================
CREATE TABLE `room_media` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_media_uuid` (`uuid`),
    KEY `idx_room_media_room_id` (`room_id`),
    KEY `idx_room_media_is_featured` (`is_featured`),
    KEY `idx_room_media_display_order` (`display_order`),
    KEY `idx_room_media_is_active` (`is_active`),
    CONSTRAINT `fk_room_media_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
