# SECURITY / CRYPTO EXECUTION AUDIT REPORT

## 1) Executive Snapshot (Facts only)

*   **Reversible Encryption:** The application currently runs **two parallel, incompatible encryption systems**.
    *   **System A (Identity):** Used for Admin Emails. Relies on direct `openssl` calls, a single static key, and stores data as a packed Base64 string.
    *   **System B (Queue):** Used for Email Queue. Relies on the `ReversibleCryptoService` pipeline, HKDF key derivation, and stores data in split columns (Cipher/IV/Tag/KeyID).
*   **Key Rotation:**
    *   **System A:** **Non-existent.** No `key_id` is stored; changing the key renders data permanently inaccessible.
    *   **System B:** **Fully implemented.** Supports active/inactive keys and stores `key_id` alongside ciphertext.
*   **Password Hashing:**
    *   **Active Service:** `App\Domain\Service\PasswordService` (Simple implementation).
    *   **Inactive Service:** `App\Modules\Crypto\Password\PasswordHasher` (Robust implementation) is present in the codebase but **not wired** into the authentication flow.
    *   **Algorithm:** `HMAC-SHA256` (Pepper) → `Argon2id`.

## 2) Reversible Crypto Execution Flow

### System A: Identity Encryption (Legacy)
*   **Authoritative Entry Point:** `App\Http\Controllers\AdminController::addEmail` and `scripts/bootstrap_admin.php`.
*   **Key Source:** `$_ENV['EMAIL_ENCRYPTION_KEY']` (via `AdminConfigDTO`).
*   **Derivation:** **None.** The raw key is used directly.
*   **Execution:** Direct calls to `openssl_encrypt` / `openssl_decrypt` (AES-256-GCM).
*   **Storage:** Single column `email_encrypted` containing `base64_encode(IV . Tag . Ciphertext)`.
*   **Key Selection:** Hardcoded to the single environment variable.

### System B: Queue Encryption (Modern)
*   **Authoritative Entry Point:** `App\Modules\Email\Queue\PdoEmailQueueWriter`.
*   **Key Source:** `$_ENV['CRYPTO_KEYS']` (JSON array) managed by `KeyRotationService`.
*   **Derivation:** **HKDF-SHA256** via `CryptoContextFactory`.
    *   Contexts: `email:recipient:v1` and `email:payload:v1`.
*   **Execution:** `ReversibleCryptoService` delegating to `Aes256GcmAlgorithm`.
*   **Storage:** Split columns: `recipient_encrypted`, `recipient_iv`, `recipient_tag`, `recipient_key_id`.
*   **Key Selection:** Automatically selects the `ACTIVE` key for encryption; uses stored `key_id` for decryption.

## 3) Key Rotation Mechanics (Actual)

*   **Service:** `App\Modules\Crypto\KeyRotation\KeyRotationService`.
*   **Policy:** `StrictSingleActiveKeyPolicy`.
    *   Enforces exactly **one** key with status `ACTIVE`.
    *   Allows decryption with `ACTIVE`, `INACTIVE`, or `RETIRED` keys.
*   **Key ID Generation:** Defined manually in the `CRYPTO_KEYS` JSON configuration in `.env`.
*   **Rotation Logic:**
    *   **Manual:** Rotation is triggered by updating the `CRYPTO_ACTIVE_KEY_ID` environment variable.
    *   **Scope:** Only affects **System B (Queue)**.
    *   **Bypass:** **System A (Identity)** completely bypasses this service and cannot rotate keys.

## 4) Password Hashing Execution Flow

*   **Authoritative Service:** `App\Domain\Service\PasswordService`.
    *   *Note: The more advanced `App\Modules\Crypto\Password\PasswordHasher` is unused.*
*   **Sequence:**
    1.  **Pepper:** `hash_hmac('sha256', $plaintext, $pepper)`
    2.  **Hash:** `password_hash($peppered, PASSWORD_ARGON2ID)`
*   **Pepper Source:** `$_ENV['PASSWORD_PEPPER']` (Static).
*   **Verification Logic:**
    1.  **Primary:** Verify using `PASSWORD_PEPPER`.
    2.  **Rotation:** If failed, verify using `PASSWORD_PEPPER_OLD` (if configured).
    3.  **Legacy Fallback:** If failed, verify using raw plaintext (no pepper).

## 5) Implicit Assumptions & Hidden Couplings

*   **Assumption:** The `admin_emails` table structure assumes a single blob storage format, preventing the adoption of the standard `ReversibleCryptoService` (which requires split columns) without schema migration.
*   **Coupling:** `AdminController` is tightly coupled to `AdminConfigDTO` properties (`emailEncryptionKey`), bypassing the centralized `CryptoProvider`.
*   **Dead Code:** The entire `App\Modules\Crypto\Password` namespace (including `PasswordHasher`, `ArgonPolicyDTO`) appears to be dead code, as the DI container wires `App\Domain\Service\PasswordService` instead.

## 6) Open Questions (Based on missing code paths)

*   **Migration:** There is no visible code path or script to migrate existing `admin_emails` from System A (Legacy) to System B (Key Rotation aware).
*   **Rehashing:** `PasswordService::verify` does not call `password_needs_rehash` or trigger a re-hash upon successful verification with a legacy/old pepper, meaning users are not automatically migrated to the latest security parameters.
*   **Consistency:** Why does `bootstrap_admin.php` manually implement encryption logic instead of reusing a service, creating a risk of implementation drift?
