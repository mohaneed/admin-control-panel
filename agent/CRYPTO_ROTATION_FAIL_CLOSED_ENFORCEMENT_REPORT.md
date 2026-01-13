# CRYPTO ROTATION FAIL-CLOSED ENFORCEMENT REPORT

## 1. Executive Summary
The application has been hardened to enforce **modern key rotation** as the only supported operational mode. The legacy single-key mode (`EMAIL_ENCRYPTION_KEY` as the sole source of truth) has been deprecated and is now only permitted for specific legacy migration paths. The system now **fails to boot** if the modern `CRYPTO_KEYS` configuration is missing or invalid.

## 2. Changes Implemented

### A. Fail-Closed Startup (Container.php)
- **Strict Validation:** The DI Container now strictly validates `CRYPTO_KEYS` and `CRYPTO_ACTIVE_KEY_ID` at startup.
- **JSON Enforcement:** `CRYPTO_KEYS` must be a valid JSON array of objects with `id` and `key`.
- **Active Key Check:** The `CRYPTO_ACTIVE_KEY_ID` must exist within the `CRYPTO_KEYS` array.
- **Single Active Key:** The system enforces exactly one active key.
- **Outcome:** If any of these conditions are not met, the application throws an exception and halts immediately.

### B. Legacy Fallback Removal
- **Removed:** The fallback logic that automatically wrapped `EMAIL_ENCRYPTION_KEY` into a pseudo-rotation provider has been removed.
- **Impact:** `CRYPTO_KEYS` is now mandatory. `EMAIL_ENCRYPTION_KEY` is retained *only* for legacy readers (`AdminController`, `PdoAdminQueryReader`) to decrypt old data during the migration phase.

### C. Repository Guardrails
- **New Script:** `scripts/ci/forbid-legacy-crypto.sh`
- **Function:** Scans the `app/` directory for forbidden usage of:
    - `openssl_encrypt` / `openssl_decrypt`
    - `EMAIL_ENCRYPTION_KEY`
- **Allowlist:** Only specific legacy files (e.g., `AdminController.php`) are allowed to bypass this check.
- **Integration:** Added as `composer lint:crypto`.

### D. Environment Canonicalization
- **.env.example:** Updated to reflect that `CRYPTO_KEYS` is required and `EMAIL_ENCRYPTION_KEY` is deprecated/migration-only.

## 3. Verification Steps

### Manual Verification
To verify the fail-closed behavior, you can temporarily modify your `.env` file:

1.  **Test Missing Keys:**
    - Comment out `CRYPTO_KEYS` in `.env`.
    - Run the app (or a script like `php scripts/email_worker.php`).
    - **Expected Result:** Exception: `CRYPTO_KEYS is required and cannot be empty.`

2.  **Test Invalid JSON:**
    - Set `CRYPTO_KEYS="invalid-json"`.
    - **Expected Result:** JSON decode error or exception.

3.  **Test Missing Active Key:**
    - Set `CRYPTO_ACTIVE_KEY_ID="non-existent-id"`.
    - **Expected Result:** Exception: `CRYPTO_ACTIVE_KEY_ID 'non-existent-id' not found in CRYPTO_KEYS.`

### CI Guardrail Verification
Run the following command to ensure no new legacy crypto usage has slipped in:

```bash
composer lint:crypto
```

**Expected Output:**
```
🔍 Scanning for forbidden legacy crypto usage...
   Checking for direct openssl_encrypt usage...
   Checking for direct openssl_decrypt usage...
   Checking for reference to legacy EMAIL_ENCRYPTION_KEY...
✅ SUCCESS: No forbidden legacy crypto usage found.
```

## 4. Next Steps
- **Migration:** Proceed with the migration of `AdminController` and `PdoAdminQueryReader` to use `AdminIdentifierCryptoService`.
- **Cleanup:** Once migration is complete, remove the allowed files from the guardrail script and delete `EMAIL_ENCRYPTION_KEY` entirely.
