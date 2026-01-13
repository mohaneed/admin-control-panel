# AdminConfigDTO Secret Removal Report

## 1. Inventory Findings
The initial inventory confirmed that `AdminConfigDTO` was acting as a carrier for sensitive secrets, including:
- `cryptoKeys` (Raw array of crypto keys)
- `passwordPeppers` (Raw array of password peppers)
- `emailBlindIndexKey` (Raw blind index key)
- `emailEncryptionKey` (Raw encryption key)
- `dbPass` (Database password)

These fields were being accessed in:
- `Container.php` (for service configuration)
- `AdminController.php` (for manual encryption/decryption)
- `PdoAdminQueryReader.php` (for manual decryption)
- `PdoSessionListReader.php` (for manual decryption)
- `RecoveryStateService.php` (for environment checks)
- `bootstrap_admin.php` (for initial setup)

No endpoint was found to return `AdminConfigDTO` directly to the frontend.

## 2. Chosen Option: Option A (Preferred)
**AdminConfigDTO becomes UI-safe only (no secrets ever).**

We chose this option because:
- It strictly separates configuration from secrets.
- It prevents accidental exposure of secrets via `var_dump`, logging, or future API endpoints.
- It forces services to request secrets explicitly via DI or dedicated config objects (`CryptoKeyRingConfig`, `PasswordPepperRingConfig`), or better yet, via the `CryptoProvider` abstraction (though full migration to `CryptoProvider` is out of scope for this specific task, we at least removed the secrets from the DTO).

## 3. Refactoring Execution

### A. AdminConfigDTO Update
Removed the following fields from `AdminConfigDTO`:
- `passwordPeppers`
- `emailBlindIndexKey`
- `emailEncryptionKey`
- `dbPass`
- `cryptoKeys`

Added safe flags:
- `hasCryptoKeyRing` (bool)
- `hasPasswordPepperRing` (bool)

### B. Container Update
Updated `app/Bootstrap/Container.php` to:
1.  Inject secrets directly from `$_ENV` into services that require them, instead of passing them through `AdminConfigDTO`.
    - `PDOFactory` receives `DB_PASS` directly.
    - `AdminController` receives `EMAIL_BLIND_INDEX_KEY` and `EMAIL_ENCRYPTION_KEY` directly.
    - `PdoAdminQueryReader` receives keys directly.
    - `PdoSessionListReader` receives keys directly.
    - `RecoveryStateService` receives `EMAIL_BLIND_INDEX_KEY` directly.
    - `AuthController`, `LoginController`, `EmailVerificationController` receive keys directly.
2.  Configure `AdminConfigDTO` without secrets.

### C. Service Updates
Updated the following classes to accept keys via constructor injection instead of extracting them from `AdminConfigDTO`:
- `App\Http\Controllers\AdminController`
- `App\Infrastructure\Reader\Admin\PdoAdminQueryReader`
- `App\Infrastructure\Reader\Session\PdoSessionListReader`
- `App\Domain\Service\RecoveryStateService`
- `Tests\Canonical\Admins\AdminsQueryContractTest` (Updated mocks)

### D. Bootstrap Script
Updated `scripts/bootstrap_admin.php` to read secrets from `$_ENV` instead of `AdminConfigDTO`.

## 4. Verification Results

### Grep Search for Secrets in Codebase
Ran the following searches to ensure no lingering references to secrets on `AdminConfigDTO`:

- `grep "->cryptoKeys" .` -> **0 hits** (Clean)
- `grep "->passwordPeppers" .` -> **0 hits** (Clean)
- `grep "->emailBlindIndexKey" .` -> **0 hits** (Clean)
- `grep "->emailEncryptionKey" .` -> **0 hits** (Clean)
- `grep "->dbPass" .` -> **0 hits** (Clean)

(Note: Hits in `agent/` folder are expected as they are reports/inventories).

### Fail-Closed Verification
The `Container.php` still enforces `dotenv->required(...)->notEmpty()`, ensuring the application will not boot if secrets are missing from the environment.

## 5. Conclusion
The refactor is complete. `AdminConfigDTO` is now safe to be passed around without risking secret leakage. All secrets are injected directly into the services that need them, reducing the attack surface and improving code clarity.
