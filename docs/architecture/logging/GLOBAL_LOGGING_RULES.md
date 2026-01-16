# 🌐 Global Logging Rules

**Project:** maatify/admin-control-panel  
**Status:** CANONICAL  
**Audience:** Backend Developers, Security Reviewers, Auditors  
**Last Updated:** 2026-01

---

## 1. Purpose

This document defines the **global, canonical rules** for all logging
within the Admin Control Panel.

It exists to enforce:
- Clear separation of concerns
- Correct semantic usage of log types
- Elimination of ambiguous or misleading logs
- Long-term audit and security correctness

Any deviation from these rules is considered a **critical architectural violation**.

---

## 2. Log Categories (Authoritative)

The system recognizes **exactly four** functional logging categories:

| Category        | Purpose                               | Authority Level       |
|-----------------|---------------------------------------|-----------------------|
| Audit Logs      | Authoritative record of state changes | **Authoritative**     |
| Security Events | Observational security signals        | **Non-authoritative** |
| Activity Logs   | Admin operational actions             | **Non-authoritative** |
| Telemetry       | Metrics and diagnostics               | **Non-authoritative** |

Additionally, the system uses **PSR-3 Logger** as a **diagnostic channel**
(not a business logging category).

No other logging category is allowed.

---

## 3. Audit Logs

### 3.1 Definition

Audit Logs represent **authoritative, irreversible state changes** that affect:
- Security posture
- Authority
- Access rights
- Persistent system state

They MUST be:
- Transactional
- Fail-closed
- Authoritative source of truth

### 3.2 Storage

Audit logs are written to:

```

audit_outbox

```

The outbox is the **only authoritative audit source**.

Downstream consumers MAY materialize audit records into other tables
for querying, but those tables are NOT authoritative.

### 3.3 When to Use Audit Logs

Audit Logs MUST be used for:
- Session creation and revocation
- Step-Up grant issuance and revocation
- Permission or role assignment
- Admin creation or deletion
- Credential changes
- Security posture changes

### 3.4 When NOT to Use Audit Logs

Audit Logs MUST NOT be used for:
- Failed login attempts
- Invalid credentials
- Step-Up failures
- Permission denials
- Any event that does NOT change system state

---

## 4. Security Events

### 4.1 Definition

Security Events are **observational signals** that indicate:
- Suspicious behavior
- Failed security actions
- Risk indicators
- Abuse patterns

They DO NOT represent state changes.

### 4.2 Characteristics

Security Events:
- Are best-effort
- MUST NOT affect control flow
- MUST NOT be transactional
- MUST NOT block user actions

### 4.3 Storage

Security Events are written to:

```

security_events

```

### 4.4 Event Structure

All Security Events MUST use:
- `SecurityEventRecordDTO`
- `SecurityEventRecorderInterface`
- Typed enums for:
  - Event Type
  - Severity
  - Actor Type

### 4.5 Severity Rules

| Severity | Meaning              |
|----------|----------------------|
| INFO     | Informational signal |
| WARNING  | Suspicious behavior  |
| ERROR    | Security failure     |
| CRITICAL | High-risk incident   |

Severity reflects **risk**, not authority.

### 4.6 Examples

Security Events include:
- Login failure
- Invalid password
- Step-Up not enrolled
- Step-Up invalid code
- Step-Up risk mismatch
- Permission denied

---

## 5. Activity Logs

### 5.1 Definition

Activity Logs track **admin operational actions** for visibility and review.

They answer:
> "What did this admin do?"

### 5.2 Storage

Activity Logs are written to:

```

activity_logs

````

### 5.3 When to Use Activity Logs

Activity Logs SHOULD be used for:
- Viewing records
- Triggering actions
- Performing administrative operations
- Manual admin workflows

### 5.4 When NOT to Use Activity Logs

Activity Logs MUST NOT be used for:
- Authentication
- Authorization
- Security failures
- Any automatic system action

---

## 6. Telemetry

### 6.1 Definition

Telemetry is used ONLY for:
- Performance metrics
- Diagnostics
- System health
- Observability

### 6.2 Rules

Telemetry:
- MUST NOT represent security or authority events
- MUST NOT write to audit tables
- MUST NOT be used for compliance or review
- MUST tolerate failure (fail-open)

### 6.3 Examples

Telemetry includes:
- Request duration
- Cache hit/miss
- Background job timing
- Error rates

---

## 6.5 PSR-3 Diagnostic Logging

### 6.5.1 Definition

The PSR-3 Logger is used exclusively for **diagnostic and operational
error reporting**.

It exists to capture:
- Silent failures
- Unexpected runtime conditions
- Infrastructure or dependency issues
- Exceptions that are intentionally swallowed

PSR-3 logs are **NOT business events** and **NOT part of any audit or
security trail**.

---

### 6.5.2 When to Use PSR-3 Logger

PSR-3 Logger MUST be used when:
- An exception is caught and intentionally NOT rethrown
- A best-effort operation fails silently
- A non-critical dependency fails (cache, telemetry, async dispatch)
- An unexpected state occurs that does not affect user flow
- Logging itself fails (nested logging failure)

Examples:
- Telemetry write failure
- Notification dispatch failure
- Optional external service timeout
- Background task error that should not block execution

---

### 6.5.3 When NOT to Use PSR-3 Logger

PSR-3 Logger MUST NOT be used for:
- Security events
- Authorization or authentication failures
- Audit logging
- Activity tracking
- Business-level failures
- Expected validation errors

If the system *expects* the failure, PSR-3 is NOT the correct channel.

---

### 6.5.4 Severity Mapping

PSR-3 log levels reflect **operational impact**, not business severity:

| PSR-3 Level       | Usage                               |
|-------------------|-------------------------------------|
| debug             | Development-time diagnostics        |
| info              | Normal but noteworthy condition     |
| warning           | Recoverable issue                   |
| error             | Non-recoverable operational failure |
| critical          | Infrastructure-level failure        |
| alert / emergency | Reserved for system-wide outages    |

---

### 6.5.5 Example

```php
try {
    $telemetryRecorder->record($event);
} catch (\Throwable $e) {
    $logger->warning(
        'Telemetry write failed',
        [
            'exception' => $e,
            'event_type' => $event->type,
        ]
    );
}
````

This is correct PSR-3 usage:

* The failure is unexpected
* The failure must not affect business logic
* The failure must be visible to operators

---

### 6.5.6 Core Rule

> **PSR-3 logs describe system problems — not user behavior.**

Using PSR-3 as a substitute for:

* Audit
* Security Events
* Activity Logs

is a **hard violation**.

---

## 7. Forbidden Patterns (Hard Rules)

The following are **explicitly forbidden**:

* ❌ Telemetry writing to `audit_logs`
* ❌ Audit logging for failures
* ❌ Security Events affecting control flow
* ❌ Activity Logs for authentication or authorization
* ❌ Double-writing the same event to multiple log types
* ❌ Using logs as a source of truth

Any of the above requires immediate remediation.

---

## 8. Enforcement

* All new code MUST comply with this document
* Code reviews MUST validate logging semantics
* Violations block merges
* This document supersedes legacy behavior

---

## 9. Change Policy

This document is:

* Canonical
* Version-controlled
* Changeable only via explicit architectural decision

Ad-hoc exceptions are NOT allowed.

---

## 10. Summary

> Logs are not interchangeable.

Each log type exists for a **specific purpose**.
Misusing logs creates:

* False audit trails
* Missed security incidents
* Compliance risks

Follow the rules strictly.
