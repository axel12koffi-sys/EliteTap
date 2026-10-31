-- EliteTap — schéma MySQL 8 / MariaDB 10.4+
-- Import : phpMyAdmin > Importer, ou : mysql -u USER -p DBNAME < schema.mysql.sql

SET NAMES utf8mb4;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(120) NOT NULL,
  role          ENUM('client','admin') NOT NULL DEFAULT 'client',
  lang          CHAR(2) NOT NULL DEFAULT 'fr',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  plan          ENUM('essentiel','pro','entreprise') NOT NULL DEFAULT 'essentiel',
  plan_source   ENUM('manual','stripe') NOT NULL DEFAULT 'manual',
  plan_status   VARCHAR(20) NULL,
  plan_renews_at DATETIME NULL,
  stripe_customer_id VARCHAR(64) NULL,
  stripe_subscription_id VARCHAR(64) NULL,
  reset_token_hash CHAR(64) NULL,
  reset_expires_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profiles (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  slug         VARCHAR(60) NOT NULL UNIQUE,
  first_name   VARCHAR(80) NOT NULL DEFAULT '',
  last_name    VARCHAR(80) NOT NULL DEFAULT '',
  title        VARCHAR(120) NOT NULL DEFAULT '',
  company      VARCHAR(120) NOT NULL DEFAULT '',
  bio          VARCHAR(300) NOT NULL DEFAULT '',
  phone        VARCHAR(40)  NOT NULL DEFAULT '',
  email        VARCHAR(190) NOT NULL DEFAULT '',
  website      VARCHAR(255) NOT NULL DEFAULT '',
  whatsapp     VARCHAR(40)  NOT NULL DEFAULT '',
  address      VARCHAR(255) NOT NULL DEFAULT '',
  avatar_path  VARCHAR(255) NULL,
  logo_path    VARCHAR(255) NULL,
  theme        VARCHAR(20) NOT NULL DEFAULT 'marine',
  font         VARCHAR(12) NOT NULL DEFAULT 'moderne',
  lead_form    TINYINT(1) NOT NULL DEFAULT 1,
  lead_alerts  TINYINT(1) NOT NULL DEFAULT 1,
  cover_color  CHAR(7) NOT NULL DEFAULT '#14161A',
  accent_color CHAR(7) NOT NULL DEFAULT '#2563EB',
  is_public    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL,
  updated_at   DATETIME NOT NULL,
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE links (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED NOT NULL,
  label      VARCHAR(80) NOT NULL,
  url        VARCHAR(500) NOT NULL,
  position   INT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_links_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
  INDEX idx_links_profile (profile_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Une carte physique. Le code est encodé dans la puce NFC et le QR : https://elitetap.ca/t/{code}
-- La carte peut être réassignée à un autre profil sans être reprogrammée.
CREATE TABLE cards (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(16) NOT NULL UNIQUE,
  nfc_uid      VARCHAR(32) NULL,
  material     VARCHAR(40) NOT NULL DEFAULT 'Standard',
  profile_id   INT UNSIGNED NULL,
  order_id     INT UNSIGNED NULL,
  status       ENUM('stock','active','disabled') NOT NULL DEFAULT 'stock',
  activated_at DATETIME NULL,
  created_at   DATETIME NOT NULL,
  CONSTRAINT fk_cards_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE SET NULL,
  INDEX idx_cards_profile (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference    VARCHAR(20) NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  company      VARCHAR(120) NOT NULL DEFAULT '',
  email        VARCHAR(190) NOT NULL,
  phone        VARCHAR(40)  NOT NULL DEFAULT '',
  card_type    VARCHAR(40)  NOT NULL,
  quantity     INT UNSIGNED NOT NULL DEFAULT 1,
  message      TEXT NULL,
  file_path    VARCHAR(255) NULL,
  file_name    VARCHAR(255) NULL,
  status       ENUM('devis','design','epreuve','production','expediee','annulee') NOT NULL DEFAULT 'devis',
  amount_cents INT UNSIGNED NULL,
  user_id      INT UNSIGNED NULL,
  admin_notes  TEXT NULL,
  lang         CHAR(2) NOT NULL DEFAULT 'fr',
  pay_token    CHAR(32) NULL,
  paid_at      DATETIME NULL,
  payment_ref  VARCHAR(120) NULL,
  created_at   DATETIME NOT NULL,
  updated_at   DATETIME NOT NULL,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE cards ADD CONSTRAINT fk_cards_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- Statistiques. L'IP n'est jamais stockée en clair (hash salé, Loi 25).
CREATE TABLE events (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED NOT NULL,
  card_id    INT UNSIGNED NULL,
  link_id    INT UNSIGNED NULL,
  type       ENUM('view','tap','vcard','link','lead') NOT NULL,
  visitor    CHAR(16) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_events_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
  INDEX idx_events_profile (profile_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prospects : coordonnées laissées par un visiteur sur un profil, avec consentement (Loi 25)
CREATE TABLE leads (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id  INT UNSIGNED NOT NULL,
  card_id     INT UNSIGNED NULL,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL DEFAULT '',
  phone       VARCHAR(40)  NOT NULL DEFAULT '',
  company     VARCHAR(120) NOT NULL DEFAULT '',
  message     VARCHAR(1000) NOT NULL DEFAULT '',
  consent_text VARCHAR(500) NOT NULL,
  consent_at  DATETIME NOT NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL,
  CONSTRAINT fk_leads_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
  INDEX idx_leads_profile (profile_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key_hash   CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_attempts (key_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
