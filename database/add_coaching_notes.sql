-- Migration : carnet de notes de coaching (à importer une fois sur une base existante)
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
