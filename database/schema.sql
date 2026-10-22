-- =====================================================================
-- ISHEMA - Hotel & Restaurant Management System
-- Database schema for MySQL 8+
-- Database: ishema_system
-- Multi-tenant SaaS: every tenant-owned table carries business_id
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `ishema_system`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ishema_system`;

-- ---------------------------------------------------------------------
-- USERS & ACCESS CONTROL
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(40) NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) NULL,
    `role` ENUM('SUPER_ADMIN','BUSINESS_OWNER','MANAGER','RECEPTIONIST','WAITER','KITCHEN_STAFF','CASHIER','HOUSEKEEPING','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
    `language` VARCHAR(5) NOT NULL DEFAULT 'en',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `email_verified_at` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `last_login_ip` VARCHAR(64) NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_role_index` (`role`),
    KEY `users_email_index` (`email`)
) ENGINE=InnoDB;

CREATE TABLE `businesses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` VARCHAR(32) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `type` ENUM('HOTEL','RESTAURANT','HOTEL_AND_RESTAURANT') NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(40) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `logo` VARCHAR(255) NULL,
    `cover_image` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `status` ENUM('active','suspended','pending') NOT NULL DEFAULT 'pending',
    `subscription_policy` ENUM('block','read_only','allow') NOT NULL DEFAULT 'block',
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'Africa/Kigali',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `businesses_business_id_unique` (`business_id`),
    KEY `businesses_type_index` (`type`),
    KEY `businesses_status_index` (`status`)
) ENGINE=InnoDB;

CREATE TABLE `business_users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('BUSINESS_OWNER','MANAGER','RECEPTIONIST','WAITER','KITCHEN_STAFF','CASHIER','HOUSEKEEPING','CUSTOMER') NOT NULL,
    `permissions` JSON NULL,
    `assigned_tables` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `bu_user_business_unique` (`user_id`,`business_id`),
    KEY `bu_business_role_index` (`business_id`,`role`)
) ENGINE=InnoDB;

CREATE TABLE `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(64) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(128) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `group` VARCHAR(64) NOT NULL DEFAULT 'general',
    PRIMARY KEY (`id`),
    UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB;

CREATE TABLE `user_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `granted` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `up_user_perm_bus_unique` (`user_id`,`permission_id`,`business_id`),
    KEY `up_user_index` (`user_id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SUBSCRIPTIONS & PACKAGES
-- ---------------------------------------------------------------------
CREATE TABLE `packages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(64) NOT NULL,
    `price_monthly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `price_yearly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_employees` INT NOT NULL DEFAULT 5,
    `max_rooms` INT NOT NULL DEFAULT 0,
    `max_tables` INT NOT NULL DEFAULT 0,
    `max_products` INT NOT NULL DEFAULT 100,
    `max_branches` INT NOT NULL DEFAULT 1,
    `modules` JSON NULL,
    `features` JSON NULL,
    `has_reports` TINYINT(1) NOT NULL DEFAULT 1,
    `support_level` ENUM('email','priority','dedicated') NOT NULL DEFAULT 'email',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `packages_slug_unique` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `package_id` INT UNSIGNED NOT NULL,
    `billing_cycle` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `starts_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `status` ENUM('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
    `payment_reference` VARCHAR(191) NULL,
    `auto_renew` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sub_business_index` (`business_id`),
    KEY `sub_status_expiry_index` (`status`,`expires_at`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RESTAURANT DOMAIN
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `type` ENUM('food','drinks','desserts','breakfast','other') NOT NULL DEFAULT 'other',
    `color` VARCHAR(16) NULL DEFAULT '#1056A0',
    `image` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cat_business_slug_unique` (`business_id`,`slug`),
    KEY `cat_business_active` (`business_id`,`is_active`)
) ENGINE=InnoDB;

CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `cost_price` DECIMAL(12,2) NULL DEFAULT 0.00,
    `image` VARCHAR(255) NULL,
    `barcode` VARCHAR(64) NULL,
    `stock_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `min_stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `prod_business_category` (`business_id`,`category_id`),
    KEY `prod_business_available` (`business_id`,`is_available`),
    KEY `prod_barcode` (`barcode`)
) ENGINE=InnoDB;

CREATE TABLE `suppliers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `contact_person` VARCHAR(191) NULL,
    `phone` VARCHAR(40) NULL,
    `email` VARCHAR(191) NULL,
    `address` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sup_business_index` (`business_id`)
) ENGINE=InnoDB;

CREATE TABLE `inventory_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('in','out','adjustment','order','waste') NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `reference` VARCHAR(191) NULL,
    `supplier_id` BIGINT UNSIGNED NULL,
    `notes` TEXT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `inv_business_product` (`business_id`,`product_id`),
    KEY `inv_product_created` (`product_id`,`created_at`)
) ENGINE=InnoDB;

