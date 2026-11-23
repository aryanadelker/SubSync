# SubSync

A PHP + MySQL web app for tracking recurring subscriptions in one place. Add services with their category, billing cycle, cost and renewal date, then see spending, upcoming renewals and reminders at a glance.

## Features
- User registration and login (hashed passwords, session-based auth)
- Add, edit and delete subscriptions
- Dashboard with monthly/yearly totals, category breakdown and renewals due in 7 days
- Reports page with Chart.js charts (by category and billing cycle)
- Auto-generated renewal reminders with pending / sent / dismissed status

## Tech Stack
PHP, MySQL (mysqli), HTML/CSS, Chart.js

## Project Structure
```
SubSync/
├── includes/db.php      # Database connection
├── database.sql         # Tables schema
├── css/style.css
├── index.php
├── register.php
├── login.php
├── logout.php
├── dashboard.php
├── subscriptions.php
├── reminders.php
└── reports.php
```

## Setup
1. Install [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack) and start Apache and MySQL.
2. Copy the `SubSync` folder into `htdocs`.
3. In phpMyAdmin, open the SQL tab and import/run `database.sql` (or run the SQL below):

```sql
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
```
4. Check the credentials in `includes/db.php` (defaults: `localhost`, `root`, empty password).
5. Open `http://localhost/SubSync/` and register an account.

## Notes
- Change the database credentials before deploying anywhere public.
