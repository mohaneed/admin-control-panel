SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS admin_direct_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS admin_roles;
DROP TABLE IF EXISTS step_up_grants;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_passwords;
DROP TABLE IF EXISTS admin_emails;
DROP TABLE IF EXISTS admin_remember_me_tokens;
DROP TABLE IF EXISTS admin_totp_secrets;

DROP TABLE IF EXISTS verification_codes;
DROP TABLE IF EXISTS admin_notification_preferences;
DROP TABLE IF EXISTS admin_notification_channels;
DROP TABLE IF EXISTS admin_notifications;
DROP TABLE IF EXISTS failed_notifications;

DROP TABLE IF EXISTS audit_outbox;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS security_events;
DROP TABLE IF EXISTS telemetry_traces;
DROP TABLE IF EXISTS activity_logs;

DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS telegram_queue;

DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS system_ownership;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS system_state;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE admins (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_ownership (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  admin_id INT NOT NULL UNIQUE,
                                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  CONSTRAINT fk_so_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_emails (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              admin_id INT NOT NULL,
                              email_blind_index CHAR(64) NOT NULL,
                              email_ciphertext VARBINARY(512) NOT NULL,
                              email_iv VARBINARY(16) NOT NULL,
                              email_tag VARBINARY(16) NOT NULL,
                              email_key_id VARCHAR(64) NOT NULL,
                              verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                              verified_at DATETIME NULL,
                              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                              UNIQUE KEY uq_admin_email_blind_index (email_blind_index),
                              CONSTRAINT fk_ae_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_passwords (
                                 admin_id INT PRIMARY KEY,
                                 password_hash VARCHAR(255) NOT NULL,
                                 pepper_id VARCHAR(16) NOT NULL,
                                 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                 CONSTRAINT fk_ap_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_sessions (
                                session_id VARCHAR(64) PRIMARY KEY,
                                admin_id INT NOT NULL,
                                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                expires_at DATETIME NOT NULL,
                                is_revoked TINYINT(1) NOT NULL DEFAULT 0,
                                CONSTRAINT fk_as_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_totp_secrets (
                                    admin_id INT NOT NULL,

                                    seed_ciphertext VARBINARY(512) NOT NULL,
                                    seed_iv VARBINARY(16) NOT NULL,
                                    seed_tag VARBINARY(16) NOT NULL,
                                    seed_key_id VARCHAR(64) NOT NULL,

                                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    rotated_at TIMESTAMP NULL DEFAULT NULL,

                                    PRIMARY KEY (admin_id),
                                    CONSTRAINT fk_admin_totp_admin FOREIGN KEY (admin_id)
                                        REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Encrypted TOTP seeds for admins (CryptoContext::TOTP_SEED_V1)';

CREATE TABLE roles (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(64) NOT NULL UNIQUE,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
                             id INT AUTO_INCREMENT PRIMARY KEY,
                             name VARCHAR(64) NOT NULL UNIQUE,
                             created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_roles (
                             admin_id INT NOT NULL,
                             role_id INT NOT NULL,
                             PRIMARY KEY (admin_id, role_id),
                             CONSTRAINT fk_ar_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                             CONSTRAINT fk_ar_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
                                  role_id INT NOT NULL,
                                  permission_id INT NOT NULL,
                                  PRIMARY KEY (role_id, permission_id),
                                  CONSTRAINT fk_rp_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                                  CONSTRAINT fk_rp_permission_id FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE verification_codes (
                                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    identity_type ENUM('admin','user','customer') NOT NULL,
                                    identity_id VARCHAR(64) NOT NULL,
                                    purpose VARCHAR(64) NOT NULL,
                                    code_hash VARCHAR(64) NOT NULL,
                                    expires_at DATETIME NOT NULL,
                                    max_attempts INT UNSIGNED NOT NULL,
                                    attempts INT UNSIGNED NOT NULL DEFAULT 0,
                                    status ENUM('active','used','expired','revoked') NOT NULL DEFAULT 'active',
                                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    used_at DATETIME NULL,
                                    UNIQUE KEY uniq_verification_code_hash (code_hash),
                                    INDEX idx_active_lookup (identity_type, identity_id, purpose, status),
                                    INDEX idx_status_expiry (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notification_channels (
                                             id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                             admin_id INT NOT NULL,
                                             channel ENUM('email','telegram') NOT NULL,
                                             identifier VARCHAR(255) NOT NULL,
                                             status ENUM('pending','active','revoked') NOT NULL DEFAULT 'pending',
                                             created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                             verified_at DATETIME NULL,
                                             UNIQUE KEY uq_admin_channel (admin_id, channel),
                                             UNIQUE KEY uq_channel_identifier (channel, identifier),
                                             CONSTRAINT fk_anc_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notification_preferences (
                                                id INT AUTO_INCREMENT PRIMARY KEY,
                                                admin_id INT NOT NULL,
                                                notification_type VARCHAR(64) NOT NULL,
                                                channel_type VARCHAR(32) NOT NULL,
                                                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                                                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                UNIQUE KEY uq_admin_type_channel (admin_id, notification_type, channel_type),
                                                CONSTRAINT fk_anp_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_notifications (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     admin_id INT NOT NULL,
                                     notification_type VARCHAR(64) NOT NULL,
                                     channel_type VARCHAR(32) NOT NULL,
                                     intent_id VARCHAR(64) NULL,
                                     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     read_at DATETIME NULL,
                                     CONSTRAINT fk_an_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE security_events (
                                 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                 actor_type VARCHAR(32) NOT NULL,
                                 actor_id INT NULL,
                                 event_type VARCHAR(100) NOT NULL,
                                 severity VARCHAR(20) NOT NULL,
                                 request_id VARCHAR(64) NULL,
                                 route_name VARCHAR(255) NULL,
                                 ip_address VARCHAR(45) NULL,
                                 user_agent TEXT NULL,
                                 metadata JSON NOT NULL,
                                 occurred_at DATETIME NOT NULL,
                                 INDEX idx_security_actor (actor_type, actor_id),
                                 INDEX idx_security_event_type (event_type),
                                 INDEX idx_security_severity (severity),
                                 INDEX idx_security_request_id (request_id),
                                 INDEX idx_security_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
                               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                               actor_type VARCHAR(32) NOT NULL,
                               actor_id INT NULL,
                               action VARCHAR(128) NOT NULL,
                               entity_type VARCHAR(64) NULL,
                               entity_id INT NULL,
                               metadata JSON NULL,
                               ip_address VARCHAR(45) NULL,
                               user_agent VARCHAR(255) NULL,
                               request_id VARCHAR(64) NULL,
                               occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                               INDEX idx_actor (actor_type, actor_id),
                               INDEX idx_action (action),
                               INDEX idx_entity (entity_type, entity_id),
                               INDEX idx_occurred_at (occurred_at),
                               INDEX idx_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE telemetry_traces (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                  event_key VARCHAR(255) NOT NULL,
                                  severity VARCHAR(16) NOT NULL DEFAULT 'info',
                                  route_name VARCHAR(255) NULL,
                                  request_id VARCHAR(64) NULL,
                                  actor_type VARCHAR(32) NOT NULL,
                                  actor_id INT NULL,
                                  ip_address VARCHAR(45) NULL,
                                  user_agent TEXT NULL,
                                  metadata JSON NULL,
                                  occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                  INDEX idx_telemetry_actor (actor_type, actor_id),
                                  INDEX idx_telemetry_event_key (event_key),
                                  INDEX idx_telemetry_severity (severity),
                                  INDEX idx_telemetry_request (request_id),
                                  INDEX idx_telemetry_route (route_name),
                                  INDEX idx_telemetry_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
