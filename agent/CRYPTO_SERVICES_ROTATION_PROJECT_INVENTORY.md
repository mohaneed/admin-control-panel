# CRYPTO SERVICES & ROTATION — PROJECT INVENTORY (READ-ONLY)

## 1) Reconciliation Verdict (DRIFT: FOUND)
- **DRIFT FOUND**: `App\Domain\DTO\Crypto\EncryptedPayloadDTO` exists in `app/Domain/DTO/Crypto/` AND `app/Application/Crypto/DTO/`. The latter seems to be the intended canonical one for the new services, but the former exists.
- **DRIFT FOUND**: `PasswordHasher` (Module) is fully implemented but unused in `Container.php`. `PasswordService` (Domain) is used instead.
- **DRIFT FOUND**: `AdminIdentifierCryptoService` is implemented but NOT used in `AdminController` or `PdoAdminQueryReader`, which still use legacy direct crypto.

## 2) Executive Summary
The project is in a **hybrid state**. A modern, rotatable crypto architecture (`CryptoProvider`, `KeyRotationService`, `HKDF`) is fully implemented and wired for `NotificationCryptoService` and `TotpSecretCryptoService`. However, critical Identity (Admin Email) operations still rely on legacy, static-key `openssl` calls in controllers and readers. Password hashing uses a robust but distinct `PasswordService` with pepper rotation support. A duplicate DTO definition exists for encrypted payloads.

## 3) Current State Matrix

| Area               | Current Mechanism           | Uses Unified Service? | Key/Env                 | Risk     | Files (count) |
|:-------------------|:----------------------------|:----------------------|:------------------------|:---------|:--------------|
| **Notifications**  | `NotificationCryptoService` | **YES**               | `CRYPTO_KEYS` (HKDF)    | LOW      | 2             |
| **TOTP Secrets**   | `TotpSecretCryptoService`   | **YES**               | `CRYPTO_KEYS` (HKDF)    | LOW      | 2             |
| **Admin Identity** | Direct `openssl_encrypt`    | **NO**                | `EMAIL_ENCRYPTION_KEY`  | **HIGH** | 3             |
| **Blind Indexes**  | Direct `hash_hmac`          | **NO**                | `EMAIL_BLIND_INDEX_KEY` | MED      | 4             |
| **Passwords**      | `PasswordService`           | **NO** (Parallel)     | `PASSWORD_PEPPER`       | LOW      | 2             |
| **Session IDs**    | SHA-256 Hash                | **NO** (Standard)     | N/A                     | LOW      | 1             |

## 4) Unified Services Inventory

### 4.1 PasswordCryptoService
- **Interface:** `App\Application\Crypto\PasswordCryptoServiceInterface`
- **Implementation:** `App\Infrastructure\Crypto\PasswordCryptoService`
- **Contexts:** N/A (Delegates to `PasswordService`)
- **Dependencies:** `App\Domain\Service\PasswordService`

### 4.2 NotificationCryptoService
- **Interface:** `App\Application\Crypto\NotificationCryptoServiceInterface`
- **Implementation:** `App\Infrastructure\Crypto\NotificationCryptoService`
- **Contexts:** `email:recipient:v1`, `email:payload:v1`
- **Dependencies:** `CryptoProvider`

### 4.3 AdminIdentifierCryptoService
- **Interface:** `App\Application\Crypto\AdminIdentifierCryptoServiceInterface`
- **Implementation:** `App\Infrastructure\Crypto\AdminIdentifierCryptoService`
- **Contexts:** `identity:email:v1`
- **Dependencies:** `CryptoProvider`, `ADMIN_IDENTIFIER_PEPPER`

### 4.4 TotpSecretCryptoService
- **Interface:** `App\Application\Crypto\TotpSecretCryptoServiceInterface`
- **Implementation:** `App\Infrastructure\Crypto\TotpSecretCryptoService`
- **Contexts:** `totp:seed:v1`
- **Dependencies:** `CryptoProvider`

## 5) Rotation & Key Selection Flow

**Flow:**
`Caller` -> `CryptoProvider->context('ctx')` -> `CryptoContextFactory` -> `HKDF(RootKey + ctx)` -> `ReversibleCryptoService` -> `Aes256GcmAlgorithm`

**Key Selection:**
- **Encryption:** `KeyRotationService` selects the key with `status=ACTIVE` (defined by `CRYPTO_ACTIVE_KEY_ID`).
- **Decryption:** `KeyRotationService` selects the key matching the `key_id` in the payload.

**Env Variables:**
- `CRYPTO_KEYS` (JSON) -> **ACTIVE** (Root of Trust)
- `CRYPTO_ACTIVE_KEY_ID` -> **ACTIVE** (Selection Pointer)
- `EMAIL_ENCRYPTION_KEY` -> **LEGACY** (Used directly by Admin Identity; also fallback for `KeyRotationService`)
- `EMAIL_BLIND_INDEX_KEY` -> **LEGACY** (Direct usage)
- `ADMIN_IDENTIFIER_PEPPER` -> **ACTIVE** (Used by `AdminIdentifierCryptoService`)
- `PASSWORD_PEPPER` -> **ACTIVE**
- `PASSWORD_PEPPER_OLD` -> **ACTIVE** (Rotation support)

