# Logging and Observability - Full ASCII Map (maatify/admin-control-panel)

Purpose: one copy/pasteable reference that explains the whole logging ecosystem using ASCII.

Includes:
- Audit Logs (authoritative)
- Security Events (observational security signals)
- Activity Logs (operational actions)
- Telemetry (tracing/performance)
- PSR-3 diagnostic logs
- Data Access Logs (deferred; not allowed yet)

---

## 0) Legend

- `-->` calls / depends on
- `==>` writes to DB
- `[swallow]` failure is swallowed (fail-open)
- `[block]` failure blocks the business transaction (fail-closed)

Status markers:
- `OK`    : compliant / intended
- `WARN`  : partial / legacy drift
- `VIOL`  : architectural violation (boundary break)
- `BUG`   : implementation bug (system works, but signal is lost)

---

## 1) High-level map (all log types)

```
                        +-------------------------+
                        |  Business request flow  |
                        +-----------+-------------+
                                    |
                                    v
                          Controllers / Services
                                    |
    +-------------------+-----------+-----------+-------------------+
    |                   |                       |                   |
    v                   v                       v                   v
 Audit Logs         Security Events          Activity Logs        Telemetry
 (authoritative)    (observational)          (operational)        (debug/perf)
    |                   |                       |                   |
    v                   v                       v                   v
 audit_outbox        security_events          activity_logs       telemetry_traces
    |
    | (outbox consumer)
    v
 audit_logs

 PSR-3 diagnostic logs are filesystem-only and should not be used as a substitute
 for DB-backed security/audit/event tables.

 Data Access Logs are deferred and NOT allowed to be emulated.
```

---

## 2) Audit Logs (authoritative, transactional, fail-closed)

### 2.1 Canonical authoritative write path (OK)

```
Domain Service (state change / authority change)
  |
  |--> AuthoritativeSecurityAuditWriterInterface
          |
          |--> AuthoritativeAuditWriter  [block]
                  |
                  |==> INSERT audit_outbox  (in the same DB transaction)
                          |
                          |--> outbox consumer/worker
                                  |
                                  |==> INSERT audit_logs (authoritative)
```

Key idea:
- If the audit write cannot be guaranteed, the business state change must not commit.

### 2.2 Known violations (from audit reports) (VIOL)

```
AdminController::create
  |
  |--> AdminRepository writes admin rows
  |
  X Missing DB transaction wrapper
  X Missing authoritative audit write

AdminController::addEmail
  |
  |--> AdminRepository writes identifier/email rows
  |
  X Missing authoritative audit write
```

### 2.3 Forbidden direct writers to audit_logs (VIOL)

```
TelemetryAuditLoggerInterface (legacy)
  |
  |--> PdoTelemetryAuditLogger
          |
          |==> INSERT audit_logs   (NON-authoritative code touching authoritative table)
```

Rule of thumb:
- `audit_logs` must only be populated by the outbox consumer.

---

## 3) Security Events (best-effort, swallow on failure)

Security events are observational signals (login attempts, step-up challenges, permission denials).
They MUST NOT block the request flow.

### 3.1 Modern recorder path (OK)

```
Service (e.g., StepUpService)
  |
  |--> SecurityEventRecorder (modern)
          |
          |-- uses Enums (SecurityEventTypeEnum / SeverityEnum)
          |
          |--> SecurityEventLoggerMysqlRepository
                  |
                  |==> INSERT security_events
          |
          +-- [swallow] catches SecurityEventStorageException
```

### 3.2 Legacy drift path (WARN)

```
Service (e.g., AdminAuthenticationService / RememberMeService / SessionValidationService)
  |
  |--> SecurityEventLoggerInterface (legacy; string event names)
          |
          |--> Legacy repository  [swallow]
                  |
                  |==> INSERT security_events
```

Problems with the legacy path:
- string event names drift from canonical enums
- services leak infra concerns (DTO construction, request_id handling)
- parallel systems (legacy vs modern) cause inconsistency over time

### 3.3 Visibility gap (MISSING) (VIOL-like gap)

```
ValidationGuard throws ValidationFailedException
  |
  X No security event emitted for validation failures
```

---

## 4) Activity Logs (operational actions, not views)

