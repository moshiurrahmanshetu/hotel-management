-- ============================================
-- Hotel & Resort Management System
-- Rooms Module Tables
-- ============================================

-- ============================================
-- Drop existing tables if they exist
-- ============================================
DROP TABLE IF EXISTS `room_amenities`;
DROP TABLE IF EXISTS `room_notes`;
DROP TABLE IF EXISTS `rooms`;

-- ============================================
-- Rooms Table
-- ============================================
CREATE TABLE `rooms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(36) NOT NULL,
    `property_id` INT UNSIGNED NOT NULL,
    `building_id` INT UNSIGNED NOT NULL,
    `floor_id` INT UNSIGNED NOT NULL,
    `room_number` VARCHAR(20) NOT NULL,
    `room_name` VARCHAR(100) NULL,
    `room_category_id` INT UNSIGNED NULL,
    `room_type_id` INT UNSIGNED NULL,
    `bed_type_id` INT UNSIGNED NULL,
    `view_type_id` INT UNSIGNED NULL,
    `max_adults` INT UNSIGNED NOT NULL DEFAULT 2,
    `max_children` INT UNSIGNED NOT NULL DEFAULT 0,
    `room_size` DECIMAL(10, 2) NULL,
    `size_unit` VARCHAR(20) NULL DEFAULT 'sqft',
    `base_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('available', 'occupied', 'reserved', 'maintenance', 'cleaning', 'out_of_service') NOT NULL DEFAULT 'available',
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rooms_uuid` (`uuid`),
    UNIQUE KEY `uk_rooms_property_number` (`property_id`, `room_number`),
    KEY `idx_rooms_property_id` (`property_id`),
    KEY `idx_rooms_building_id` (`building_id`),
    KEY `idx_rooms_floor_id` (`floor_id`),
    KEY `idx_rooms_room_number` (`room_number`),
    KEY `idx_rooms_status` (`status`),
    KEY `idx_rooms_is_active` (`is_active`),
    KEY `idx_rooms_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_rooms_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rooms_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Amenities Junction Table
-- ============================================
CREATE TABLE `room_amenities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id` INT UNSIGNED NOT NULL,
    `amenity_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_amenities_room_amenity` (`room_id`, `amenity_id`),
    KEY `idx_room_amenities_room_id` (`room_id`),
    KEY `idx_room_amenities_amenity_id` (`amenity_id`),
    CONSTRAINT `fk_room_amenities_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_room_amenities_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `master_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Room Notes Table
-- ============================================
CREATE TABLE `room_notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id` INT UNSIGNED NOT NULL,
    `note` TEXT NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room_notes_room_id` (`room_id`),
    KEY `idx_room_notes_created_by` (`created_by`),
    CONSTRAINT `fk_room_notes_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_room_notes_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
