-- Digital Store Database Schema (Backend)
-- Encoding: UTF-8

CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `permissions` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NULL, 
    `phone` VARCHAR(20) NULL,
    `is_guest` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
);

CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `promotions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10,2) NOT NULL,
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `price_dzd` DECIMAL(10,2) NOT NULL,
    `old_price_dzd` DECIMAL(10,2) NULL,
    `promotion_id` INT NULL,
    `product_type` ENUM('digital', 'subscription', 'account', 'license', 'activation', 'code', 'custom') NOT NULL,
    `delivery_type` ENUM('code', 'email', 'manual', 'custom') NOT NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
    FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`)
);

CREATE TABLE `product_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_type` VARCHAR(50) DEFAULT 'text',
    `is_required` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

CREATE TABLE `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10,2) NOT NULL,
    `expiration_date` DATETIME NULL,
    `usage_limit` INT NULL,
    `used_count` INT DEFAULT 0,
    `minimum_order_amount` DECIMAL(10,2) NULL,
    `specific_product_id` INT NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`specific_product_id`) REFERENCES `products`(`id`)
);

CREATE TABLE `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL UNIQUE,
    `is_unlimited` BOOLEAN DEFAULT FALSE,
    `stock_count` INT DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

CREATE TABLE `inventory_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inventory_id` INT NOT NULL,
    `item_data` TEXT NOT NULL,
    `is_used` BOOLEAN DEFAULT FALSE,
    `used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE
);

CREATE TABLE `payment_methods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `provider_type` ENUM('manual', 'usdt', 'baridimob', 'ccp', 'binance', 'flexy') NOT NULL,
    `details` JSON NULL,
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NULL,
    `total_dzd` DECIMAL(10,2) NOT NULL,
    `coupon_id` INT NULL,
    `payment_method_id` INT NULL,
    `payment_status` ENUM('pending', 'pending_verification', 'paid', 'failed', 'refunded', 'cancelled', 'expired') DEFAULT 'pending',
    `order_status` ENUM('pending', 'processing', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
    `secure_token` VARCHAR(64) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`),
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`)
);

CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `customer_input_data` JSON NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
);

CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `payment_method_id` INT NOT NULL,
    `price_dzd` DECIMAL(10,2) NOT NULL,
    `exchange_rate` DECIMAL(10,4) NULL,
    `rate_source` VARCHAR(50) NULL,
    `amount_usdt` DECIMAL(10,4) NULL,
    `asset_code` VARCHAR(50) NULL,
    `network` VARCHAR(50) NULL,
    `q8qpay_invoice_id` VARCHAR(100) NULL,
    `transaction_id` VARCHAR(100) NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded', 'expired') DEFAULT 'pending',
    `expires_at` TIMESTAMP NULL,
    `details` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`)
);

CREATE TABLE `payment_proofs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `payment_id` INT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `reviewed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
    FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`),
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`)
);

CREATE TABLE `deliveries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_item_id` INT NOT NULL,
    `delivery_type` ENUM('code', 'email', 'manual', 'custom') NOT NULL,
    `delivery_data` TEXT NOT NULL,
    `delivered_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`),
    FOREIGN KEY (`delivered_by`) REFERENCES `admins`(`id`)
);

CREATE TABLE `webhook_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL,
    `reference` VARCHAR(100) NULL, -- Event ID or Invoice ID to prevent duplicates
    `payload` JSON NULL,
    `headers` JSON NULL,
    `processed` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('sent', 'failed') DEFAULT 'sent',
    `error_msg` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Initial Roles
INSERT INTO `roles` (`name`, `permissions`) VALUES
('Super Admin', '["all"]'),
('Admin', '["manage_products", "manage_orders", "manage_customers"]'),
('Support', '["manage_orders", "view_customers"]'),
('Viewer', '["view_dashboard", "view_stats"]');
