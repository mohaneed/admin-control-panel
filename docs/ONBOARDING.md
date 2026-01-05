# Onboarding Guide

**Project:** Admin Control Panel
**Status:** Current State — Infrastructure-First
**Stack:** PHP 8.2 (Slim Framework) + Twig + MySQL
**Audience:** Backend Developers & Frontend (Twig) Developers

---

## 1️⃣ Executive Overview

This project is a **backend-heavy administrative system** built with a
**Security-First / Zero-Trust** architecture.

It is **not** a UI-first or feature-first project.

### What is this project?

A centralized admin system designed for sensitive environments
(financial, enterprise, internal systems), based on:

* Zero-Trust (no implicit trust after login)
* Multi-layer middleware pipeline
* Server-side session authority
* Step-up authentication for sensitive actions
* Mandatory audit logging for critical operations

### ⚠️ Important Warning

> Any change made without fully understanding this document
> introduces **high security risk**.
>
> The complexity in this system is **intentional**.
> Do not attempt to “simplify” or bypass it.

---

## 2️⃣ GitHub Workflow

To keep the codebase stable and auditable, follow these rules strictly.

1. **Clone**

```bash
git clone <repository-url>
```

2. **Branching Strategy**

* Features: `feature/your-feature-name`
* Fixes: `fix/issue-description`

3. **Pull Safety**

```bash
git pull origin main --rebase
```

4. **Strictly Forbidden**

* ❌ Direct push to `main`
* ❌ Merge without review

5. **Clean History**

* Squash commits before merge
* Clean git history = easier debugging & auditing

---

## 3️⃣ Local Setup

### Requirements

* PHP 8.2+
* Extensions:

    * pdo
    * openssl
    * redis
    * mbstring
* Composer

---

### Setup Steps

#### 1️⃣ Install Dependencies

```bash
composer install
```

#### 2️⃣ Environment Configuration

* Copy:

  ```
  .env.example → .env
  ```
* Configure database connection values.

### 🔐 Security Note About Keys

```
All cryptographic keys must be generated using a secure random source (CSPRNG).
❌ Do NOT use passwords or manual strings.
If unsure, ask the core team.
```

---

## 4️⃣ Database Setup (SQL Import) — **REQUIRED**

Before running the system, the database **must be created and initialized**.

### SQL Schema File

* The project includes an official SQL schema file
* It contains:

    * Tables
    * Constraints
    * Indexes
* It does NOT contain:

    * Default admins
    * Seed data
    * Demo accounts

---

### Import Methods

#### Using phpMyAdmin

1. Create an empty database (e.g. `admin_control_panel`)
2. Open phpMyAdmin
3. Select the database
4. Click **Import**
5. Choose the `.sql` file
6. Execute

#### Using CLI (optional)

```bash
mysql -u USER -p DB_NAME < schema.sql
```

### 📌 Important

After import:

* The database will contain **no admin users**
* The system will remain **LOCKED**
* This behavior is **expected and correct**

---

## 5️⃣ First Admin Creation (Bootstrap) — **CRITICAL**

> ⚠️ This is the most important step in the entire system.

The system always starts in:

```
BOOTSTRAP_REQUIRED
```

* ❌ No default admin exists
* ❌ No registration endpoint exists
* ❌ Admins must NOT be created via SQL

---

### The Only Correct Method

#### 1️⃣ Generate Bootstrap Token (CLI)

* A **dedicated CLI command** exists in the project
* The command:

    * Generates a one-time token
    * Applies a TTL
    * Stores only a hashed version in the database

> Do not guess or repeat this command without consulting the core team.

**Frontend developers:**
You are NOT required to execute this step.
You only need to understand that it happens once.

---

#### 2️⃣ Use the Token in the Browser

1. Open:

   ```
   http://localhost:8080
   ```
2. A page requesting a **Bootstrap Token** will appear
3. Enter the token generated via CLI

---

#### 3️⃣ Create the First Admin

After token validation:

* Enter email and password
* A fixed role is assigned (`system.owner`)
* TOTP (2FA) setup is **mandatory**

After completion:

* The token is invalidated permanently
* The system transitions:

```
LOCKED → ACTIVE
```

### ❌ Warning

```
Creating admins directly in the database is considered a backdoor
and will be detected by audit and security guards.
```

---

## 6️⃣ Database Access (phpMyAdmin)

### Important Tables

* `admins`
* `identifiers`
* `sessions`
* `audit_outbox`

### ❌ Golden Rule

```
Manual modification of security tables is considered tampering.
The system may:
- Invalidate sessions
- Block access
- Record a critical security audit event
```

---

## 7️⃣ Current API Endpoints (ONLY THESE)

### Authentication

```
POST /auth/login
POST /auth/logout
POST /auth/totp/verify
```

### Admins

```
GET  /admins
POST /admins
PUT  /admins/{id}
```

### Sessions

```
GET  /sessions
POST /sessions/{id}/revoke
```

📌 Notes:

* Any endpoint not listed here is unavailable
* This is NOT a final UI contract

---

## 8️⃣ Frontend & Twig — Safe Usage Rules

### Templates Location

```
templates/
```

### Strict Rules

1. Controller ≠ View
2. ❌ No security logic in Twig
3. Always escape output:

   ```twig
   {{ value|e }}
   ```
4. Use translation keys only
5. Do not assume DTO field order or presence

---

## 9️⃣ Golden Rules for All Developers

1. ❌ No manual auth logic
2. ❌ Do not change permission semantics
3. ❌ Session ≠ Identity
4. ❌ Do not expose `admin_id` in UI
5. ✅ When in doubt — ask

---

## 🔟 Current Phase Boundary

### **Infrastructure & Core Security**

**Completed**

* Login
* Sessions
* TOTP / Step-Up Authentication
* Transactional Audit Outbox

**Not Allowed Yet**

* Business logic
* Product-specific features

---

## ⚠️ Important Note for Frontend Developers (.env)

The `.env` file contains sensitive settings.

Frontend developers must:

* Change database connection values only
* NOT modify encryption keys
* NOT enable recovery or security flags

If something breaks — contact the core team.

---

## 🔚 Final Notes

This document is the **single source of truth** for running the project.

* Any conflict → this document is correct
* Any feature request → outside this guide

**Work carefully — the system will work with you 🔒**

---
