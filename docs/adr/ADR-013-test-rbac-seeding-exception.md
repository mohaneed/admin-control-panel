# ADR-013: Temporary RBAC Seeding Exception for Endpoint Tests

**Status:** APPROVED — TEMPORARY  
**Date:** 2026-01-19  
**Project:** maatify/admin-control-panel  
**Scope:** Testing / Authorization / RBAC  
**Phase:** Phase 1 — Admin Create Endpoint Tests

---

## Context

The project enforces a **strict, canonical testing model** as defined in:

`docs/PROJECT_CANONICAL_CONTEXT.md`  
Section: **🧪 I) Testing & Verification Model (CANONICAL)**

Key enforced rules include:

- Endpoint / Integration Tests are **MANDATORY**
- Tests must execute via the **full HTTP pipeline**
- Tests must use **real authorization and step-up flows**
- **SystemOwnership bypass is forbidden**
- **Direct SQL / PDO usage is forbidden** in tests
- Tests must rely on **real container services only**

---

## Problem Statement

During Phase 1 (Admin Create endpoint tests), a **hard architectural blocker** was identified:

- There are **no services or repositories** capable of:
    - Creating permissions
    - Creating roles
    - Binding roles to permissions
- Existing RBAC-related repositories are **read-only**:
    - `AdminDirectPermissionRepository` → read-only
    - `RolePermissionRepository` → read-only
    - `AdminRoleRepository` → can assign roles, but cannot create them
- No `RoleService` or `PermissionService` exists

As a result:

- Real authorization **cannot be established**
- SystemOwnership **cannot be used** (forbidden)
- SQL/PDO seeding **is forbidden by default**
- Phase 1 tests cannot be completed without violating canonical rules

This represents a **legitimate architectural blocker**, not an implementation error.

---

## Decision

A **temporary, explicitly documented exception** is approved:

### 🔓 Test-Only RBAC Seeding Exception

Tests are allowed to use **SQL / PDO ONLY inside `setUp()`**  
**exclusively** for the purpose of seeding RBAC data required to perform
**real authorization checks**.

This exception is:

- **Temporary**
- **Strictly scoped**
- **Fully documented**
- **Tracked via this ADR**
- **Not a precedent**

---

## Allowed Usage (Strict Scope)

The following operations are allowed **ONLY inside `setUp()`** of endpoint tests:

- Create required **permissions** (e.g. `admin.create`)
- Create required **roles**
- Bind **roles ↔ permissions**
- Assign **roles ↔ admin**

All usage must satisfy:

- SQL/PDO is used **only for seeding**
- No business logic is reimplemented
- No authorization shortcuts are introduced

---

## Explicit Prohibitions

Even with this exception, the following remain **STRICTLY FORBIDDEN**:

❌ SQL / PDO usage inside test bodies  
❌ SQL / PDO usage inside assertions  
❌ SQL / PDO usage inside `tearDown()`  
❌ Manual cleanup (`DELETE`, `TRUNCATE`)  
❌ Persistent side effects across tests  
❌ SystemOwnership bypass  
❌ Mocking authorization or audit components  
❌ Using this exception outside of tests

Violations are considered **ARCHITECTURE VIOLATIONS**.

---

## Isolation Requirements (Unchanged)

All endpoint tests must still comply with:

- Real database (`*_test`)
- Full HTTP + middleware pipeline
- **Transaction-based isolation (rollback preferred)**
- Fail-closed verification
- No dependency on execution order

This ADR **does NOT relax isolation rules**.

---

## Consequences

### Positive
- Phase 1 endpoint tests can proceed
- Real authorization is preserved
- No architectural shortcuts are introduced
- The system correctly exposes a missing RBAC write capability

### Negative
- Temporary technical debt is introduced
- Tests contain seeding logic that must later be removed

This tradeoff is accepted **explicitly and knowingly**.

---

## Exit Criteria (Mandatory Cleanup)

This exception MUST be removed when:

- RBAC write services are implemented, including:
    - Permission creation
    - Role creation
    - Role ↔ permission binding

At that point:

- All SQL/PDO seeding must be removed
- Tests must rely solely on services
- This ADR must be marked as **RESOLVED**
- Any residual SQL usage is a violation

---

## Final Notes

This ADR exists to:

- Preserve architectural integrity
- Avoid silent shortcuts
- Ensure Phase 1 does not stall indefinitely
- Maintain a clear upgrade path

The exception is **controlled**, **temporary**, and **auditable**.

Any use beyond the defined scope is invalid.

---
