-- À exécuter une fois dans phpMyAdmin (onglet SQL) : messagerie, réponses automatiques, présence.

CREATE TABLE threads (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject         VARCHAR(255) NOT NULL DEFAULT '',
  contact_email   VARCHAR(190) NOT NULL,
  contact_name    VARCHAR(190) NOT NULL DEFAULT '',
  order_id        INT UNSIGNED NULL,
  user_id         INT UNSIGNED NULL,
  status          VARCHAR(10)  NOT NULL DEFAULT 'open',
  unread          INT UNSIGNED NOT NULL DEFAULT 0,
  lang            CHAR(2) NULL,
  last_message_at DATETIME NOT NULL,
  created_at      DATETIME NOT NULL,
  INDEX idx_threads_contact (contact_email),
  INDEX idx_threads_status (status, last_message_at),
  INDEX idx_threads_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id   INT UNSIGNED NOT NULL,
  direction   VARCHAR(3)   NOT NULL,
  kind        VARCHAR(10)  NOT NULL DEFAULT 'mail',
  from_email  VARCHAR(190) NOT NULL DEFAULT '',
  from_name   VARCHAR(190) NOT NULL DEFAULT '',
  to_email    VARCHAR(190) NOT NULL DEFAULT '',
  subject     VARCHAR(255) NOT NULL DEFAULT '',
  body        MEDIUMTEXT   NOT NULL,
  body_full   MEDIUMTEXT   NULL,
  message_id  VARCHAR(255) NULL,
  in_reply_to VARCHAR(255) NULL,
  attachments TEXT NULL,
  sent_by     INT UNSIGNED NULL,
  status      VARCHAR(10)  NOT NULL DEFAULT 'ok',
  created_at  DATETIME NOT NULL,
  INDEX idx_messages_thread (thread_id),
  INDEX idx_messages_mid (message_id(191)),
  INDEX idx_messages_to (to_email, created_at),
  CONSTRAINT fk_messages_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN last_seen_at DATETIME NULL AFTER last_login_at;
