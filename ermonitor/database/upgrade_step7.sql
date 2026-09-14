USE ermonitor;

-- Step 7 migration for EXISTING installations.
-- Deliberately no foreign keys here: existing Step 6 databases may use
-- different signed/unsigned integer definitions. The new event tables keep
-- nullable IDs and remain safe if a user/server is later removed.

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