CREATE TABLE `restaurant_tables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `table_number` VARCHAR(32) NOT NULL,
    `name` VARCHAR(100) NULL,
    `seats` INT NOT NULL DEFAULT 2,
    `status` ENUM('available','occupied','cleaning','reserved','out_of_service') NOT NULL DEFAULT 'available',
    `waiter_user_id` BIGINT UNSIGNED NULL,
    `floor` VARCHAR(50) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tables_business_number_unique` (`business_id`,`table_number`),
    KEY `tables_business_status` (`business_id`,`status`)
) ENGINE=InnoDB;

CREATE TABLE `qr_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `table_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(128) NOT NULL,
    `qr_path` VARCHAR(255) NULL,
    `label` VARCHAR(191) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `qr_token_unique` (`token`),
    UNIQUE KEY `qr_business_table_unique` (`business_id`,`table_id`)
) ENGINE=InnoDB;

CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `order_number` VARCHAR(40) NOT NULL,
    `table_id` BIGINT UNSIGNED NULL,
    `guest_name` VARCHAR(191) NULL,
    `guest_phone` VARCHAR(40) NULL,
    `waiter_user_id` BIGINT UNSIGNED NULL,
    `customer_user_id` BIGINT UNSIGNED NULL,
    `source` ENUM('qr','staff','hotel','api') NOT NULL DEFAULT 'qr',
    `status` ENUM('pending','confirmed','preparing','ready','delivered','partially_delivered','not_received','completed','cancelled') NOT NULL DEFAULT 'pending',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `language` VARCHAR(5) NULL DEFAULT 'en',
    `ordered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orders_business_number_unique` (`business_id`,`order_number`),
    KEY `orders_business_status` (`business_id`,`status`),
    KEY `orders_table` (`table_id`),
    KEY `orders_waiter` (`waiter_user_id`),
    KEY `orders_customer` (`customer_user_id`),
    KEY `orders_ordered_at` (`ordered_at`)
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `product_name` VARCHAR(191) NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `line_total` DECIMAL(12,2) NOT NULL,
    `delivered_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `confirmed_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `delivery_status` ENUM('pending','partial','full','not_received','cancelled') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `oi_order_index` (`order_id`),
    KEY `oi_business_product` (`business_id`,`product_id`),
    KEY `oi_delivery_status` (`delivery_status`)
) ENGINE=InnoDB;

CREATE TABLE `order_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_item_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `delivered_by_user_id` BIGINT UNSIGNED NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `status` ENUM('delivered','returned','failed') NOT NULL DEFAULT 'delivered',
    `notes` TEXT NULL,
    `delivered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `od_item_index` (`order_item_id`),
    KEY `od_order_index` (`order_id`),
    KEY `od_business` (`business_id`)
) ENGINE=InnoDB;

