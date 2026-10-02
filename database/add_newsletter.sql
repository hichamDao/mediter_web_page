-- Migration : inscriptions à la newsletter (à importer une fois sur une base existante)
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  first_name VARCHAR(120) NOT NULL DEFAULT '',
  token CHAR(32) NOT NULL UNIQUE,             -- sert au lien de désinscription
  created_at DATETIME NOT NULL,
  unsubscribed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
