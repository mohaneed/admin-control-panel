# PASSWORD PEPPER GOVERNANCE REPORT

## 1) Current State Inventory (AS-IS)

### Peppering & Hashing Logic
**File:** `app/Domain/Service/PasswordService.php`

*   **Peppering Step:** HMAC-SHA256
    ```php
    $peppered = hash_hmac('sha256', $plain, $this->pepper);
    ```
*   **Hashing Algorithm:** Argon2id
    ```php
    password_hash($peppered, PASSWORD_ARGON2ID);
    ```
*   **Verification Logic:**
    1.  **Primary:** HMAC-SHA256 with `PASSWORD_PEPPER` + `password_verify` (Argon2id).
    2.  **Secondary (Rotation):** HMAC-SHA256 with `PASSWORD_PEPPER_OLD` + `password_verify` (Argon2id).
    3.  **Legacy Fallback:** Plain `password_verify` (no pepper) - likely for migration from older systems.

### Configuration & Environment
**File:** `app/Bootstrap/Container.php`

*   **Environment Variables:**
    *   `PASSWORD_PEPPER` (Required)
    *   `PASSWORD_PEPPER_OLD` (Optional)
*   **Injection:**
    *   `AdminConfigDTO` captures these from `$_ENV`.
    *   `PasswordService` is constructed with both values.

### Database Schema
**File:** `database/schema.sql`
**Table:** `admin_passwords`

*   **Columns:**
    *   `admin_id` (INT, PK)
    *   `password_hash` (VARCHAR(255))
    *   `created_at` (DATETIME)
*   **Findings:**
    *   **No Pepper ID:** There is no column storing which pepper version was used for a specific hash.
    *   **No Versioning:** The schema relies entirely on trial-and-error verification (try current, try old, try none).

### Upgrade-on-Login
**File:** `app/Domain/Service/PasswordService.php`

*   **Status:** **MISSING**
*   The `verify()` method returns `true` or `false`. It does **not** signal that a rehash is needed if the "Old Pepper" or "Legacy Fallback" path was successful.
*   Consequently, users verified with `PASSWORD_PEPPER_OLD` remain on the old pepper indefinitely until they manually change their password.

---

## 2) Risks & Gaps

1.  **No Automatic Rotation (Upgrade-on-Login Missing):**
    *   If you rotate the pepper (move Current -> Old, New -> Current), users can still login, but their hashes are **never updated** to the new pepper.
    *   This defeats the purpose of rotation, as the "Old Pepper" must remain active forever to support those users.

2.  **Trial-and-Error Verification:**
    *   The system tries up to 3 expensive verification operations per login attempt (Current, Old, Legacy).
    *   While Argon2id is resistant to timing attacks, the *difference* in execution time between 1 vs 3 checks could theoretically leak which generation a user's hash belongs to (though low risk).

3.  **Fail-Closed Behavior:**
    *   **Good:** `Container.php` throws an exception if `PASSWORD_PEPPER` is missing or empty.
    *   **Good:** `PasswordService` throws `RuntimeException` if constructed with an empty pepper.

4.  **Split-Brain Risk:**
    *   None found. `PasswordService` appears to be the single source of truth for password operations.

---

## 3) Recommended Best Scenario (TARGET)

### Option A: Minimal Change (Dual Pepper + Rehash Signal)
*Best for immediate hardening without schema changes.*

1.  **Modify `PasswordService::verify`** to return an Enum or DTO:
    *   `Result::SUCCESS_CURRENT`
    *   `Result::SUCCESS_NEEDS_REHASH` (if Old or Legacy path worked)
    *   `Result::FAILURE`
2.  **Update `AdminAuthenticationService`**:
    *   If `SUCCESS_NEEDS_REHASH` is returned, immediately calculate the new hash (using Current Pepper) and update the DB.
3.  **Retirement:**
    *   Monitor logs for "Rehash" events. Once they drop to zero, `PASSWORD_PEPPER_OLD` can be removed.

### Option B: Best Long-Term (Pepper ID + Schema Change)
*Best for strict governance and performance.*

1.  **Schema Change:**
    *   Add `pepper_id` (VARCHAR/INT) to `admin_passwords`.
2.  **Environment:**
    *   `PASSWORD_PEPPERS`: JSON map `{"v1": "secret...", "v2": "secret..."}`
    *   `PASSWORD_ACTIVE_PEPPER_ID`: "v2"
3.  **Logic:**
    *   **Hash:** Always use `PASSWORD_ACTIVE_PEPPER_ID`. Store "v2" in DB.
    *   **Verify:** Look up `pepper_id` from DB. Use *only* that pepper.
    *   **Rotate:** If stored `pepper_id` != `PASSWORD_ACTIVE_PEPPER_ID`, rehash after successful login.
4.  **Benefits:**
    *   Deterministic verification (1 check).
    *   Clear audit trail of which key protects which user.

---

## 4) Fail-Closed Rules

The following checks must exist at startup (`Container.php` or `AdminConfigDTO`):

1.  **`PASSWORD_PEPPER` Presence:**
    *   Must be non-empty.
    *   **Current Status:** Enforced in `Container.php` (`$dotenv->required(...)`).

2.  **`PASSWORD_PEPPER` Complexity (Recommended):**
    *   Should enforce minimum length (e.g., 32 chars).
    *   **Current Status:** Not enforced.

3.  **`PASSWORD_PEPPER_OLD` Validity:**
    *   If set, must not be equal to `PASSWORD_PEPPER`.
    *   **Current Status:** Not enforced.

---

## 5) Definition of Done

*   [ ] **Upgrade-on-Login Implemented:** Users authenticating with `PASSWORD_PEPPER_OLD` are automatically migrated to `PASSWORD_PEPPER`.
*   [ ] **Rehash Logic Verified:** Unit tests confirm that `verify()` correctly identifies when a rehash is required.
*   [ ] **Fail-Closed Verified:** Application refuses to boot if `PASSWORD_PEPPER` is missing (Already done).
*   [ ] **Rotation Procedure Documented:** Clear steps on how to promote New -> Current -> Old.

### Verification Commands
```bash
# Check for missing pepper env
grep "PASSWORD_PEPPER" .env

# Verify PasswordService logic (if tests exist)
./vendor/bin/phpunit --filter PasswordService
```