CREATE TABLE `delivery_confirmations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_item_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `customer_user_id` BIGINT UNSIGNED NULL,
    `ordered_quantity` DECIMAL(12,2) NOT NULL,
    `confirmed_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `status` ENUM('full','not_full','not_received') NOT NULL,
    `notes` TEXT NULL,
    `confirmed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `dc_item_index` (`order_item_id`),
    KEY `dc_order_index` (`order_id`),
    KEY `dc_business_status` (`business_id`,`status`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- HOTEL DOMAIN
-- ---------------------------------------------------------------------
CREATE TABLE `room_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `capacity` INT NOT NULL DEFAULT 1,
    `default_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amenities` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rt_business_slug_unique` (`business_id`,`slug`),
    KEY `rt_business` (`business_id`)
) ENGINE=InnoDB;

CREATE TABLE `rooms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `room_type_id` BIGINT UNSIGNED NOT NULL,
    `room_number` VARCHAR(50) NOT NULL,
    `floor` VARCHAR(50) NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `description` TEXT NULL,
    `images` JSON NULL,
    `status` ENUM('available','occupied','reserved','cleaning','maintenance','out_of_service') NOT NULL DEFAULT 'available',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rooms_business_number_unique` (`business_id`,`room_number`),
    KEY `rooms_business_status` (`business_id`,`status`),
    KEY `rooms_type` (`room_type_id`)
) ENGINE=InnoDB;

CREATE TABLE `guests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `first_name` VARCHAR(191) NOT NULL,
    `last_name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(40) NULL,
    `id_number` VARCHAR(100) NULL,
    `id_type` VARCHAR(50) NULL,
    `country` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `guests_business_index` (`business_id`),
    KEY `guests_email` (`email`),
    KEY `guests_phone` (`phone`)
) ENGINE=InnoDB;

CREATE TABLE `bookings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `booking_reference` VARCHAR(40) NOT NULL,
    `guest_id` BIGINT UNSIGNED NOT NULL,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `guest_user_id` BIGINT UNSIGNED NULL,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `adults` INT NOT NULL DEFAULT 1,
    `children` INT NOT NULL DEFAULT 0,
    `nights` INT NOT NULL DEFAULT 1,
    `status` ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'pending',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('pending','paid','partial','refunded') NOT NULL DEFAULT 'pending',
    `special_requests` TEXT NULL,
    `notes` TEXT NULL,
    `checked_in_at` DATETIME NULL,
    `checked_out_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `bookings_reference_unique` (`booking_reference`),
    KEY `bk_business_status` (`business_id`,`status`),
    KEY `bk_guest` (`guest_id`),
    KEY `bk_room_dates` (`room_id`,`check_in_date`,`check_out_date`),
    KEY `bk_guest_user` (`guest_user_id`)
) ENGINE=InnoDB;

CREATE TABLE `housekeeping` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `room_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('cleaning','maintenance','inspection') NOT NULL DEFAULT 'cleaning',
    `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `assigned_to_user_id` BIGINT UNSIGNED NULL,
    `notes` TEXT NULL,
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hk_business_status` (`business_id`,`status`),
    KEY `hk_room` (`room_id`),
    KEY `hk_assigned` (`assigned_to_user_id`)
) ENGINE=InnoDB;

CREATE TABLE `hotel_services` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `guest_id` BIGINT UNSIGNED NULL,
    `booking_id` BIGINT UNSIGNED NULL,
    `guest_user_id` BIGINT UNSIGNED NULL,
    `service_type` ENUM('room_service','restaurant_order','laundry','transport','other') NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hs_business_status` (`business_id`,`status`),
    KEY `hs_guest` (`guest_id`),
    KEY `hs_booking` (`booking_id`),
    KEY `hs_guest_user` (`guest_user_id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAYMENTS, REVIEWS, NOTIFICATIONS, STAFF
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `payable_type` ENUM('order','booking','subscription','service','other') NOT NULL,
    `payable_id` BIGINT UNSIGNED NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(8) NOT NULL DEFAULT 'RWF',
    `method` ENUM('mtn_momo','airtel_money','bank_transfer','visa_card','cash') NOT NULL,
    `status` ENUM('pending','success','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    `reference` VARCHAR(191) NULL,
    `provider` VARCHAR(64) NULL,
    `provider_reference` VARCHAR(191) NULL,
    `payer_name` VARCHAR(191) NULL,
    `payer_phone` VARCHAR(40) NULL,
    `paid_by_user_id` BIGINT UNSIGNED NULL,
    `metadata` JSON NULL,
    `paid_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_reference_unique` (`reference`),
    KEY `pay_business` (`business_id`),
    KEY `pay_payable` (`payable_type`,`payable_id`),
    KEY `pay_status` (`status`)
) ENGINE=InnoDB;

