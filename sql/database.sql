-- sql/database.sql

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `lksboost` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `lksboost`;

-- Table des paramètres
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `margin_percentage` DECIMAL(5,2) DEFAULT 30.00,
    `site_name` VARCHAR(255) DEFAULT 'LKSBoost',
    `site_email` VARCHAR(255) DEFAULT 'contact@lksboost.com',
    `currency` VARCHAR(10) DEFAULT '€',
    `min_order_amount` DECIMAL(10,4) DEFAULT 1.00,
    `auto_update_services` BOOLEAN DEFAULT TRUE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des administrateurs
CREATE TABLE `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255),
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_login` TIMESTAMP NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des services
CREATE TABLE `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT NOT NULL, -- ID du service chez Exobooster
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `rate_per_1000` DECIMAL(10,4) NOT NULL,
    `min_amount` INT NOT NULL,
    `max_amount` INT NOT NULL,
    `our_price` DECIMAL(10,4) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_featured` BOOLEAN DEFAULT FALSE,
    `api_data` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `service_id` INT NOT NULL,
    `link` VARCHAR(500) NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,4) NOT NULL,
    `exobooster_order_id` INT,
    `status` ENUM('pending', 'in progress', 'completed', 'partial', 'refunded', 'cancelled') DEFAULT 'pending',
    `start_count` INT DEFAULT 0,
    `remains` INT DEFAULT 0,
    `currency` VARCHAR(10) DEFAULT '€',
    `user_ip` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT
);

-- Table des transactions
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `transaction_id` VARCHAR(255) NOT NULL,
    `payment_gateway` ENUM('stripe', 'paypal', 'cinetpay', 'manual') NOT NULL,
    `amount` DECIMAL(10,4) NOT NULL,
    `currency` VARCHAR(10) DEFAULT '€',
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `gateway_response` JSON,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
);

-- Table des logs d'API
CREATE TABLE `api_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `endpoint` VARCHAR(255) NOT NULL,
    `request_data` JSON,
    `response_data` JSON,
    `http_code` INT,
    `error_message` TEXT,
    `duration` DECIMAL(5,3),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des données initiales
INSERT INTO `settings` (`margin_percentage`, `site_name`, `site_email`, `currency`) 
VALUES (30.00, 'LKSBoost', 'contact@lksboost.com', '€');

INSERT INTO `admin_users` (`username`, `password_hash`, `email`, `full_name`) 
VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
    'admin@lksboost.com', 
    'Administrateur Principal'
);

-- Création des index
CREATE INDEX idx_services_category ON services(category);
CREATE INDEX idx_services_active ON services(is_active);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_transactions_status ON transactions(status);