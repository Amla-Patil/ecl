-- ============================================================
-- schema.sql — Database Schema
-- Run this in your MySQL client or phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS my_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE my_website;

-- ─── USERS ───
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100)  NOT NULL UNIQUE,
    email      VARCHAR(150)  DEFAULT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('user','admin') DEFAULT 'user',
    created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
);

-- ─── PRODUCTS ───
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200)   NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    category    VARCHAR(100),
    image       VARCHAR(300),
    stock       INT            DEFAULT 0,
    created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP
);

-- ─── CONTACT MESSAGES ───
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL,
    subject    VARCHAR(200),
    message    TEXT          NOT NULL,
    created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
);

-- ─── ORDERS (for cart checkout) ───
CREATE TABLE IF NOT EXISTS orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    total      DECIMAL(10,2) NOT NULL,
    status     ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ─── ORDER ITEMS ───
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    qty        INT NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ─── Sample Products ───
INSERT INTO products (name, description, price, category, stock) VALUES
('Product One',   'A great product.',   999.00,  'Electronics', 50),
('Product Two',   'Another product.',   499.00,  'Clothing',    100),
('Product Three', 'Third product.',     1299.00, 'Electronics', 25);
