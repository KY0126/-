-- 01_create_database.sql
CREATE DATABASE IF NOT EXISTS workshop DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
USE workshop;

CREATE TABLE IF NOT EXISTS students (
  student_id VARCHAR(64) NOT NULL PRIMARY KEY,
  name VARCHAR(128),
  email VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(128),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  start_time DATETIME NOT NULL,
  end_time DATETIME,
  location_name VARCHAR(255),
  lat DECIMAL(10,7) DEFAULT NULL,
  lng DECIMAL(10,7) DEFAULT NULL,
  capacity INT NOT NULL DEFAULT 0,
  semester VARCHAR(32),
  hours FLOAT DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registrations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  activity_id INT NOT NULL,
  student_id VARCHAR(64) NOT NULL,
  status ENUM('registered','cancelled','checked_in') NOT NULL DEFAULT 'registered',
  qr_token VARCHAR(128) NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  checked_in_at DATETIME NULL,
  note TEXT,
  UNIQUE KEY uq_activity_student (activity_id, student_id),
  INDEX idx_activity_status (activity_id, status),
  INDEX idx_student (student_id),
  INDEX idx_qr_token (qr_token),
  FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  to_address VARCHAR(255),
  subject VARCHAR(255),
  body MEDIUMTEXT,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  result VARCHAR(64),
  raw_response TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;