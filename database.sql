-- Vytvoření databáze
CREATE DATABASE IF NOT EXISTS eshop;
USE eshop;

-- Vytvoření tabulky uživatelů
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vytvoření tabulky produktů
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vytvoření tabulky objednávek
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Vytvoření tabulky položek objednávek
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Přidání testovacího administrátora
INSERT INTO users (first_name, last_name, email, password, role)
VALUES ('Admin', 'User', 'admin@example.com', '$2y$10$abcdefghijklmnopqrstuvwx', 'admin');
    
-- Přidání testovacího uživatele
INSERT INTO users (first_name, last_name, email, password, role)
VALUES ('Test', 'User', 'user@example.com', '$2y$10$abcdefghijklmnopqrstuvwx', 'user');

-- Přidání testovacího produktu
INSERT INTO products (name, description, price, category, image)
VALUES ('Proteinový prášek', 'Vysoce kvalitní protein pro sportovce.', 499.99, 'Doplňky stravy', NULL);

-- Přidání testovací objednávky
INSERT INTO orders (user_id, total_price, status)
VALUES (2, 499.99, 'completed');

-- Přidání testovací položky objednávky
INSERT INTO order_items (order_id, product_id, quantity, price)
VALUES (1, 1, 1, 499.99);
