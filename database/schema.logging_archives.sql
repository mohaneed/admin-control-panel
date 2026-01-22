/* ===========================
 * LOGGING ARCHIVES (OPTIONAL) - SEPARATE FILE
 * File: database/schema.logging_archives.sql
 * Purpose:
 * - Optional archive tables (MySQL -> MySQL) for all logging domains.
 * - Keeps baseline schema clean and allows flipping decision later.
 *
 * Compatibility: MySQL 5.7+ (No MySQL 8-only features)
 * Notes:
 * - Archive tables mirror hot tables columns + indexes.
 * - No foreign keys (logs must not block on entity lifecycle).
 * - JSON columns MUST NOT store secrets (tokens/OTPs/passwords).
 * =========================== */


-- ==========================================================
-- 1) Authoritative Audit Archives
-- ==========================================================

CREATE TABLE authoritative_audit_outbox_archive (
                                                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                                    event_id CHAR(36) NOT NULL,

                                                    actor_type VARCHAR(32) NOT NULL,
                                                    actor_id BIGINT NULL,

                                                    action VARCHAR(128) NOT NULL,
                                                    target_type VARCHAR(64) NOT NULL,
                                                    target_id BIGINT NULL,

                                                    risk_level VARCHAR(16) NOT NULL,
                                                    payload JSON NOT NULL,

                                                    correlation_id CHAR(36) NOT NULL,
                                                    created_at DATETIME(6) NOT NULL,

                                                    UNIQUE KEY uq_auth_audit_outbox_archive_event_id (event_id),

                                                    INDEX idx_auth_audit_outbox_archive_time (created_at, id),
                                                    INDEX idx_auth_audit_outbox_archive_actor_time (actor_type, actor_id, created_at),
                                                    INDEX idx_auth_audit_outbox_archive_target_time (target_type, target_id, created_at),
                                                    INDEX idx_auth_audit_outbox_archive_correlation_time (correlation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): authoritative audit outbox. Mirror of authoritative_audit_outbox. Payload MUST NOT contain secrets.';


CREATE TABLE authoritative_audit_log_archive (
                                                 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                                 event_id CHAR(36) NOT NULL,

                                                 actor_type VARCHAR(32) NOT NULL,
                                                 actor_id BIGINT NULL,

                                                 action VARCHAR(128) NOT NULL,
                                                 target_type VARCHAR(64) NOT NULL,
                                                 target_id BIGINT NULL,

                                                 changes JSON NULL,

                                                 ip_address VARCHAR(45) NULL,
                                                 user_agent VARCHAR(512) NULL,

                                                 correlation_id CHAR(36) NULL,
                                                 occurred_at DATETIME(6) NOT NULL,

                                                 UNIQUE KEY uq_auth_audit_log_archive_event_id (event_id),

                                                 INDEX idx_auth_audit_log_archive_time (occurred_at, id),
                                                 INDEX idx_auth_audit_log_archive_actor_time (actor_type, actor_id, occurred_at),
                                                 INDEX idx_auth_audit_log_archive_target_time (target_type, target_id, occurred_at),
                                                 INDEX idx_auth_audit_log_archive_correlation_time (correlation_id, occurred_at),
                                                 INDEX idx_auth_audit_log_archive_action_time (action, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): materialized authoritative audit log. Mirror of authoritative_audit_log. No secrets in changes.';


-- ==========================================================
-- 2) Audit Trail Archive
-- ==========================================================

CREATE TABLE audit_trail_archive (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                     event_id CHAR(36) NOT NULL,

                                     actor_type VARCHAR(32) NOT NULL,
                                     actor_id BIGINT NULL,

                                     event_key VARCHAR(255) NOT NULL,

                                     entity_type VARCHAR(64) NOT NULL,
                                     entity_id BIGINT NULL,

                                     subject_type VARCHAR(64) NULL,
                                     subject_id BIGINT NULL,

                                     referrer_route_name VARCHAR(255) NULL,
                                     referrer_path VARCHAR(1024) NULL,
                                     referrer_host VARCHAR(255) NULL,

                                     correlation_id CHAR(36) NULL,
                                     request_id VARCHAR(64) NULL,
                                     route_name VARCHAR(255) NULL,

                                     ip_address VARCHAR(45) NULL,
                                     user_agent VARCHAR(512) NULL,

                                     metadata JSON NOT NULL,
                                     occurred_at DATETIME(6) NOT NULL,

                                     UNIQUE KEY uq_audit_trail_archive_event_id (event_id),

                                     INDEX idx_audit_trail_archive_time (occurred_at, id),
                                     INDEX idx_audit_trail_archive_actor_time (actor_type, actor_id, occurred_at),
                                     INDEX idx_audit_trail_archive_event_time (event_key, occurred_at),
                                     INDEX idx_audit_trail_archive_entity_time (entity_type, entity_id, occurred_at),
                                     INDEX idx_audit_trail_archive_subject_time (subject_type, subject_id, occurred_at),
                                     INDEX idx_audit_trail_archive_correlation_time (correlation_id, occurred_at),
                                     INDEX idx_audit_trail_archive_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): audit trail for data exposure/navigation/views/exports. Mirror of audit_trail. Store sanitized referrer_path only.';


-- ==========================================================
-- 3) Security Signals Archive
-- ==========================================================

