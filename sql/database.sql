-- Table des services
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL, -- ID du service chez Exobooster
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    rate_per_1000 DECIMAL(10,4),
    min_amount INT,
    max_amount INT,
    our_price DECIMAL(10,4),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE,
    service_id INT,
    link VARCHAR(500),
    quantity INT,
    price DECIMAL(10,4),
    exobooster_order_id INT,
    status ENUM('pending', 'in progress', 'completed', 'partial', 'refunded', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Table des transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    transaction_id VARCHAR(255),
    payment_gateway VARCHAR(50),
    amount DECIMAL(10,4),
    status ENUM('pending', 'completed', 'failed'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Table des paramètres (marge, etc.)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    margin_percentage DECIMAL(5,2) DEFAULT 30.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);