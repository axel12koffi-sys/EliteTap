-- Équivalent SQLite, utilisé seulement pour le développement local et les tests.
PRAGMA foreign_keys = ON;

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  name TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client','admin','staff')),
  services TEXT NULL,
  lang TEXT NOT NULL DEFAULT 'fr',
  is_active INTEGER NOT NULL DEFAULT 1,
  plan TEXT NOT NULL DEFAULT 'essentiel',
  plan_source TEXT NOT NULL DEFAULT 'manual',
  plan_status TEXT NULL,
  plan_renews_at TEXT NULL,
  plan_started_at TEXT NULL,
  stripe_customer_id TEXT NULL,
  stripe_subscription_id TEXT NULL,
  reset_token_hash TEXT NULL,
  reset_expires_at TEXT NULL,
  last_login_at TEXT NULL,
  last_seen_at TEXT NULL,
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
  whatsapp TEXT NOT NULL DEFAULT '',
  address TEXT NOT NULL DEFAULT '',
  avatar_path TEXT NULL,
  logo_path TEXT NULL,
  theme TEXT NOT NULL DEFAULT 'marine',
  font TEXT NOT NULL DEFAULT 'moderne',
  lead_form INTEGER NOT NULL DEFAULT 1,
  lead_alerts INTEGER NOT NULL DEFAULT 1,
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
  lang TEXT NOT NULL DEFAULT 'fr',
  pay_token TEXT NULL,
  paid_at TEXT NULL,
  payment_ref TEXT NULL,
  confirm_token TEXT NULL,
  confirmed_at TEXT NULL,
  payment_method TEXT NULL,
  subtotal_cents INTEGER NULL,
  tax_cents INTEGER NULL,
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

CREATE TABLE leads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  profile_id INTEGER NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  card_id INTEGER NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL DEFAULT '',
  phone TEXT NOT NULL DEFAULT '',
  company TEXT NOT NULL DEFAULT '',
  message TEXT NOT NULL DEFAULT '',
  consent_text TEXT NOT NULL,
  consent_at TEXT NOT NULL,
  read_at TEXT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_leads_profile ON leads(profile_id, created_at);

CREATE TABLE settings (name TEXT PRIMARY KEY, value TEXT NULL, updated_at TEXT NOT NULL);
CREATE TABLE mail_log (id INTEGER PRIMARY KEY AUTOINCREMENT, to_email TEXT NOT NULL, subject TEXT NOT NULL, status TEXT NOT NULL, transport TEXT NOT NULL, error TEXT NULL, created_at TEXT NOT NULL);
CREATE TABLE designs (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL DEFAULT '', material TEXT NOT NULL DEFAULT 'Standard', image_path TEXT NOT NULL, thumb_path TEXT NOT NULL, position INTEGER NOT NULL DEFAULT 0, is_visible INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL);

CREATE TABLE login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  key_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE threads (id INTEGER PRIMARY KEY AUTOINCREMENT, subject TEXT NOT NULL DEFAULT '', contact_email TEXT NOT NULL, contact_name TEXT NOT NULL DEFAULT '', order_id INTEGER NULL, user_id INTEGER NULL, status TEXT NOT NULL DEFAULT 'open', unread INTEGER NOT NULL DEFAULT 0, lang TEXT NULL, last_message_at TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, thread_id INTEGER NOT NULL REFERENCES threads(id) ON DELETE CASCADE, direction TEXT NOT NULL, kind TEXT NOT NULL DEFAULT 'mail', from_email TEXT NOT NULL DEFAULT '', from_name TEXT NOT NULL DEFAULT '', to_email TEXT NOT NULL DEFAULT '', subject TEXT NOT NULL DEFAULT '', body TEXT NOT NULL, body_full TEXT NULL, message_id TEXT NULL, in_reply_to TEXT NULL, attachments TEXT NULL, sent_by INTEGER NULL, status TEXT NOT NULL DEFAULT 'ok', created_at TEXT NOT NULL);
CREATE INDEX idx_messages_thread ON messages(thread_id);
CREATE INDEX idx_messages_mid ON messages(message_id);
CREATE TABLE chat_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, token_hash TEXT NOT NULL UNIQUE, visitor_name TEXT NOT NULL DEFAULT '', visitor_email TEXT NULL, lang TEXT NOT NULL DEFAULT 'fr', page TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'waiting', assigned_to INTEGER NULL, thread_id INTEGER NULL, agent_last_read INTEGER NOT NULL DEFAULT 0, rating INTEGER NULL, first_reply_at TEXT NULL, last_message_at TEXT NOT NULL, last_visitor_at TEXT NOT NULL, closed_at TEXT NULL, closed_by TEXT NULL, created_at TEXT NOT NULL);
CREATE TABLE chat_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER NOT NULL REFERENCES chat_sessions(id) ON DELETE CASCADE, sender TEXT NOT NULL, user_id INTEGER NULL, body TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE TABLE newsletter_subscribers (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, name TEXT NOT NULL DEFAULT '', lang TEXT NOT NULL DEFAULT 'fr', source TEXT NOT NULL DEFAULT 'site', status TEXT NOT NULL DEFAULT 'pending', consent_text TEXT NOT NULL, consented_at TEXT NOT NULL, confirmed_at TEXT NULL, unsubscribed_at TEXT NULL, confirm_token TEXT NOT NULL, unsub_token TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE TABLE team_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, channel TEXT NOT NULL, user_id INTEGER NOT NULL, body TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE TABLE team_reads (user_id INTEGER NOT NULL, channel TEXT NOT NULL, last_read_id INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (user_id, channel));
