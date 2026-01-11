# 📄 ADR — Notification Module Scope & Coupling

## ADR-ID

`ADR-007-notification-scope-and-history-coupling`

## Status

**ACCEPTED**

## Date

2026-01-10

---

## Context

The Notification module is designed as a **channel-agnostic orchestration system** responsible for:

* Queueing notification intents
* Delegating delivery execution to **channel-owned queues**
* Secure (encrypted) persistence of notification state
* Recording delivery outcomes for **Admin UX history** (sent / failed / skipped)

The module operates within the **Admin Control Panel** domain
and intentionally avoids direct delivery implementations.

---

## Decision

### 1️⃣ History Persistence Is Admin-Coupled (Intentionally)

* Notification history is persisted directly into:

```

admin_notifications

```

* The worker maps:

```

entity_id → admin_id

```

* The module **assumes `entity_type = admin`** for history logging.

This coupling is **intentional and accepted** for the current project scope.

---

### 2️⃣ No Channel Implementations in Notification Module

* The module **must not** contain:
* Email
* Telegram
* SMS
* Push

* Delivery channels are integrated **only via enqueue delegation**
to channel-owned delivery queues.

---

### 3️⃣ Delivery Execution Is Out of Scope

This ADR defines **orchestration boundaries only**.

Actual delivery execution is governed by:

* **ADR-008** — Email Delivery as Independent Channel Queue
* **ADR-009** — Telegram Delivery as Independent Channel Queue

The Notification module **must not**:
* Call transport APIs
* Perform retries
* Apply rate limits
* Handle channel-specific errors

---

### 4️⃣ Extraction as a Standalone Library Is Deferred

The Notification module **is NOT extraction-ready** due to:

* Direct SQL dependency on `admin_notifications`
* Domain-specific assumptions (Admin UX history)

Extraction would require:

* Introducing a `NotificationHistoryWriterInterface`
* Removing direct schema knowledge from the worker

This refactor is **explicitly deferred**.

---

## Consequences

### ✅ Positive

* Clean, stable Notification core
* Channel-agnostic orchestration
* Predictable lifecycle and testability
* Clear separation between intent and delivery
* No premature abstractions

### ⚠️ Trade-offs

* Cannot be reused for non-admin entities without refactor
* History schema is application-specific

---

## Guardrails (LOCKED)

The following **must not be added** before the Channels phase:

* Channel implementations inside `app/Modules/Notification`
* Business rules (e.g. “send welcome email”)
* Non-admin entity usage
* History abstraction refactors
* Direct delivery execution logic

---

## Future Work (Explicitly Out of Scope)

* `NotificationHistoryWriterInterface`
* Multi-entity history support
* Standalone library extraction

---
