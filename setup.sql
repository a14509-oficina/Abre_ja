-- Corre este ficheiro no phpMyAdmin (separador SQL)
-- ou importa-o diretamente

CREATE DATABASE IF NOT EXISTS garagem
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE garagem;

CREATE TABLE IF NOT EXISTS users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(255) NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cars (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  plate      VARCHAR(8)  NOT NULL,
  brand      VARCHAR(100) NOT NULL,
  color      VARCHAR(20)  NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
