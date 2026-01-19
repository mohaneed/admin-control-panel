# ARCHITECTURAL_CONFLICT_RESOLUTION_POLICY.md

## 🔒 Architectural Conflict Resolution Policy

**Status:** CANONICAL / LOCKED  
**Authority Level:** Architecture Governance  
**Applies To:** All Phases, Tests, Infrastructure, Security, and CI  
**Audience:** Core Maintainers, Reviewers, AI Agents, Auditors

---

## 1️⃣ Purpose

This document defines the **only allowed mechanism** for handling
**architectural deadlocks** within this project.

An architectural deadlock is **not a bug** and **not an implementation failure**.
It is a formally recognized state where **mandatory architectural rules**
cannot be simultaneously satisfied.

This policy exists to prevent:
- Ad-hoc workarounds
- Silent rule breaking
- Test-driven architecture erosion
- AI-introduced inconsistencies

---

## 2️⃣ Definition — Architectural Deadlock

An **Architectural Deadlock** exists when **ALL** of the following are true:

1. Two or more rules are marked as:
    - `MANDATORY`
    - `CANONICAL`
    - or `ARCHITECTURE-LOCKED`

2. All rules are individually correct and intentional.

3. There is **no valid execution path** that satisfies all rules **simultaneously**.

4. Resolving the conflict would require **one or more** of:
    - Modifying production code
    - Modifying infrastructure wiring
    - Introducing test doubles for core infra
    - Violating an existing ADR
    - Ignoring isolation / security guarantees

When these conditions are met, the system is considered **BLOCKED**, not broken.

---

## 3️⃣ Mandatory Action on Deadlock (NON-NEGOTIABLE)

When an Architectural Deadlock is detected:

✅ **ALL WORK MUST STOP IMMEDIATELY**

The following actions are **MANDATORY**:

1. A **BLOCKER REPORT** must be issued.
2. The report must clearly describe:
    - The conflicting rules
    - Why they cannot coexist
    - Why no compliant workaround exists
3. No partial delivery, workaround, or assumption is allowed.

❌ Continuing implementation is forbidden  
❌ “Temporary hacks” are forbidden  
❌ Silent deviations are forbidden

---

## 4️⃣ Allowed Resolution Paths (STRICT ORDER)

Only **one** of the following resolution paths may be chosen.

### 🅐 Resolution A — Production Architecture Change

- Requires a **new ADR**
- Requires explicit approval
- Applies when the conflict reveals a genuine architectural flaw
- **NOT ALLOWED mid-phase** unless escalated and approved

---

### 🅑 Resolution B — Explicit, Narrow, Temporary Exception ✅

Allowed **ONLY IF** all conditions are met:

- The exception is:
    - Explicit
    - Narrow in scope
    - Time-bounded
- The exception is documented in an ADR
- The ADR MUST include:
    - Exact reason for exception
    - Exact rules being temporarily relaxed
    - Explicit **removal condition**

Example removal conditions:
- “After RBAC write-side is implemented”
- “After transaction layer supports SAVEPOINT”
- “After Phase X is completed”

Undocumented exceptions are **ARCHITECTURE VIOLATIONS**.

---

### 🅒 Resolution C — Phase Freeze

- Work is paused
- Blocker remains open
- No partial implementation is accepted
- Phase is resumed only after resolution A or B

---

## 5️⃣ Exception Rules (CRITICAL)

Any exception introduced under Resolution B:

- MUST be documented
- MUST reference the blocking conflict
- MUST NOT be reused as precedent
- MUST NOT expand beyond its declared scope
- MUST include a **clear removal trigger**

Exceptions without removal triggers are INVALID.

---

## 6️⃣ Relationship to ADRs

- ADRs describe **decisions**
- This policy governs **conflicts between decisions**

In a conflict scenario:
- The ADR documents *what is allowed temporarily*
- This policy defines *why the exception exists and when it must be removed*

This policy does **not** replace ADRs.
It constrains how ADRs may be temporarily bent under deadlock.

---

## 7️⃣ Authority & Precedence

This policy overrides:
- Developer convenience
- Time pressure
- Test coverage pressure
- AI-generated assumptions

**Correctness > Progress**

Any implementation that violates this policy is invalid,
even if it passes tests.

---

## 8️⃣ Canonical Status

This document is **ARCHITECTURE-LOCKED**.

Changes require:
- Explicit architectural review
- Clear justification
- Maintainer approval

Ad-hoc modification is forbidden.

---

## 9️⃣ Final Principle

> When architecture makes progress impossible,
> stopping is the correct behavior.

Silence is failure.  
Documentation is authority.  
Discipline is architecture.
