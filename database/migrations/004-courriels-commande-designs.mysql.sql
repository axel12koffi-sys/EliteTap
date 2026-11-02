-- À exécuter dans phpMyAdmin (onglet SQL) sur une base créée avant cette version.

-- Réglages modifiables depuis l'admin (prix, taxes, courriel de notification…)
CREATE TABLE settings (
  name       VARCHAR(60) NOT NULL PRIMARY KEY,
  value      TEXT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journal des courriels envoyés (destinataire et sujet seulement, jamais le contenu)
CREATE TABLE mail_log (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email   VARCHAR(190) NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  status     VARCHAR(10)  NOT NULL,
  transport  VARCHAR(10)  NOT NULL,
  error      VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_mail_log_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galerie de designs (photos de réalisations)
CREATE TABLE designs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(120) NOT NULL DEFAULT '',
  material    VARCHAR(40)  NOT NULL DEFAULT 'Standard',
  image_path  VARCHAR(255) NOT NULL,
  thumb_path  VARCHAR(255) NOT NULL,
  position    INT NOT NULL DEFAULT 0,
  is_visible  TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parcours de commande : confirmation par courriel, mode de paiement, détail des taxes
ALTER TABLE orders
  ADD COLUMN confirm_token CHAR(32) NULL AFTER payment_ref,
  ADD COLUMN confirmed_at DATETIME NULL AFTER confirm_token,
  ADD COLUMN payment_method VARCHAR(20) NULL AFTER confirmed_at,
  ADD COLUMN subtotal_cents INT UNSIGNED NULL AFTER payment_method,
  ADD COLUMN tax_cents INT UNSIGNED NULL AFTER subtotal_cents;

-- Date de début du forfait payant (indicateur de rétention)
ALTER TABLE users ADD COLUMN plan_started_at DATETIME NULL AFTER plan_renews_at;
UPDATE users SET plan_started_at = created_at WHERE plan IN ('pro','entreprise');

-- Les commandes existantes restent accessibles par leur lien de confirmation
UPDATE orders SET confirm_token = MD5(CONCAT(reference, RAND(), NOW())) WHERE confirm_token IS NULL;
