-- ============================================
-- Hotel & Resort Management System
-- Room Types Module Database Schema
-- ============================================
-- Engine: InnoDB
-- Charset: UTF8MB4
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- Amenities Table (reusable across room types)
-- ============================================
CREATE TABLE IF NOT EXISTS `amenities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) NULL,
    `description` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_amenities_slug` (`slug`),
    KEY `idx_amenities_is_active` (`is_active`),
    KEY `idx_amenities_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Types Table
-- ============================================
CREATE TABLE IF NOT EXISTS `room_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `base_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `weekend_price` DECIMAL(10, 2) NULL,
    `max_adults` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `max_children` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_occupancy` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `room_size` DECIMAL(6, 2) NULL,
    `bed_type` VARCHAR(50) NULL,
    `num_beds` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_types_uuid` (`uuid`),
    UNIQUE KEY `uk_room_types_code` (`code`),
    KEY `idx_room_types_name` (`name`),
    KEY `idx_room_types_is_active` (`is_active`),
    KEY `idx_room_types_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Type Amenities Junction Table
-- ============================================
CREATE TABLE IF NOT EXISTS `room_type_amenities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_type_id` INT UNSIGNED NOT NULL,
    `amenity_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_type_amenities_room_type_amenity` (`room_type_id`, `amenity_id`),
    KEY `idx_room_type_amenities_amenity_id` (`amenity_id`),
    CONSTRAINT `fk_room_type_amenities_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_room_type_amenities_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Type Images Table
-- ============================================
CREATE TABLE IF NOT EXISTS `room_type_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_type_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(255) NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room_type_images_room_type_id` (`room_type_id`),
    KEY `idx_room_type_images_is_featured` (`is_featured`),
    KEY `idx_room_type_images_display_order` (`display_order`),
    CONSTRAINT `fk_room_type_images_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Amenities
-- ============================================
INSERT INTO `amenities` (`name`, `slug`, `icon`, `description`, `display_order`) VALUES
('WiFi', 'wifi', 'bi-wifi', 'High-speed wireless internet access', 1),
('Air Conditioner', 'air-conditioner', 'bi-fan', 'Climate control system', 2),
('TV', 'tv', 'bi-tv', 'Television with cable/satellite', 3),
('Mini Bar', 'mini-bar', 'bi-cup', 'Refrigerated mini bar', 4),
('Balcony', 'balcony', 'bi-door-open', 'Private balcony or terrace', 5),
('Sea View', 'sea-view', 'bi-water', 'Ocean or sea view', 6),
('Coffee Maker', 'coffee-maker', 'bi-cup-hot', 'Coffee/tea making facilities', 7),
('Refrigerator', 'refrigerator', 'bi-box-seam', 'Full-size refrigerator', 8),
('Bathtub', 'bathtub', 'bi-droplet', 'Bathtub in bathroom', 9),
('Shower', 'shower', 'bi-droplet-fill', 'Shower facilities', 10),
('Kitchen', 'kitchen', 'bi-egg-fry', 'Kitchen or kitchenette', 11),
('Safe Locker', 'safe-locker', 'bi-shield-lock', 'In-room safe for valuables', 12),
('Hair Dryer', 'hair-dryer', 'bi-wind', 'Hair dryer provided', 13),
('Iron', 'iron', 'bi-lightning', 'Iron and ironing board', 14),
('Telephone', 'telephone', 'bi-telephone', 'Direct dial telephone', 15)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

COMMIT;
