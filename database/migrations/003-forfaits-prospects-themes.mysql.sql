-- À exécuter dans phpMyAdmin (onglet SQL) sur une base créée avant cette version.
ALTER TABLE users
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER lang,
  ADD COLUMN plan ENUM('essentiel','pro','entreprise') NOT NULL DEFAULT 'essentiel' AFTER is_active,
  ADD COLUMN plan_source ENUM('manual','stripe') NOT NULL DEFAULT 'manual' AFTER plan,
  ADD COLUMN plan_status VARCHAR(20) NULL AFTER plan_source,
  ADD COLUMN plan_renews_at DATETIME NULL AFTER plan_status,
  ADD COLUMN stripe_customer_id VARCHAR(64) NULL AFTER plan_renews_at,
  ADD COLUMN stripe_subscription_id VARCHAR(64) NULL AFTER stripe_customer_id;

ALTER TABLE profiles
  ADD COLUMN whatsapp VARCHAR(40) NOT NULL DEFAULT '' AFTER website,
  ADD COLUMN logo_path VARCHAR(255) NULL AFTER avatar_path,
  ADD COLUMN theme VARCHAR(20) NOT NULL DEFAULT 'marine' AFTER logo_path,
  ADD COLUMN font VARCHAR(12) NOT NULL DEFAULT 'moderne' AFTER theme,
  ADD COLUMN lead_form TINYINT(1) NOT NULL DEFAULT 1 AFTER font,
  ADD COLUMN lead_alerts TINYINT(1) NOT NULL DEFAULT 1 AFTER lead_form;

ALTER TABLE events MODIFY type ENUM('view','tap','vcard','link','lead') NOT NULL;

-- Les profils existants gardent leurs couleurs actuelles
UPDATE profiles SET theme = 'custom';

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
