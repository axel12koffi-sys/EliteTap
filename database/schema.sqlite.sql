-- Équivalent SQLite, utilisé seulement pour le développement local et les tests.
PRAGMA foreign_keys = ON;

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  name TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client','admin')),
  reset_token_hash TEXT NULL,
  reset_expires_at TEXT NULL,
  last_login_at TEXT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE profiles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  slug TEXT NOT NULL UNIQUE,
  first_name TEXT NOT NULL DEFAULT '',
  last_name TEXT NOT NULL DEFAULT '',
  title TEXT NOT NULL DEFAULT '',
  company TEXT NOT NULL DEFAULT '',
  bio TEXT NOT NULL DEFAULT '',
  phone TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL DEFAULT '',
  website TEXT NOT NULL DEFAULT '',
  address TEXT NOT NULL DEFAULT '',
  avatar_path TEXT NULL,
  cover_color TEXT NOT NULL DEFAULT '#14161A',
  accent_color TEXT NOT NULL DEFAULT '#2563EB',
  is_public INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE links (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  profile_id INTEGER NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  label TEXT NOT NULL,
  url TEXT NOT NULL,
  position INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL
);

CREATE TABLE orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reference TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  company TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL,
  phone TEXT NOT NULL DEFAULT '',
  card_type TEXT NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  message TEXT NULL,
  file_path TEXT NULL,
  file_name TEXT NULL,
  status TEXT NOT NULL DEFAULT 'devis',
  amount_cents INTEGER NULL,
  user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
  admin_notes TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE cards (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  nfc_uid TEXT NULL,
  material TEXT NOT NULL DEFAULT 'Standard',
  profile_id INTEGER NULL REFERENCES profiles(id) ON DELETE SET NULL,
  order_id INTEGER NULL REFERENCES orders(id) ON DELETE SET NULL,
  status TEXT NOT NULL DEFAULT 'stock',
  activated_at TEXT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  profile_id INTEGER NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  card_id INTEGER NULL,
  link_id INTEGER NULL,
  type TEXT NOT NULL,
  visitor TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_events_profile ON events(profile_id, created_at);

CREATE TABLE login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  key_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);