CREATE TABLE `payment_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `provider` VARCHAR(64) NULL,
    `provider_reference` VARCHAR(191) NULL,
    `request_payload` JSON NULL,
    `response_payload` JSON NULL,
    `status` VARCHAR(50) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `pt_payment_index` (`payment_id`),
    KEY `pt_provider_ref` (`provider_reference`)
) ENGINE=InnoDB;

CREATE TABLE `reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `service_type` ENUM('restaurant_order','hotel_stay','service') NOT NULL,
    `service_id` BIGINT UNSIGNED NULL,
    `customer_user_id` BIGINT UNSIGNED NULL,
    `guest_id` BIGINT UNSIGNED NULL,
    `food_quality` TINYINT NULL,
    `service_rating` TINYINT NULL,
    `speed_rating` TINYINT NULL,
    `overall_rating` TINYINT NOT NULL,
    `comment` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `rev_business` (`business_id`),
    KEY `rev_service` (`service_type`,`service_id`)
) ENGINE=InnoDB;

CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(64) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `data` JSON NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `notif_user_read` (`user_id`,`is_read`),
    KEY `notif_business` (`business_id`),
    KEY `notif_created` (`created_at`)
) ENGINE=InnoDB;

CREATE TABLE `shifts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `shifts_business` (`business_id`)
) ENGINE=InnoDB;

CREATE TABLE `staff_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `business_user_id` BIGINT UNSIGNED NOT NULL,
    `shift_id` BIGINT UNSIGNED NULL,
    `table_id` BIGINT UNSIGNED NULL,
    `assignment_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sa_business_date` (`business_id`,`assignment_date`),
    KEY `sa_staff` (`business_user_id`)
) ENGINE=InnoDB;

CREATE TABLE `branches` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(40) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `branches_business` (`business_id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CMS / PLATFORM
-- ---------------------------------------------------------------------
CREATE TABLE `tutorials` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `category` ENUM('hotel','restaurant','owner','employee','general') NOT NULL,
    `language` VARCHAR(5) NOT NULL DEFAULT 'en',
    `video_url` VARCHAR(512) NOT NULL,
    `thumbnail` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `duration` VARCHAR(20) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tut_slug_unique` (`slug`),
    KEY `tut_category` (`category`)
) ENGINE=InnoDB;

CREATE TABLE `contact_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(40) NULL,
    `subject` VARCHAR(255) NULL,
    `business_type` VARCHAR(50) NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `cm_is_read` (`is_read`)
) ENGINE=InnoDB;

CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `business_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(191) NOT NULL,
    `entity` VARCHAR(191) NULL,
    `entity_id` VARCHAR(191) NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(64) NULL,
    `user_agent` VARCHAR(512) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_user` (`user_id`),
    KEY `audit_business` (`business_id`),
    KEY `audit_entity` (`entity`,`entity_id`),
    KEY `audit_created` (`created_at`)
) ENGINE=InnoDB;

CREATE TABLE `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NULL,
    `setting_key` VARCHAR(191) NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_bus_key_unique` (`business_id`,`setting_key`)
) ENGINE=InnoDB;

CREATE TABLE `password_resets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `token` VARCHAR(191) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `pr_email_index` (`email`),
    KEY `pr_token_index` (`token`)
) ENGINE=InnoDB;

CREATE TABLE `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `ip_address` VARCHAR(64) NULL,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `la_email_time` (`email`,`attempted_at`)
) ENGINE=InnoDB;