CREATE TABLE security_signals_archive (
                                          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                          event_id CHAR(36) NOT NULL,

                                          actor_type VARCHAR(32) NOT NULL,
                                          actor_id BIGINT NULL,

                                          signal_type VARCHAR(100) NOT NULL,
                                          severity VARCHAR(16) NOT NULL,

                                          correlation_id CHAR(36) NULL,
                                          request_id VARCHAR(64) NULL,
                                          route_name VARCHAR(255) NULL,

                                          ip_address VARCHAR(45) NULL,
                                          user_agent VARCHAR(512) NULL,

                                          metadata JSON NOT NULL,
                                          occurred_at DATETIME(6) NOT NULL,

                                          UNIQUE KEY uq_security_signals_archive_event_id (event_id),

                                          INDEX idx_security_signals_archive_time (occurred_at, id),
                                          INDEX idx_security_signals_archive_actor_time (actor_type, actor_id, occurred_at),
                                          INDEX idx_security_signals_archive_type_time (signal_type, occurred_at),
                                          INDEX idx_security_signals_archive_severity_time (severity, occurred_at),
                                          INDEX idx_security_signals_archive_correlation_time (correlation_id, occurred_at),
                                          INDEX idx_security_signals_archive_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): security signals for detection/alerting. Mirror of security_signals. Best-effort; no secrets in metadata.';


-- ==========================================================
-- 4) Operational Activity Archive
-- ==========================================================

CREATE TABLE operational_activity_archive (
                                              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                              event_id CHAR(36) NOT NULL,

                                              actor_type VARCHAR(32) NOT NULL,
                                              actor_id BIGINT NULL,

                                              action VARCHAR(128) NOT NULL,
                                              entity_type VARCHAR(64) NULL,
                                              entity_id BIGINT NULL,

                                              metadata JSON NOT NULL,

                                              correlation_id CHAR(36) NULL,
                                              request_id VARCHAR(64) NULL,
                                              route_name VARCHAR(255) NULL,

                                              ip_address VARCHAR(45) NULL,
                                              user_agent VARCHAR(512) NULL,

                                              occurred_at DATETIME(6) NOT NULL,

                                              UNIQUE KEY uq_operational_activity_archive_event_id (event_id),

                                              INDEX idx_operational_activity_archive_time (occurred_at, id),
                                              INDEX idx_operational_activity_archive_actor_time (actor_type, actor_id, occurred_at),
                                              INDEX idx_operational_activity_archive_action_time (action, occurred_at),
                                              INDEX idx_operational_activity_archive_entity_time (entity_type, entity_id, occurred_at),
                                              INDEX idx_operational_activity_archive_correlation_time (correlation_id, occurred_at),
                                              INDEX idx_operational_activity_archive_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): operational activity for mutations only. Mirror of operational_activity. Views/reads are NOT allowed (use audit_trail).';


