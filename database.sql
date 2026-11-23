CREATE DATABASE IF NOT EXISTS subsync_db;
USE subsync_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  service_name VARCHAR(100) NOT NULL,
  category VARCHAR(50),
  billing_cycle ENUM('monthly','yearly') NOT NULL,
  cost DECIMAL(10,2) NOT NULL,
  renewal_date DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subscription_id INT NOT NULL,
  remind_days_before INT DEFAULT 7,
  status ENUM('pending','sent','dismissed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);
