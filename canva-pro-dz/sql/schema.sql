CREATE DATABASE IF NOT EXISTS canva_pro_dz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE canva_pro_dz;

CREATE TABLE IF NOT EXISTS admins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_code VARCHAR(24) NOT NULL UNIQUE,
  tracking_token_hash CHAR(64) NOT NULL UNIQUE,
  customer_email VARCHAR(254) NOT NULL,
  customer_name VARCHAR(120) NULL,
  preferred_payment ENUM('BINANCE','FLEXY') NOT NULL,
  amount_dzd DECIMAL(10,2) NOT NULL DEFAULT 300.00,
  currency_estimate DECIMAL(12,6) NULL,
  estimate_currency VARCHAR(8) NULL,
  exchange_rate DECIMAL(18,8) NULL,
  exchange_rate_at DATETIME NULL,
  status ENUM('NEW','EMAIL_CHECK','ACTIVATION_PENDING','ACTIVATION_SENT','CUSTOMER_CONFIRMED','AWAITING_PAYMENT','PAYMENT_SUBMITTED','PAYMENT_REVIEW','PAID','COMPLETED','REJECTED','CANCELLED','REFUND_PENDING','REFUNDED') NOT NULL DEFAULT 'NEW',
  activation_sent_at DATETIME NULL,
  customer_confirmed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_status_created (status, created_at),
  INDEX idx_orders_email (customer_email),
  INDEX idx_orders_payment (preferred_payment)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  actor_type ENUM('CUSTOMER','ADMIN','SYSTEM') NOT NULL,
  event_key VARCHAR(60) NOT NULL,
  status VARCHAR(40) NULL,
  note VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_events_order_time (order_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_proofs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  storage_name VARCHAR(120) NOT NULL,
  mime_type VARCHAR(40) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  customer_payment_time DATETIME NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reference_text VARCHAR(160) NULL,
  review_status ENUM('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING',
  review_note VARCHAR(500) NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  CONSTRAINT fk_proofs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_proofs_admin FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_proofs_order_time (order_id, submitted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  method ENUM('BINANCE','FLEXY') NOT NULL,
  amount_dzd DECIMAL(10,2) NOT NULL,
  status ENUM('PENDING','CONFIRMED','REJECTED','REFUND_PENDING','REFUNDED') NOT NULL DEFAULT 'PENDING',
  transaction_reference VARCHAR(160) NULL,
  confirmed_by BIGINT UNSIGNED NULL,
  confirmed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_admin FOREIGN KEY (confirmed_by) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_payments_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exchange_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_currency VARCHAR(8) NOT NULL,
  quote_currency VARCHAR(8) NOT NULL,
  rate DECIMAL(18,8) NOT NULL,
  source VARCHAR(120) NOT NULL,
  fetched_at DATETIME NOT NULL,
  INDEX idx_rate_pair_time (base_currency, quote_currency, fetched_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_key CHAR(64) NOT NULL,
  page_path VARCHAR(190) NOT NULL,
  visited_on DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visits_day (visited_on),
  INDEX idx_visits_key_day (visit_key, visited_on)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS analytics_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_key VARCHAR(60) NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  anonymous_key CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_analytics_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  INDEX idx_analytics_event_time (event_key, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NULL,
  action_key VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id BIGINT UNSIGNED NULL,
  details VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_admin_logs_time (created_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Canva Pro DZ'),
('price_dzd', '300'),
('service_duration', 'Set duration before publishing'),
('flexy_number', '0654103330'),
('support_email', ''),
('payment_binance_instructions', ''),
('payment_flexy_instructions', 'Flexy Normal — التعبئة العادية. احتفظ بمرجع العملية وأرسل الإثبات للمراجعة.'),
('default_language', 'ar'),
('exchange_display_currency', 'USDT');
