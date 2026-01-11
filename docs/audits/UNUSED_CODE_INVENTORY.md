---
Document Type: Audit / Inventory
Scope: Static analysis only
Excludes: Tests-only references, future planned features
Created At: 2026-01
Related Commits: fac1406, 7feb1ef
---

# 🧾 UNUSED & ORPHAN ARTIFACTS — DETAILED REPORT (FULL PATHS)

## Purpose

This document provides a **static inventory snapshot** of code artifacts
(classes, DTOs, interfaces, controllers, and modules)
that are currently unused or orphaned in the codebase.

This document exists for:
- Visibility
- Architectural clarity
- Future decision support

## Non-Goals

- This document does **NOT** mandate deletions
- This document does **NOT** mandate refactoring
- This document does **NOT** represent an execution or cleanup plan

Any action based on this inventory must be taken explicitly
and documented separately.

---

## Source

Static analysis based on:

- `app/Bootstrap/Container.php`
- `routes/web.php`
- `scripts/`
- Full text search across `app/`

---

## 🔴 CATEGORY A — HIGH RISK (REQUIRES EXPLICIT DECISION)

### 1️⃣ Password Hashing Duplication (AUTH / CRYPTO)

#### Files

```

app/Modules/Crypto/Password/PasswordHasher.php
app/Modules/Crypto/Password/PasswordHasherInterface.php
app/Modules/Crypto/Password/DTO/PasswordHashDTO.php
app/Modules/Crypto/Password/DTO/PasswordVerifyDTO.php

```

#### Active Alternative

```

app/Domain/Service/PasswordService.php   ← USED

```

#### Status

- ❌ `PasswordHasher` module is **NOT referenced**
- ❌ Not bound in Container
- ❌ No consumer in Domain or Infrastructure
- ✔ `PasswordService` performs hashing & verification directly

#### Risk

- Two parallel hashing concepts exist
- No authoritative declaration
- High risk of future misuse (security regression)

#### Classification

🔴 **HIGH RISK — AUTH / CRYPTO**

#### Possible Handling Options (Non-Binding)

- **Architectural decision required**
- Declare a **single source of truth**:
  - `PasswordService` as authoritative
- Add explicit documentation or ADR:
  - Mark `app/Modules/Crypto/Password/*` as:
    - `@deprecated`, or
    - `@experimental`
- ❌ Do NOT silently delete without decision

---

## 🟠 CATEGORY B — MEDIUM RISK (MISLEADING / ORPHANED FEATURES)

### 2️⃣ Audit UI Controllers (Implemented but Unreachable)

#### Files

```

app/Http/Controllers/AdminSecurityEventController.php
app/Http/Controllers/AdminSelfAuditController.php
app/Http/Controllers/AdminTargetedAuditController.php

```

#### Status

- ✔ Controllers fully implemented
- ✔ Bound in `Container.php`
- ❌ No routes defined in `routes/web.php`
- ❌ Feature effectively disabled

#### Risk

- Gives a false impression that:
  - Audit UI is active
- Can confuse maintainers or auditors

#### Classification

🟠 **MEDIUM — FEATURE CONFUSION**

#### Possible Handling Options (Non-Binding)

- Add explicit docblock comment:
  > “Controller implemented but routes intentionally disabled — feature postponed”
- OR remove Container bindings while keeping code
- ❌ No urgent deletion required

---

### 3️⃣ Notification Routing Logic (Orphan After Delivery Removal)

#### Files

```

app/Domain/Service/AdminNotificationRoutingService.php
app/Domain/Contracts/NotificationRoutingInterface.php

```

#### Status

- ✔ Bound in `Container.php`
- ❌ No consumers after Notification delivery removal
- ❌ No runtime usage

#### Risk

- Minimal runtime risk
- Architectural noise only

#### Classification

🟠 **MEDIUM — ORPHANED DOMAIN LOGIC**

#### Possible Handling Options (Non-Binding)

- Comment clearly:
  > “Kept intentionally for future Notification delivery phase”
- Optional relocation to `/future` or `/planned`
- Safe to keep

---

## 🟢 CATEGORY C — LOW RISK (SAFE / EXPECTED REMNANTS)

### 4️⃣ Notification DTOs (Passive Data Objects)

#### Files

```

app/Domain/DTO/Notification/NotificationDeliveryDTO.php
app/Domain/DTO/Notification/DeliveryResultDTO.php
app/Domain/DTO/Notification/AdminAlertDTO.php

```

#### Status

- ❌ No references
- ❌ Delivery system removed
- DTOs only (no behavior)

#### Risk

- None
- Passive, inert

#### Classification

🟢 **LOW — SAFE**

#### Handling

- Leave as-is
- Or remove later when rebuilding Notifications

---

### 5️⃣ Failed Notification Persistence Artifacts

#### Files

```

app/Infrastructure/Repository/FailedNotificationRepository.php
app/Domain/Contracts/FailedNotificationRepositoryInterface.php

```

#### Status

- ✔ Bound in Container
- ❌ Not injected anywhere
- ❌ Delivery layer removed

#### Risk

- None
- Expected after feature excision

#### Classification

🟢 **LOW — SAFE REMNANTS**

#### Handling

- Keep for future retry / DLQ phase
- Or document as unused

---

### 6️⃣ Admin List / Validation Remnants

#### Files

```

app/Modules/Validation/Schemas/AdminListSchema.php
app/Domain/DTO/AdminList/AdminListQueryDTO.php

```

#### Status

- ❌ No active controllers
- ❌ Feature incomplete
- AdminListController commented out

#### Risk

- None

#### Classification

🟢 **LOW — HALF-BUILT FEATURE**

#### Handling

- Leave untouched
- Complete or remove later

---

### 7️⃣ Blueprint / Placeholder Interfaces

#### Files

```

app/Domain/Contracts/NotificationChannelPreferenceResolverInterface.php
app/Domain/Contracts/CryptoFacadeInterface.php
app/Domain/Contracts/AdminActivityQueryInterface.php

```

#### Status

- ❌ No implementations or consumers
- Appear to be architectural blueprints

#### Risk

- None

#### Classification

🟢 **LOW — FUTURE CONTRACTS**

#### Handling

- Leave as-is
- Optionally document intended future use

---

## 📊 FINAL SUMMARY TABLE

| Category  | Files                        | Risk   | Action Needed         |
|-----------|------------------------------|--------|-----------------------|
| 🔴 High   | Crypto Password Module       | High   | **Decision required** |
| 🟠 Medium | Audit + Notification Routing | Medium | Document intent       |
| 🟢 Low    | DTOs / Interfaces / Repos    | Low    | Ignore for now        |

---

## 🧠 FINAL ARCHITECTURAL POSITION

- ❌ No deletions recommended at this stage
- ❌ No refactors recommended
- ✅ Only **clarity & documentation** required
- 🔴 One real architectural risk remains:
  **Password hashing authority**

---

## 📝 Decision Log

_No decisions have been taken based on this inventory yet._

This section is intentionally left empty.
