-- À exécuter une fois dans phpMyAdmin (onglet SQL) : suivi des livraisons.

CREATE TABLE shipments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id        INT UNSIGNED NOT NULL,
  token           CHAR(32) NOT NULL UNIQUE,
  carrier         VARCHAR(30) NOT NULL DEFAULT 'postes_canada',
  tracking_number VARCHAR(80) NULL,
  tracking_url    VARCHAR(500) NULL,
  status          VARCHAR(12) NOT NULL DEFAULT 'a_preparer',
  recipient       VARCHAR(120) NOT NULL DEFAULT '',
  address         VARCHAR(255) NOT NULL DEFAULT '',
  city            VARCHAR(100) NOT NULL DEFAULT '',
  province        VARCHAR(30) NOT NULL DEFAULT 'QC',
  postal_code     VARCHAR(12) NOT NULL DEFAULT '',
  phone           VARCHAR(40) NOT NULL DEFAULT '',
  packages        INT UNSIGNED NOT NULL DEFAULT 1,
  estimated_at    DATE NULL,
  shipped_at      DATETIME NULL,
  delivered_at    DATETIME NULL,
  notes           TEXT NULL,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL,
  INDEX idx_ship_order (order_id),
  INDEX idx_ship_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipment_events (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT UNSIGNED NOT NULL,
  status      VARCHAR(12) NOT NULL,
  note        VARCHAR(500) NOT NULL DEFAULT '',
  notified    TINYINT(1) NOT NULL DEFAULT 0,
  user_id     INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL,
  INDEX idx_shipev (shipment_id, id),
  CONSTRAINT fk_shipev FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
