# REFACTOR PLAN — CRYPTO & DATABASE CENTRALIZATION

**Status:** ARCHITECTURE-MANDATED  
**Applies To:** All Executors (AI & Human)  
**Related Docs:**
- docs/PROJECT_CANONICAL_CONTEXT.md
- docs/adr/ADR-001-input-normalization.md
- docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md

---

## 1. PURPOSE & SCOPE (LOCKED)

This document defines a **mandatory, bounded refactor plan** to:

1. Centralize all cryptographic operations
2. Eliminate raw SQL from Domain Services
3. Prevent open-ended or creative refactoring
4. Enforce strict architectural compliance during refactor work

This is **NOT** a feature plan.  
This is **NOT** an optimization plan.  
This is a **corrective architectural remediation plan**.

---

## 2. NON-NEGOTIABLE PRINCIPLES

### 2.1 No Behavior Change

Refactor work under this plan MUST:

- Preserve all existing security semantics
- Preserve all cryptographic algorithms
- Preserve all crypto contexts and versions
- Preserve all database schemas and queries (logic only relocated)

Any change in behavior is **FORBIDDEN**.

---

### 2.2 Crypto Is Governed Logic (HARD RULE)

Cryptography is **not refactorable logic**.

❌ No executor is allowed to:
- Re-implement encryption
- Replace algorithms
- Simplify cryptographic flows
- Introduce new primitives
- Introduce new contexts

All cryptography decisions are **LOCKED** by canonical architecture.

---

## 3. CRYPTO ENFORCEMENT MODEL (MANDATORY)

### 3.1 Single Entry Point

All cryptographic operations MUST go through:

```

App\Domain\Contracts\CryptoFacadeInterface

```

❌ Direct usage of:
- `openssl_*`
- `hash_*`
- `random_bytes`
- `sodium_*`
- HKDF utilities

outside the Crypto module is **STRICTLY FORBIDDEN**.

---

### 3.2 Crypto Context Registry (LOCKED)

All reversible encryption MUST use predefined contexts from:

```

App\Domain\Security\CryptoContext

```

Rules:

- Contexts MUST be versioned (`:v1`, `:v2`)
- Contexts MUST be static
- Contexts MUST be documented
- Runtime or dynamic contexts are forbidden

---

### 3.3 Usage Matrix (LOCKED)

| Use Case            | Method       | Context            |
|---------------------|--------------|--------------------|
| Passwords           | hashSecret   | ❌                  |
| OTP / Verification  | hashSecret   | ❌                  |
| Email Recipient     | encrypt      | EMAIL_RECIPIENT_V1 |
| Email Payload       | encrypt      | EMAIL_PAYLOAD_V1   |
| TOTP Seed           | encrypt      | TOTP_SEED_V1       |
| Identifiers (PII)   | encrypt      | IDENTIFIER_*_V1    |

Any deviation is a **Canonical Violation**.

---

## 4. CRYPTO APPLICATION SERVICES (PRE-REFACTOR REQUIREMENT)

### 4.1 Purpose

To prevent open-ended refactor, **NO controller or domain service** may
interact with the Crypto module directly.

Instead, the system defines **Crypto Application Services**.

These services:
- Are thin adapters
- Contain NO business logic
- Contain NO crypto primitives
- Translate domain intent → crypto operation

---

### 4.2 Mandatory Crypto Services

The following services MUST exist before refactor begins:

#### 4.2.1 AdminIdentifierCryptoService
Responsible for:
- Encrypting admin email
- Decrypting admin email (where permitted)
- Deriving blind indexes

Used by:
- Admin creation
- Admin lookup
- Email verification

---

#### 4.2.2 SystemStateCryptoService
Responsible for:
- Encrypting sensitive system_state values
- Decrypting values for internal evaluation

Used by:
- RecoveryStateService
- System configuration flows

---

#### 4.2.3 NotificationCryptoService
Responsible for:
- Encrypting notification payloads
- Encrypting email queue data

Used by:
- Email queue writers
- Notification dispatchers

---

#### 4.2.4 SecretMaterialCryptoService (Optional)
Responsible for:
- API tokens
- Webhook secrets
- External credentials

---

### 4.3 Access Rules

| Layer           | May Use Crypto Application Services |
|-----------------|-------------------------------------|
| Controllers     | ❌ NO                                |
| Domain Services | ❌ NO                                |
| Repositories    | ❌ NO                                |
| App Services    | ✅ YES                               |
| Crypto Module   | ✅ YES                               |

---

## 5. REFACTOR PHASES (STRICTLY ORDERED)

### 🔴 Phase R1 — CRITICAL HARD VIOLATIONS

#### R1.1 AdminController Remediation

**Violations:**
- Cryptographic logic inside controller
- Direct repository access
- Manual DTO construction

**Actions:**
- Move crypto + creation logic to AdminService
- AdminService MUST use AdminIdentifierCryptoService
- Controller becomes HTTP adapter only

❌ Controller MUST NOT:
- Encrypt
- Hash
- Derive keys
- Construct domain DTOs

---

#### R1.2 RecoveryStateService Remediation

**Violations:**
- Raw SQL inside domain service
- Possible crypto primitives usage

**Actions:**
- Extract SQL into SystemStateRepository
- Use SystemStateCryptoService for encryption
- Domain service contains decisions only

---

### 🟠 Phase R2 — TRANSACTIONAL DECOUPLING

**Problem:**
- PDO injected into domain services

**Solution:**
- Introduce TransactionManagerInterface
- Domain services depend on abstraction only

❌ This phase MUST NOT touch crypto.

---

### 🟢 Phase R3 — CLEANLINESS (OPTIONAL)

- Middleware helpers
- Response factories
- Cookie builders

No security impact.

---

## 6. EXECUTOR COMPLIANCE CHECKLIST (MANDATORY)

Before submitting any refactor work, executor MUST confirm:

- [ ] No cryptographic primitives were introduced
- [ ] CryptoFacadeInterface was used exclusively
- [ ] Crypto Application Services were respected
- [ ] No SQL was added outside repositories
- [ ] No behavior or security semantics changed
- [ ] No new crypto contexts were introduced

Failure on any item = **REJECTED EXECUTION**

---

## 7. ENFORCEMENT & ANTI-REGRESSION

### 7.1 Static Enforcement

CI MUST fail if any of the following are detected outside Crypto module:

- `openssl_`
- `hash_`
- `random_bytes`
- `sodium_`

---

### 7.2 Review Gate

Any PR touching:
- Crypto
- Database
- Domain services

MUST undergo architectural review.

---

## 8. RELATIONSHIP TO CANONICAL CONTEXT

This document:
- DOES NOT replace `PROJECT_CANONICAL_CONTEXT.md`
- IMPLEMENTS and ENFORCES its crypto decisions
- Acts as the execution-level guardrail

Canonical Context defines **WHAT** is allowed.  
This plan defines **HOW refactor is allowed to proceed**.

---

## 9. FINAL NOTE (NON-NEGOTIABLE)

This refactor plan exists to:

- Protect security invariants
- Prevent architectural drift
- Stop refactor creativity
- Preserve auditability and governance

Deviation from this plan without an explicit ADR is a
**SECURITY AND ARCHITECTURE VIOLATION**.
