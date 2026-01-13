# Password Pepper Ring Cutover Report

## Overview
This report documents the successful cutover from a single legacy `PASSWORD_PEPPER` to a deterministic **Pepper Ring** architecture. This change enforces strict security boundaries, removes all legacy fallback modes, and ensures that every password hash is cryptographically bound to a specific pepper ID.

## Changes Implemented

### 1. Database Schema
- **Table:** `admin_passwords`
- **Change:** Added `pepper_id VARCHAR(16) NOT NULL` column.
- **Impact:** Every password record now explicitly stores which pepper was used to hash it.
- **Migration:** The schema file `database/schema.sql` has been updated. Since a DB reset is required, no backfill migration was created.

### 2. Environment Configuration
- **Removed:** `PASSWORD_PEPPER`, `PASSWORD_PEPPER_OLD`
- **Added:**
  - `PASSWORD_PEPPERS`: A JSON map of ID -> Secret (e.g., `{"p1": "secret1", "p2": "secret2"}`).
  - `PASSWORD_ACTIVE_PEPPER_ID`: The ID of the pepper to use for *new* hashes (e.g., `p1`).
- **Validation:** The application container now **fails to boot** if these variables are missing, invalid JSON, or if the active ID is not found in the map.

### 3. Password Service (Core Logic)
- **Hashing:** Now returns `['hash' => string, 'pepper_id' => string]`. Always uses `PASSWORD_ACTIVE_PEPPER_ID`.
- **Verification:** Now requires `pepper_id` as an argument. It looks up the secret from the ring using this ID.
- **Fail-Closed:** If the `pepper_id` stored in the DB is not found in the `PASSWORD_PEPPERS` config, verification fails immediately.
- **Legacy Removal:** All logic related to "try old pepper" or "try no pepper" has been deleted.
- **Defensive Validation:** `PasswordService` constructor now defensively validates that all pepper secrets meet the minimum length requirement (>= 32 chars), ensuring fail-closed behavior even if the container validation is bypassed.

### 4. Authentication Flow (Upgrade-on-Login)
- **Location:** `AdminAuthenticationService::login`
- **Logic:**
  1. Load password record (hash + pepper_id).
  2. Verify using the stored pepper_id.
  3. **Transactional Block Starts**:
     - If verification succeeds and `stored_pepper_id !== active_pepper_id`:
       - **Rehash** the password using the active pepper.
       - **Update** the DB record immediately.
     - **Create Session**.
     - **Commit Transaction**.
- **Benefit:** This allows for seamless key rotation in the future without requiring a global password reset.
- **Transactional Integrity:** Upgrade-on-login is now fully transactional with session creation. If session creation fails, the password upgrade is rolled back, ensuring atomic login mutation.

### 5. Bootstrapping
- The `scripts/bootstrap_admin.php` script has been updated to store the `pepper_id` when creating the initial admin user.

## Verification Steps

### Prerequisite
Ensure your `.env` file is updated:
```dotenv
PASSWORD_PEPPERS='{"v1":"YOUR_SECURE_SECRET_32_CHARS_MIN"}'
PASSWORD_ACTIVE_PEPPER_ID=v1
```

### 1. Fresh Install
1. Drop the existing database.
2. Import `database/schema.sql`.
3. Run `php scripts/bootstrap_admin.php`.
4. Verify the database row:
   ```sql
   SELECT * FROM admin_passwords;
   -- Should show a valid hash AND 'v1' in the pepper_id column.
   ```

### 2. Login Test
1. Attempt to login via the web UI or API.
2. Login should succeed.

### 3. Rotation & Upgrade Test (Manual)
1. Add a new pepper to `.env`: `PASSWORD_PEPPERS='{"v1":"...","v2":"NEW_SECRET"}'`
2. Change active pepper: `PASSWORD_ACTIVE_PEPPER_ID=v2`
3. Restart application (if necessary).
4. Login with the existing user (hashed with `v1`).
   - Login should **succeed** (because `v1` is still in the map).
5. Check the database:
   ```sql
   SELECT pepper_id FROM admin_passwords WHERE admin_id = ...;
   ```
   - The `pepper_id` should now be updated to `v2`.

## Files Changed
- `database/schema.sql`
- `.env.example`
- `app/Bootstrap/Container.php`
- `app/Domain/DTO/AdminConfigDTO.php`
- `app/Domain/DTO/AdminPasswordRecordDTO.php` (Created)
- `app/Domain/Contracts/AdminPasswordRepositoryInterface.php`
- `app/Infrastructure/Repository/AdminPasswordRepository.php`
- `app/Domain/Service/PasswordService.php`
- `app/Domain/Service/AdminAuthenticationService.php`
- `app/Infrastructure/Crypto/PasswordCryptoService.php`
- `scripts/bootstrap_admin.php`

## Conclusion
The system is now fully cut over to the Pepper Ring architecture. Legacy modes are gone, and the system is ready for secure, zero-downtime key rotation.