-- ==========================================================
-- 5) Diagnostics Telemetry Archive
-- ==========================================================

CREATE TABLE diagnostics_telemetry_archive (
                                               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                               event_id CHAR(36) NOT NULL,

                                               event_key VARCHAR(255) NOT NULL,
                                               severity VARCHAR(16) NOT NULL DEFAULT 'INFO',

                                               actor_type VARCHAR(32) NOT NULL,
                                               actor_id BIGINT NULL,

                                               correlation_id CHAR(36) NULL,
                                               request_id VARCHAR(64) NULL,
                                               route_name VARCHAR(255) NULL,

                                               ip_address VARCHAR(45) NULL,
                                               user_agent VARCHAR(512) NULL,

                                               duration_ms INT UNSIGNED NULL,
                                               metadata JSON NULL,

                                               occurred_at DATETIME(6) NOT NULL,

                                               UNIQUE KEY uq_diagnostics_telemetry_archive_event_id (event_id),

                                               INDEX idx_diag_telemetry_archive_time (occurred_at, id),
                                               INDEX idx_diag_telemetry_archive_actor_time (actor_type, actor_id, occurred_at),
                                               INDEX idx_diag_telemetry_archive_event_time (event_key, occurred_at),
                                               INDEX idx_diag_telemetry_archive_severity_time (severity, occurred_at),
                                               INDEX idx_diag_telemetry_archive_correlation_time (correlation_id, occurred_at),
                                               INDEX idx_diag_telemetry_archive_request_time (request_id, occurred_at),
                                               INDEX idx_diag_telemetry_archive_route_time (route_name, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): diagnostics telemetry for tracing/performance/technical errors. Mirror of diagnostics_telemetry. Avoid PII; no secrets in metadata.';


-- ==========================================================
-- 6) Delivery Operations Archive
-- ==========================================================

CREATE TABLE delivery_operations_archive (
                                             id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                             event_id CHAR(36) NOT NULL,

                                             channel VARCHAR(32) NOT NULL,
                                             operation_type VARCHAR(64) NOT NULL,

                                             actor_type VARCHAR(32) NULL,
                                             actor_id BIGINT NULL,

                                             target_type VARCHAR(64) NULL,
                                             target_id BIGINT NULL,

                                             status VARCHAR(32) NOT NULL,
                                             attempt_no INT UNSIGNED NOT NULL DEFAULT 0,

                                             scheduled_at DATETIME(6) NULL,
                                             completed_at DATETIME(6) NULL,

                                             correlation_id CHAR(36) NULL,
                                             request_id VARCHAR(64) NULL,

                                             provider VARCHAR(64) NULL,
                                             provider_message_id VARCHAR(128) NULL,

                                             error_code VARCHAR(64) NULL,
                                             error_message TEXT NULL,

                                             metadata JSON NOT NULL,
                                             occurred_at DATETIME(6) NOT NULL,

                                             UNIQUE KEY uq_delivery_operations_archive_event_id (event_id),

                                             INDEX idx_delivery_ops_archive_time (occurred_at, id),
                                             INDEX idx_delivery_ops_archive_actor_time (actor_type, actor_id, occurred_at),
                                             INDEX idx_delivery_ops_archive_channel_time (channel, occurred_at),
                                             INDEX idx_delivery_ops_archive_type_time (operation_type, occurred_at),
                                             INDEX idx_delivery_ops_archive_status_time (status, occurred_at),
                                             INDEX idx_delivery_ops_archive_target_time (target_type, target_id, occurred_at),
                                             INDEX idx_delivery_ops_archive_correlation_time (correlation_id, occurred_at),
                                             INDEX idx_delivery_ops_archive_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='ARCHIVE (optional): delivery/system operations for notifications, queues, jobs, webhooks. Mirror of delivery_operations. Track retries & providers. No secrets in metadata.';
