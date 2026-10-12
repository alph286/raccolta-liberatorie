CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(60) NOT NULL,
  titolo VARCHAR(200) NOT NULL,
  testo MEDIUMTEXT NOT NULL,
  versione INT UNSIGNED NOT NULL DEFAULT 1,
  attiva TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  INDEX (slug, attiva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token CHAR(32) NOT NULL UNIQUE,
  template_id INT UNSIGNED NOT NULL,
  template_titolo VARCHAR(200) NOT NULL,
  template_versione INT UNSIGNED NOT NULL,
  testo_snapshot MEDIUMTEXT NOT NULL,
  testo_hash CHAR(64) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  cognome VARCHAR(100) NOT NULL,
  data_nascita DATE NOT NULL,
  luogo_nascita VARCHAR(120) NOT NULL,
  codice_fiscale VARCHAR(16) NULL,
  indirizzo VARCHAR(255) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefono VARCHAR(40) NULL,
  progetto VARCHAR(200) NULL,
  luogo_riprese VARCHAR(200) NULL,
  data_riprese DATE NULL,
  consenso_immagini TINYINT(1) NOT NULL,
  consenso_audio TINYINT(1) NOT NULL,
  consenso_diffusione TINYINT(1) NOT NULL,
  consenso_privacy TINYINT(1) NOT NULL,
  firma_file VARCHAR(80) NOT NULL,
  pdf_file VARCHAR(80) NOT NULL DEFAULT '',
  mail_inviata TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NOT NULL,
  operatore VARCHAR(60) NULL,
  user_agent VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX (cognome, nome),
  INDEX (created_at),
  INDEX (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