Activity logs record staff actions (create, update, revoke, assign) for UI history.

### 4.1 Typical path (OK)

```
Controller / Service (action)
  |
  |--> ActivityLogWriterInterface
          |
          |==> INSERT activity_logs
```

### 4.2 Misuse noted by audit (VIOL)

```
TelemetryQueryController
  |
  |--> ActivityLogWriterInterface
          |
          |==> activity_logs: TELEMETRY_LIST (view/list)
```

This conflicts with the strict rule: do not log view/read/open as Activity Logs.
If you need to log data exposure, that belongs to Data Access Logs (but that category is deferred).

---

## 5) Telemetry (best-effort tracing, debugging/performance)

Telemetry is write-only, non-authoritative, and must never affect business logic.

### 5.1 Canonical layered flow (OK)

```
HTTP Request
  |
  v
HttpRequestTelemetryMiddleware (App)  [swallow Throwable]
  |
  |--> HttpTelemetryRecorderFactory (App)
  |        |
  |        |--> HttpTelemetryAdminRecorder / HttpTelemetrySystemRecorder (App)
  |                 |
  |                 |--(inject)--> RequestContext (request_id, route_name, ip, ua, actor)
  |                 |
  |                 |--> TelemetryRecorderInterface (Domain)
  |                          |
  |                          |--> TelemetryRecorder (Domain)
  |                                  |
  |                                  |--> TelemetryLoggerInterface (Module)
  |                                          |
  |                                          |--> TelemetryLoggerMysqlRepository (PDO)
  |                                                  |
  |                                                  |==> INSERT telemetry_traces
  |
  v
Request continues even if telemetry fails (fail-open)
```

### 5.2 Critical implementation bug (BUG)

```
DB schema: telemetry_traces.event_key
Code insert: telemetry_traces.event_type

=> SQL error: Unknown column 'event_type'
=> telemetry fails 100% of the time
=> swallowed by design
=> application works but telemetry_traces remains empty
```

---

## 6) PSR-3 Diagnostic Logs (filesystem)

PSR-3 is diagnostic only.
It should be used for:
- unexpected runtime issues
- internal warnings when swallowing exceptions (carefully)

It must NOT be used as a substitute for:
- Audit Logs
- Security Events
- Activity Logs
- Telemetry

---

## 7) Data Access Logs (DEFERRED - NOT IMPLEMENTED, NOT ALLOWED)

Data Access Logs are a future category intended to capture data exposure/access:
- who accessed what
- when
- under which permissions

Current state:

```
Data Access Logs (DEFERRED)
  |
  X NOT IMPLEMENTED
  X NOT AVAILABLE FOR USE
  X MUST NOT be simulated by any existing category
        - Activity Logs (view/read/open)
        - Security Events
        - Telemetry
        - Audit Logs
```

Until a dedicated ADR + schema + privacy/retention policy exists, NO data access logging is allowed.

---

## 8) Table ownership and write protection

```
Table             Allowed writer(s)                                 Status
----------------  ------------------------------------------------  ------------------------------
audit_outbox       AuthoritativeAuditWriter (transactional)          OK

audit_logs         Outbox consumer only                              OK
                  Any direct writer (e.g., TelemetryAuditLogger)      VIOL

security_events    SecurityEventRecorder -> LoggerMysqlRepository     OK
                  Legacy direct logger injection in services          WARN (migrate)

activity_logs      ActivityLogWriterInterface for ACTIONS             OK
                  View/list/open logging in activity_logs             VIOL (do not emulate access)

telemetry_traces   TelemetryLoggerMysqlRepository                     BUG (schema mismatch blocks)
```

---

## 9) Boundary rules (short checklist)

1) Protect `audit_logs`:
   - only outbox consumer writes `audit_logs`
   - remove/disable any direct writers

2) Admin authority events:
   - Admin create and credential changes must be wrapped in transaction + authoritative audit

3) Security events:
   - services should emit via SecurityEventRecorder (enums)
   - migrate legacy string-based loggers to the recorder
   - add missing validation failure events (visibility)

4) Activity logs:
   - actions only
   - do not log views/read/open

5) Telemetry:
   - keep fail-open
   - fix schema mismatch so telemetry actually records

6) Data access logs:
   - deferred
   - do not emulate using other log categories
