-- À exécuter UNIQUEMENT si la base a été créée avec une version antérieure du schéma
-- (avant l'ajout du paiement en ligne et de la version anglaise).
ALTER TABLE users  ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'fr' AFTER role;
ALTER TABLE orders ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'fr' AFTER admin_notes,
                   ADD COLUMN pay_token CHAR(32) NULL AFTER lang,
                   ADD COLUMN paid_at DATETIME NULL AFTER pay_token,
                   ADD COLUMN payment_ref VARCHAR(120) NULL AFTER paid_at;
