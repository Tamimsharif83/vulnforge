-- ForgeShop seed data. Demo credentials only; see README.
SET NAMES utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(128) NOT NULL,
  password_md5 CHAR(32) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  balance DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  bio VARCHAR(255) NOT NULL DEFAULT ''
);

INSERT INTO users (username, email, password_md5, role, balance, bio) VALUES
  ('admin', 'admin@forgeshop.local', MD5('Forge!Admin#2026'), 'admin', 5000.00, 'Store administrator'),
  ('alice', 'alice@forgeshop.local', MD5('alice123'), 'user', 100.00, 'Keeps buying keyboards'),
  ('bob',   'bob@forgeshop.local',   MD5('bob123'),   'user', 100.00, 'Reviews everything');

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  category VARCHAR(64) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description VARCHAR(255) NOT NULL
);

INSERT INTO products (name, category, price, description) VALUES
  ('Anvil Mechanical Keyboard', 'Peripherals', 129.00, 'Hot-swappable 75 percent board'),
  ('Ember Wireless Mouse', 'Peripherals', 59.00, 'Lightweight, 70 hour battery'),
  ('Crucible USB-C Dock', 'Accessories', 89.00, 'Eleven ports, 100W passthrough'),
  ('Tongs Cable Organizer', 'Accessories', 14.00, 'Magnetic desk cable clips'),
  ('Bellows Desk Fan', 'Office', 39.00, 'Quiet USB-powered airflow'),
  ('Slag Monitor Arm', 'Office', 74.00, 'Gas spring, supports 32 inch panels');

CREATE TABLE lab_progress (
  student CHAR(32) NOT NULL,
  slug VARCHAR(64) NOT NULL,
  level VARCHAR(16) NOT NULL,
  solved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student, slug, level)
);

CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_uid INT NOT NULL,
  detail VARCHAR(255) NOT NULL
);
INSERT INTO invoices (owner_uid, detail) VALUES
  (1, 'Admin corporate card ending 4242. Note: FORGE-INVOICE{idor_exposed_another_users_record}'),
  (2, 'Alice order: Anvil Mechanical Keyboard, 129.00'),
  (2, 'Alice order: Ember Wireless Mouse, 59.00'),
  (3, 'Bob order: Crucible USB-C Dock, 89.00');
