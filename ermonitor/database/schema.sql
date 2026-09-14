CREATE DATABASE IF NOT EXISTS ermonitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ermonitor;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    username VARCHAR(40) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    os VARCHAR(120) DEFAULT NULL,
    status ENUM('online','warning','offline') NOT NULL DEFAULT 'offline',
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IMPORTANT: create the first administrator manually or promote your existing admin:
-- UPDATE users SET role='admin' WHERE username='YOUR_ADMIN_USERNAME';

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(80) NOT NULL,
    details VARCHAR(500) DEFAULT NULL,
    ip_address VARCHAR(64) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alert_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    server_id INT NULL,
    server_name VARCHAR(120) NOT NULL,
    previous_status VARCHAR(20) DEFAULT NULL,
    status VARCHAR(20) NOT NULL,
    event_type ENUM('triggered','recovered') NOT NULL DEFAULT 'triggered',
    message VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alert_created (created_at),
    KEY idx_alert_server (server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
