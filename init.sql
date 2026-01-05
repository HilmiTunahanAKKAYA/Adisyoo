-- Basit başlangıç migrasyonu: veritabanı `adisyon` oluşturun ve aşağıdaki tabloları çalıştırın.

-- Not: phpMyAdmin veya mysql CLI ile çalıştırın.

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `tables` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NULL,
  status VARCHAR(30) DEFAULT 'open',
  total DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'staff'
);


INSERT INTO categories (name) VALUES ('İçecekler'), ('Ana Yemek'), ('Tatlı');



INSERT INTO `tables` (name) VALUES ('Masa 1'),('Masa 2'),('Paket Servis');

-- Örnek kullanıcı: şifre 'admin' (bcrypt hash). Not: üretimde farklı bir parola kullanın.
INSERT INTO users (username, password_hash, role) VALUES ('admin', '$2y$10$exampleplaceholderhashchangeit', 'manager');
