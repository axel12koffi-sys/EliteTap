-- À exécuter une fois dans phpMyAdmin (onglet SQL) : employés, clavardage du site, infolettre, clavardage interne.

-- Employés : rôle « staff » et services (service client, comptabilité)
ALTER TABLE users
  MODIFY role ENUM('client','admin','staff') NOT NULL DEFAULT 'client',
  ADD COLUMN services VARCHAR(100) NULL AFTER role;

-- Clavardage du site
CREATE TABLE chat_sessions (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_hash      CHAR(64) NOT NULL UNIQUE,
  visitor_name    VARCHAR(120) NOT NULL DEFAULT '',
  visitor_email   VARCHAR(190) NULL,
  lang            CHAR(2) NOT NULL DEFAULT 'fr',
  page            VARCHAR(255) NOT NULL DEFAULT '',
  status          VARCHAR(10) NOT NULL DEFAULT 'waiting',
  assigned_to     INT UNSIGNED NULL,
  thread_id       INT UNSIGNED NULL,
  agent_last_read BIGINT UNSIGNED NOT NULL DEFAULT 0,
  rating          TINYINT NULL,
  first_reply_at  DATETIME NULL,
  last_message_at DATETIME NOT NULL,
  last_visitor_at DATETIME NOT NULL,
  closed_at       DATETIME NULL,
  closed_by       VARCHAR(10) NULL,
  created_at      DATETIME NOT NULL,
  INDEX idx_chat_status (status, last_message_at),
  INDEX idx_chat_email (visitor_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  sender     VARCHAR(10) NOT NULL,
  user_id    INT UNSIGNED NULL,
  body       TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_chatmsg_session (session_id, id),
  CONSTRAINT fk_chatmsg_session FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Infolettre (double confirmation, consentement conservé : LCAP / Loi 25)
CREATE TABLE newsletter_subscribers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL DEFAULT '',
  lang          CHAR(2) NOT NULL DEFAULT 'fr',
  source        VARCHAR(20) NOT NULL DEFAULT 'site',
  status        VARCHAR(12) NOT NULL DEFAULT 'pending',
  consent_text  VARCHAR(500) NOT NULL,
  consented_at  DATETIME NOT NULL,
  confirmed_at  DATETIME NULL,
  unsubscribed_at DATETIME NULL,
  confirm_token CHAR(32) NOT NULL,
  unsub_token   CHAR(32) NOT NULL,
  created_at    DATETIME NOT NULL,
  INDEX idx_news_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clavardage interne de l'équipe (canaux et messages directs)
CREATE TABLE team_messages (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel    VARCHAR(40) NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_team_channel (channel, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_reads (
  user_id      INT UNSIGNED NOT NULL,
  channel      VARCHAR(40) NOT NULL,
  last_read_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
