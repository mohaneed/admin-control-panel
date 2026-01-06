
# 👑 0️⃣ System Ownership & Script-Driven Bootstrap

## System Ownership Model

* **Explicit Ownership**: Ownership is assigned strictly via the `system_ownership` table (one row max).
* **Script-Driven Only**: Ownership is assigned **ONLY** by the first-admin creation script (`scripts/bootstrap_admin.php`).
* **Immutable**: Once assigned, ownership cannot be transferred or reassigned via the script.
* **Global Bypass**: The system owner bypasses all authorization checks (`checkPermission` returns immediately).

## Why This Model?

* **No Auto-Detection**: We do not detect "admin count = 1" or "admin ID = 1".
* **No Database Flags**: We do not pollute the `admins` table with `is_super_admin` flags.
* **Deterministic**: The bootstrap script is the single authority for creating the owner.
* **Strict Authorization**: Authorization remains enabled and strict for everyone else.

---
