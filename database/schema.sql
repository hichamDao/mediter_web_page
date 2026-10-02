-- Éveil Intérieur — schéma MySQL / MariaDB (utf8mb4). Toutes les dates sont en UTC.
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('member','admin') NOT NULL DEFAULT 'member',
  paid_at DATETIME NULL,                      -- renseigné uniquement après un paiement PayPal validé côté serveur
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  paypal_order_id VARCHAR(64) NOT NULL UNIQUE,
  paypal_capture_id VARCHAR(64) NULL UNIQUE,
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL,
  status ENUM('CREATED','COMPLETED','FAILED') NOT NULL DEFAULT 'CREATED',
  payer_email VARCHAR(190) NULL,
  created_at DATETIME NOT NULL,
  paid_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carnet d'adresses : chaque membre ne voit que ses propres contacts
CREATE TABLE IF NOT EXISTS address_book (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  phone VARCHAR(40) NOT NULL DEFAULT '',
  address VARCHAR(300) NOT NULL DEFAULT '',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carnet de notes de coaching (réservé aux membres ayant payé), une note par séance
CREATE TABLE IF NOT EXISTS coaching_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  session TINYINT UNSIGNED NOT NULL,          -- séance de coaching 1, 2 ou 3
  content MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_user_session (user_id, session),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT '',
  excerpt VARCHAR(300) NOT NULL DEFAULT '',
  content MEDIUMTEXT NOT NULL,                -- texte brut ; ligne vide = nouveau paragraphe ; **gras**
  image VARCHAR(190) NOT NULL DEFAULT '',
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  INDEX (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX (ip, email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inscriptions à la newsletter
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  first_name VARCHAR(120) NOT NULL DEFAULT '',
  token CHAR(32) NOT NULL UNIQUE,             -- sert au lien de désinscription
  created_at DATETIME NOT NULL,
  unsubscribed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
