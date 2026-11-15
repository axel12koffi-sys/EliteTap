-- À exécuter une fois dans phpMyAdmin (onglet SQL) : profil sur mesure (design de carte → HTML/CSS).

CREATE TABLE profile_designs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id    INT UNSIGNED NOT NULL,
  version       INT UNSIGNED NOT NULL DEFAULT 1,
  source_path   VARCHAR(255) NULL,
  source_name   VARCHAR(200) NULL,
  source_mime   VARCHAR(60) NULL,
  html          MEDIUMTEXT NULL,
  css           MEDIUMTEXT NULL,
  instruction   TEXT NULL,
  status        VARCHAR(10) NOT NULL DEFAULT 'draft',
  ai_used       TINYINT(1) NOT NULL DEFAULT 0,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL,
  updated_at    DATETIME NOT NULL,
  published_at  DATETIME NULL,
  INDEX idx_pdesign_profile (profile_id, id),
  CONSTRAINT fk_pdesign_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profile_design_assets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT UNSIGNED NOT NULL,
  name       VARCHAR(40) NOT NULL,
  path       VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_passet (profile_id, name),
  CONSTRAINT fk_passet_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE profiles
  ADD COLUMN custom_design_id INT UNSIGNED NULL AFTER theme,
  ADD COLUMN use_custom TINYINT(1) NOT NULL DEFAULT 1 AFTER custom_design_id;
