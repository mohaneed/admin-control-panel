
/* ===========================
 * LOGGING DOMAINS (CANONICAL) - FIRST-TIME BASELINE (NO ARCHIVE)
 * Compatibility: MySQL 5.7+ (No MySQL 8-only features)
 * Notes:
 * - All severities / risk levels are VARCHAR for max portability.
 * - Cursor index (occurred_at, id) exists on all tables to enable stable paging & future archiving.
 * - JSON columns MUST NOT store secrets (tokens/OTPs/passwords).
 * =========================== */


-- ==========================================================
-- 1) Authoritative Audit (COMPLIANCE-GRADE / FAIL-CLOSED)
-- ----------------------------------------------------------
-- Source of truth is the OUTBOX (transactional).
-- The LOG is a materialized view written ONLY by a consumer.
-- ==========================================================

CREATE TABLE authoritative_audit_outbox (
                                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID string for idempotency / tracing across systems (portable, no BINARY dependency)
                                            event_id CHAR(36) NOT NULL,

    -- Who caused the event (ADMIN / SYSTEM / SERVICE ...)
                                            actor_type VARCHAR(32) NOT NULL,
                                            actor_id BIGINT NULL,

    -- What happened (authoritative state change)
                                            action VARCHAR(128) NOT NULL,

    -- What was affected
                                            target_type VARCHAR(64) NOT NULL,
                                            target_id BIGINT NULL,

    -- Risk level (portable VARCHAR; values should be controlled at app-level)
    -- Recommended values: LOW|MEDIUM|HIGH|CRITICAL
                                            risk_level VARCHAR(16) NOT NULL,

    -- Authoritative event payload (MUST NOT contain secrets)
                                            payload JSON NOT NULL,

    -- Correlation between different log domains and request pipeline
                                            correlation_id CHAR(36) NOT NULL,

    -- Outbox enqueue timestamp (transactional)
                                            created_at DATETIME(6) NOT NULL,

    -- Idempotency: same event MUST NOT be written twice
                                            UNIQUE KEY uq_auth_audit_outbox_event_id (event_id),

    -- Cursor index for stable paging / future batch processing (no partitioning assumptions)
                                            INDEX idx_auth_audit_outbox_time (created_at, id),

    -- Common investigations
                                            INDEX idx_auth_audit_outbox_actor_time (actor_type, actor_id, created_at),
                                            INDEX idx_auth_audit_outbox_target_time (target_type, target_id, created_at),
                                            INDEX idx_auth_audit_outbox_correlation_time (correlation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Authoritative audit outbox (source of truth). Transactional + fail-closed. Written ONLY inside business transactions. Payload MUST NOT contain secrets (tokens/OTPs/passwords).';


CREATE TABLE authoritative_audit_log (
                                         id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Same event_id from outbox (idempotency & cross-reference)
                                         event_id CHAR(36) NOT NULL,

    -- Who caused the event
                                         actor_type VARCHAR(32) NOT NULL,
                                         actor_id BIGINT NULL,

    -- What happened (authoritative state change)
                                         action VARCHAR(128) NOT NULL,

    -- What was affected
                                         target_type VARCHAR(64) NOT NULL,
                                         target_id BIGINT NULL,

    -- Optional structured changes (MUST remain minimal; never store secrets)
                                         changes JSON NULL,

    -- Request context
                                         ip_address VARCHAR(45) NULL,
                                         user_agent VARCHAR(512) NULL,

    -- Correlation to other logs / traces
                                         correlation_id CHAR(36) NULL,

    -- Materialized timestamp (usually equals business occurred time)
                                         occurred_at DATETIME(6) NOT NULL,

                                         UNIQUE KEY uq_auth_audit_log_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                         INDEX idx_auth_audit_log_time (occurred_at, id),

    -- Common investigations
                                         INDEX idx_auth_audit_log_actor_time (actor_type, actor_id, occurred_at),
                                         INDEX idx_auth_audit_log_target_time (target_type, target_id, occurred_at),
                                         INDEX idx_auth_audit_log_correlation_time (correlation_id, occurred_at),
                                         INDEX idx_auth_audit_log_action_time (action, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Materialized authoritative audit log. MUST be written ONLY by outbox consumer. Not authoritative. Changes must be structured & minimal; no secrets.';


-- ==========================================================
-- 2) Audit Trail (DATA ACCESS / VIEWS / NAVIGATION / EXPORTS)
-- ----------------------------------------------------------
-- Answers: "Who accessed what sensitive thing, when?"
-- This is NOT authoritative state change.
-- ==========================================================

CREATE TABLE audit_trail (
                             id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID per row/event (portable)
                             event_id CHAR(36) NOT NULL,

    -- Who performed the access
                             actor_type VARCHAR(32) NOT NULL,
                             actor_id BIGINT NULL,

    -- Examples: customer.view, customer.export, page.visit, invoice.download
                             event_key VARCHAR(255) NOT NULL,

    -- Accessed resource (what was touched)
                             entity_type VARCHAR(64) NOT NULL,
                             entity_id BIGINT NULL,

    -- "On behalf of" / data subject (e.g., the customer whose data was viewed)
                             subject_type VARCHAR(64) NULL,
                             subject_id BIGINT NULL,

    -- Navigation context (store sanitized values; DO NOT store sensitive query strings)
                             referrer_route_name VARCHAR(255) NULL,
                             referrer_path VARCHAR(1024) NULL,  -- recommended: path only, no query
                             referrer_host VARCHAR(255) NULL,   -- optional

    -- Correlation to request pipeline
                             correlation_id CHAR(36) NULL,
                             request_id VARCHAR(64) NULL,
                             route_name VARCHAR(255) NULL,

    -- Request context
                             ip_address VARCHAR(45) NULL,
                             user_agent VARCHAR(512) NULL,

    -- Extra metadata (MUST NOT contain secrets)
                             metadata JSON NOT NULL,

                             occurred_at DATETIME(6) NOT NULL,

                             UNIQUE KEY uq_audit_trail_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                             INDEX idx_audit_trail_time (occurred_at, id),

    -- Common investigations (two required search dimensions: actor+time and key+time)
                             INDEX idx_audit_trail_actor_time (actor_type, actor_id, occurred_at),
                             INDEX idx_audit_trail_event_time (event_key, occurred_at),

    -- Deep investigations
                             INDEX idx_audit_trail_entity_time (entity_type, entity_id, occurred_at),
                             INDEX idx_audit_trail_subject_time (subject_type, subject_id, occurred_at),

    -- Correlation helpers
                             INDEX idx_audit_trail_correlation_time (correlation_id, occurred_at),
                             INDEX idx_audit_trail_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Audit Trail: records data exposure/navigation/views/exports. Searchable by actor+time and event_key+time. Store sanitized referrer_path (no tokens/OTP/query secrets).';


-- ==========================================================
-- 3) Security Signals (NON-AUTH / BEST-EFFORT)
-- ----------------------------------------------------------
-- Answers: "What security-relevant signals happened?"
-- MUST NOT affect control-flow. MUST tolerate failure.
-- ==========================================================

CREATE TABLE security_signals (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- UUID per signal/event (portable)
                                  event_id CHAR(36) NOT NULL,

    -- Actor involved (can be anonymous for pre-auth signals)
                                  actor_type VARCHAR(32) NOT NULL,
                                  actor_id BIGINT NULL,

    -- Examples: login_failed, permission_denied, session_invalid...
                                  signal_type VARCHAR(100) NOT NULL,

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                  severity VARCHAR(16) NOT NULL,

    -- Correlation to request pipeline
                                  correlation_id CHAR(36) NULL,
                                  request_id VARCHAR(64) NULL,
                                  route_name VARCHAR(255) NULL,

    -- Request context
                                  ip_address VARCHAR(45) NULL,
                                  user_agent VARCHAR(512) NULL,

    -- Additional info (MUST NOT contain secrets)
                                  metadata JSON NOT NULL,

                                  occurred_at DATETIME(6) NOT NULL,

                                  UNIQUE KEY uq_security_signals_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                  INDEX idx_security_signals_time (occurred_at, id),

    -- Required search dimensions
                                  INDEX idx_security_signals_actor_time (actor_type, actor_id, occurred_at),
                                  INDEX idx_security_signals_type_time (signal_type, occurred_at),

    -- Dashboards / alerting
                                  INDEX idx_security_signals_severity_time (severity, occurred_at),

    -- Correlation helpers
                                  INDEX idx_security_signals_correlation_time (correlation_id, occurred_at),
                                  INDEX idx_security_signals_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Security signals for detection/alerting (non-authoritative). Best-effort; MUST NOT block user actions. Metadata MUST NOT contain secrets (passwords, OTP codes, tokens).';


-- ==========================================================
-- 4) Operational Activity (MUTATIONS ONLY)
-- ----------------------------------------------------------
-- Answers: "What operational changes did the actor do?"
-- Strictly mutations (create/update/delete). No reads/views here.
-- ==========================================================

CREATE TABLE operational_activity (
                                      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                      event_id CHAR(36) NOT NULL,

                                      actor_type VARCHAR(32) NOT NULL,
                                      actor_id BIGINT NULL,

    -- Examples: create/update/delete/bulk_action/report_run...
                                      action VARCHAR(128) NOT NULL,

    -- Affected entity (optional)
                                      entity_type VARCHAR(64) NULL,
                                      entity_id BIGINT NULL,

    -- Metadata about the operation (MUST NOT contain secrets)
                                      metadata JSON NOT NULL,

                                      correlation_id CHAR(36) NULL,
                                      request_id VARCHAR(64) NULL,
                                      route_name VARCHAR(255) NULL,

                                      ip_address VARCHAR(45) NULL,
                                      user_agent VARCHAR(512) NULL,

                                      occurred_at DATETIME(6) NOT NULL,

                                      UNIQUE KEY uq_operational_activity_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                      INDEX idx_operational_activity_time (occurred_at, id),

    -- Required search dimensions
                                      INDEX idx_operational_activity_actor_time (actor_type, actor_id, occurred_at),
                                      INDEX idx_operational_activity_action_time (action, occurred_at),

    -- Deep investigations
                                      INDEX idx_operational_activity_entity_time (entity_type, entity_id, occurred_at),

    -- Correlation helpers
                                      INDEX idx_operational_activity_correlation_time (correlation_id, occurred_at),
                                      INDEX idx_operational_activity_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Operational activity for mutations only. Searchable by actor+time and action+time. Views/reads are NOT allowed (use audit_trail).';


-- ==========================================================
-- 5) Diagnostics Telemetry (TECHNICAL / NON-BUSINESS)
-- ----------------------------------------------------------
-- Answers: "How did the system behave?" (performance/diagnostics).
-- Non-business, best-effort, no compliance meaning.
-- ==========================================================

CREATE TABLE diagnostics_telemetry (
                                       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                       event_id CHAR(36) NOT NULL,

    -- Examples: http.request, db.query, exception, cache.miss...
                                       event_key VARCHAR(255) NOT NULL,

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                       severity VARCHAR(16) NOT NULL DEFAULT 'INFO',

                                       actor_type VARCHAR(32) NOT NULL,
                                       actor_id BIGINT NULL,

                                       correlation_id CHAR(36) NULL,
                                       request_id VARCHAR(64) NULL,
                                       route_name VARCHAR(255) NULL,

                                       ip_address VARCHAR(45) NULL,
                                       user_agent VARCHAR(512) NULL,

    -- Duration for timing metrics (optional)
                                       duration_ms INT UNSIGNED NULL,

    -- Additional diagnostics metadata (avoid PII; never store secrets)
                                       metadata JSON NULL,

                                       occurred_at DATETIME(6) NOT NULL,

                                       UNIQUE KEY uq_diagnostics_telemetry_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                       INDEX idx_diag_telemetry_time (occurred_at, id),

    -- Required search dimensions
                                       INDEX idx_diag_telemetry_actor_time (actor_type, actor_id, occurred_at),
                                       INDEX idx_diag_telemetry_event_time (event_key, occurred_at),

    -- Dashboards
                                       INDEX idx_diag_telemetry_severity_time (severity, occurred_at),

    -- Correlation helpers
                                       INDEX idx_diag_telemetry_correlation_time (correlation_id, occurred_at),
                                       INDEX idx_diag_telemetry_request_time (request_id, occurred_at),
                                       INDEX idx_diag_telemetry_route_time (route_name, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Diagnostics telemetry for tracing/performance/technical errors (non-business). Searchable by event_key+time and actor+time. Avoid PII; prefer hashed identifiers if needed.';


-- ==========================================================
-- 6) Delivery Operations (NOTIFICATIONS / JOBS / WEBHOOKS)
-- ----------------------------------------------------------
-- Answers: "What delivery/queue/job operations happened and their status?"
-- Best-effort domain for operational observability & retries.
-- ==========================================================

CREATE TABLE delivery_operations (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                     event_id CHAR(36) NOT NULL,

    -- Delivery channel (email/telegram/sms/webhook/push/job)
                                     channel VARCHAR(32) NOT NULL,

    -- Operation type (notification_send/webhook_deliver/job_run/...)
                                     operation_type VARCHAR(64) NOT NULL,

    -- Who initiated the operation (can be NULL for pure system jobs)
                                     actor_type VARCHAR(32) NULL,
                                     actor_id BIGINT NULL,

    -- Target of the delivery (optional)
                                     target_type VARCHAR(64) NULL,
                                     target_id BIGINT NULL,

    -- Status (queued/sent/delivered/failed/retrying/cancelled...)
                                     status VARCHAR(32) NOT NULL,

    -- Retry counter
                                     attempt_no INT UNSIGNED NOT NULL DEFAULT 0,

    -- Lifecycle timestamps (optional)
                                     scheduled_at DATETIME(6) NULL,
                                     completed_at DATETIME(6) NULL,

                                     correlation_id CHAR(36) NULL,
                                     request_id VARCHAR(64) NULL,

    -- Provider metadata (optional)
                                     provider VARCHAR(64) NULL,
                                     provider_message_id VARCHAR(128) NULL,

    -- Failure details (best-effort; no secrets)
                                     error_code VARCHAR(64) NULL,
                                     error_message TEXT NULL,

    -- Additional metadata (MUST NOT contain secrets)
                                     metadata JSON NOT NULL,

                                     occurred_at DATETIME(6) NOT NULL,

                                     UNIQUE KEY uq_delivery_operations_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                     INDEX idx_delivery_ops_time (occurred_at, id),

    -- Required search dimensions
                                     INDEX idx_delivery_ops_actor_time (actor_type, actor_id, occurred_at),
                                     INDEX idx_delivery_ops_channel_time (channel, occurred_at),
                                     INDEX idx_delivery_ops_type_time (operation_type, occurred_at),
                                     INDEX idx_delivery_ops_status_time (status, occurred_at),

    -- Deep investigations
                                     INDEX idx_delivery_ops_target_time (target_type, target_id, occurred_at),

    -- Correlation helpers
                                     INDEX idx_delivery_ops_correlation_time (correlation_id, occurred_at),
                                     INDEX idx_delivery_ops_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Delivery/system operations for notifications, queues, jobs, webhooks. Searchable by channel/type/status+time and actor+time. Track retries & providers. No secrets in metadata.';


-- ==========================================================
-- 7) Generic Checkpoints (RESERVED)
-- ----------------------------------------------------------
-- Generic cursor checkpoints for future processors:
-- - archivers (hot -> archive)
-- - materializers (outbox -> log)
-- - exporters (MySQL -> external)
-- Not used by baseline itself.
-- ==========================================================

CREATE TABLE log_processing_checkpoints (
                                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Logical stream identifier (e.g. audit_trail, security_signals, diagnostics_telemetry)
                                            log_stream VARCHAR(64) NOT NULL,

    -- Processor name for uniqueness if multiple processors exist in future
                                            processor VARCHAR(64) NOT NULL,

    -- Cursor: last successfully processed row (ordered by occurred_at then id)
                                            last_processed_occurred_at DATETIME(6) NULL,
                                            last_processed_mysql_id BIGINT UNSIGNED NULL,

    -- Operational metadata
                                            last_run_started_at DATETIME(6) NULL,
                                            last_run_finished_at DATETIME(6) NULL,
                                            last_batch_count INT UNSIGNED NOT NULL DEFAULT 0,

    -- Failure tracking (best-effort; do not store secrets)
                                            last_error_code VARCHAR(64) NULL,
                                            last_error_message VARCHAR(512) NULL,

                                            updated_at DATETIME(6) NOT NULL,

                                            UNIQUE KEY uq_log_processing_checkpoint (log_stream, processor),
                                            INDEX idx_log_processing_checkpoints_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='RESERVED: Generic checkpoints for future log processors (materializers/archivers/exporters). Cursor order: occurred_at ASC, id ASC. NOT USED in current baseline.';
