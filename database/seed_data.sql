-- ============================================
-- Hotel & Resort Management System
-- Default Roles, Permissions, and Super Admin User
-- ============================================

-- ============================================
-- Default Roles
-- ============================================
INSERT INTO `roles` (`name`, `slug`, `description`, `is_default`, `created_at`, `updated_at`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 0, NOW(), NOW()),
('Admin', 'admin', 'Administrative access with most permissions', 0, NOW(), NOW()),
('Manager', 'manager', 'Management access for hotel operations', 0, NOW(), NOW()),
('Receptionist', 'receptionist', 'Front desk operations and guest management', 0, NOW(), NOW()),
('Accountant', 'accountant', 'Financial and billing operations', 0, NOW(), NOW()),
('Housekeeping', 'housekeeping', 'Room cleaning and maintenance operations', 0, NOW(), NOW()),
('Staff', 'staff', 'Basic staff access', 0, NOW(), NOW());

-- ============================================
-- Default Permissions
-- ============================================
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
-- Dashboard
('View Dashboard', 'dashboard.view', 'dashboard', 'View dashboard and statistics', NOW(), NOW()),

-- Users
('View Users', 'users.view', 'users', 'View user list and details', NOW(), NOW()),
('Create Users', 'users.create', 'users', 'Create new users', NOW(), NOW()),
('Edit Users', 'users.edit', 'users', 'Edit user information', NOW(), NOW()),
('Delete Users', 'users.delete', 'users', 'Delete users', NOW(), NOW()),

-- Roles
('View Roles', 'roles.view', 'roles', 'View role list and details', NOW(), NOW()),
('Create Roles', 'roles.create', 'roles', 'Create new roles', NOW(), NOW()),
('Edit Roles', 'roles.edit', 'roles', 'Edit role information', NOW(), NOW()),
('Delete Roles', 'roles.delete', 'roles', 'Delete roles', NOW(), NOW()),
('Manage Permissions', 'roles.permissions', 'roles', 'Manage role permissions', NOW(), NOW()),

-- Settings
('View Settings', 'settings.view', 'settings', 'View system settings', NOW(), NOW()),
('Edit Settings', 'settings.edit', 'settings', 'Edit system settings', NOW(), NOW()),

-- Rooms
('View Rooms', 'rooms.view', 'rooms', 'View room list and details', NOW(), NOW()),
('Create Rooms', 'rooms.create', 'rooms', 'Create new rooms', NOW(), NOW()),
('Edit Rooms', 'rooms.edit', 'rooms', 'Edit room information', NOW(), NOW()),
('Delete Rooms', 'rooms.delete', 'rooms', 'Delete rooms', NOW(), NOW()),

-- Bookings
('View Bookings', 'bookings.view', 'bookings', 'View booking list and details', NOW(), NOW()),
('Create Bookings', 'bookings.create', 'bookings', 'Create new bookings', NOW(), NOW()),
('Edit Bookings', 'bookings.edit', 'bookings', 'Edit booking information', NOW(), NOW()),
('Delete Bookings', 'bookings.delete', 'bookings', 'Delete bookings', NOW(), NOW()),
('Check In', 'bookings.checkin', 'bookings', 'Check in guests', NOW(), NOW()),
('Check Out', 'bookings.checkout', 'bookings', 'Check out guests', NOW(), NOW()),

-- Guests
('View Guests', 'guests.view', 'guests', 'View guest list and details', NOW(), NOW()),
('Create Guests', 'guests.create', 'guests', 'Create new guest profiles', NOW(), NOW()),
('Edit Guests', 'guests.edit', 'guests', 'Edit guest information', NOW(), NOW()),
('Delete Guests', 'guests.delete', 'guests', 'Delete guest profiles', NOW(), NOW()),

-- Payments
('View Payments', 'payments.view', 'payments', 'View payment list and details', NOW(), NOW()),
('Create Payments', 'payments.create', 'payments', 'Create new payments', NOW(), NOW()),
('Edit Payments', 'payments.edit', 'payments', 'Edit payment information', NOW(), NOW()),
('Delete Payments', 'payments.delete', 'payments', 'Delete payments', NOW(), NOW()),
('View Invoices', 'payments.invoices', 'payments', 'View and manage invoices', NOW(), NOW()),

-- Reports
('View Reports', 'reports.view', 'reports', 'View system reports', NOW(), NOW()),
('Export Reports', 'reports.export', 'reports', 'Export reports', NOW(), NOW());

-- ============================================
-- Role Permissions Assignment
-- ============================================

-- Super Admin: All permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Admin: All permissions except delete roles
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE slug NOT IN ('roles.delete');

-- Manager: Dashboard, Rooms, Bookings, Guests, Reports (view only)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE slug IN (
    'dashboard.view',
    'rooms.view', 'rooms.create', 'rooms.edit',
    'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.checkin', 'bookings.checkout',
    'guests.view', 'guests.create', 'guests.edit',
    'reports.view'
);

-- Receptionist: Dashboard, Rooms (view), Bookings, Guests
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE slug IN (
    'dashboard.view',
    'rooms.view',
    'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.checkin', 'bookings.checkout',
    'guests.view', 'guests.create', 'guests.edit'
);

-- Accountant: Dashboard, Payments, Reports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE slug IN (
    'dashboard.view',
    'payments.view', 'payments.create', 'payments.edit', 'payments.invoices',
    'reports.view', 'reports.export'
);

-- Housekeeping: Dashboard, Rooms (view, edit)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM `permissions` WHERE slug IN (
    'dashboard.view',
    'rooms.view', 'rooms.edit'
);

-- Staff: Dashboard only
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM `permissions` WHERE slug = 'dashboard.view';

-- ============================================
-- Default Super Admin User
-- ============================================
-- Password: Admin@123
INSERT INTO `users` (`uuid`, `first_name`, `last_name`, `email`, `username`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
(UUID(), 'Super', 'Admin', 'admin@example.com', 'admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyY1W5ZqZq5e', 1, NOW(), NOW());

-- Assign Super Admin role to the user
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`)
SELECT id, 1, NOW() FROM `users` WHERE email = 'admin@example.com';