## 6) Legacy Crypto Usage Map (openssl/direct keys)

| File Path                                                         | Function/Class         | Purpose                           | Bypasses Service?   |
|:------------------------------------------------------------------|:-----------------------|:----------------------------------|:--------------------|
| `app/Http/Controllers/AdminController.php`                        | `addEmail`, `getEmail` | Encrypt/Decrypt Admin Emails      | **YES**             |
| `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`         | `decryptEmail`         | Decrypt Admin Emails for List     | **YES**             |
| `app/Infrastructure/Reader/Session/PdoSessionListReader.php`      | `decryptEmail`         | Decrypt Admin Emails for Sessions | **YES**             |
| `app/Modules/Crypto/Reversible/Algorithms/Aes256GcmAlgorithm.php` | `encrypt`, `decrypt`   | Core Primitive Implementation     | NO (Is the Service) |

## 7) Blind Index Map (keys + where computed)

| Key Source                | Usage Location                                        | Divergence          |
|:--------------------------|:------------------------------------------------------|:--------------------|
| `EMAIL_BLIND_INDEX_KEY`   | `AdminController::addEmail`                           | Legacy              |
| `EMAIL_BLIND_INDEX_KEY`   | `AdminController::lookupEmail`                        | Legacy              |
| `EMAIL_BLIND_INDEX_KEY`   | `PdoAdminQueryReader::queryAdmins`                    | Legacy              |
| `ADMIN_IDENTIFIER_PEPPER` | `AdminIdentifierCryptoService::deriveEmailBlindIndex` | **Modern (Unused)** |

## 8) Consumers Migration Status

| Consumer               | Current Path               | Unified Service? | Risk     | Next Step                                                        |
|:-----------------------|:---------------------------|:-----------------|:---------|:-----------------------------------------------------------------|
| **Email Queue Writer** | `PdoEmailQueueWriter`      | **YES**          | LOW      | None (Done)                                                      |
| **Email Worker**       | (Not in scope/scan)        | UNKNOWN          | N/A      | Verify worker uses `NotificationCryptoService`                   |
| **Admin Identity**     | `AdminController` (Direct) | **NO**           | **HIGH** | Refactor `AdminController` to use `AdminIdentifierCryptoService` |
| **TOTP Storage**       | `FileTotpSecretRepository` | **NO**           | MED      | Migrate to DB + `TotpSecretCryptoService`                        |

## 9) Orphans / Duplicates / Split-Brain Alerts
- **Duplicate DTO:** `App\Domain\DTO\Crypto\EncryptedPayloadDTO` vs `App\Application\Crypto\DTO\EncryptedPayloadDTO`.
- **Orphaned Service:** `AdminIdentifierCryptoService` is wired in Container but unused by consumers.
- **Split Brain:** Admin Emails are written using `EMAIL_ENCRYPTION_KEY` but `AdminIdentifierCryptoService` expects `CRYPTO_KEYS` derived keys.

## 10) Action Checklist (lowest-risk-first)
1. **Consolidate DTOs:** Remove `App\Domain\DTO\Crypto\EncryptedPayloadDTO` and update references to `App\Application\Crypto\DTO\EncryptedPayloadDTO`.
2. **Activate Admin Service:** Inject `AdminIdentifierCryptoService` into `AdminController` and implement dual-write (Legacy + Modern).
3. **Migrate Readers:** Update `PdoAdminQueryReader` to try `AdminIdentifierCryptoService` decryption before falling back to legacy.

## Appendix A — Full File Path Index
- `app/Application/Crypto/AdminIdentifierCryptoServiceInterface.php`
- `app/Application/Crypto/DTO/EncryptedPayloadDTO.php`
- `app/Application/Crypto/NotificationCryptoServiceInterface.php`
- `app/Application/Crypto/PasswordCryptoServiceInterface.php`
- `app/Application/Crypto/TotpSecretCryptoServiceInterface.php`
- `app/Bootstrap/Container.php`
- `app/Domain/DTO/Crypto/EncryptedPayloadDTO.php`
- `app/Domain/Service/PasswordService.php`
- `app/Http/Controllers/AdminController.php`
- `app/Infrastructure/Crypto/AdminIdentifierCryptoService.php`
- `app/Infrastructure/Crypto/NotificationCryptoService.php`
- `app/Infrastructure/Crypto/PasswordCryptoService.php`
- `app/Infrastructure/Crypto/TotpSecretCryptoService.php`
- `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`
- `app/Infrastructure/Reader/Session/PdoSessionListReader.php`
- `app/Modules/Crypto/DX/CryptoProvider.php`
- `app/Modules/Crypto/HKDF/HKDFService.php`
- `app/Modules/Crypto/KeyRotation/KeyRotationService.php`
- `app/Modules/Crypto/Password/PasswordHasher.php`
- `app/Modules/Crypto/Reversible/Algorithms/Aes256GcmAlgorithm.php`
- `app/Modules/Email/Queue/PdoEmailQueueWriter.php`
