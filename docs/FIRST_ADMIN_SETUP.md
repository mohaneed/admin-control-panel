# 🔑 First Administrator Setup (Bootstrap)

This document explains how to create the **first administrator**
for a fresh installation of the **Admin Control Panel**.

This step is **required once** and is intentionally explicit.

---

## 🚨 Important Concept (Read First)

When the system is installed for the first time:

* ❌ No admin users exist
* ❌ No one can log in
* ❌ There is no public registration

This is **by design**.

The system starts in a **LOCKED** state to prevent insecure deployments.

---

## 🧠 What Is Bootstrap?

**Bootstrap** is a one-time process used to:

* Create the **first administrator**
* Establish explicit system ownership
* Activate the system securely

After bootstrap succeeds:

* The system becomes active
* Bootstrap mode is permanently disabled

---

## ✅ Prerequisites

Before starting, make sure:

1. The project is installed
2. Dependencies are installed (`composer install`)
3. The database is created
4. The SQL schema is imported
5. The local server is running

Example:

```bash
php -S localhost:8080 -t public
```

If you open:

```
http://localhost:8080
```

and see a message like:

* *System Locked*
* *Bootstrap Required*

That is **expected and correct**.

---

## 🟢 Step 1: Generate Bootstrap Token (CLI)

> ⚠️ This step must be performed by a **responsible operator**
> (owner, lead developer, or system administrator).

From the project root directory, run the **bootstrap CLI command**
provided by the project.

The command will:

* Generate a **one-time bootstrap token**
* Store only a hashed version in the database
* Set a limited lifetime (TTL)

After execution, a token will be printed in the terminal.

Example (format only):

```
BOOTSTRAP TOKEN:
a9f3c1e2-xxxx-xxxx-xxxx-xxxxxxxx
```

📌 This token:

* Can be used **once**
* Expires automatically
* Must not be stored or reused

---

## 🟢 Step 2: Enter the Token in the Browser

1. Open your browser:

   ```
   http://localhost:8080
   ```
2. You will see a page asking for a **Bootstrap Token**
3. Paste the token generated in Step 1
4. Click **Continue**

If the token is valid, you will proceed to the admin creation screen.

---

## 🟢 Step 3: Create the First Administrator

You will be asked to provide:

* Email address
* Password

The system will automatically assign the highest initial role
(e.g. system owner).

📌 There is **no alternative way** to create the first admin.

---

## 🟢 Step 4: Enable Two-Factor Authentication (Required)

Before access is granted, you must enable **TOTP (2FA)**:

1. Install Google Authenticator (or compatible app)
2. Scan the displayed QR code
3. Enter the generated verification code

This step is **mandatory**.

Without 2FA, the setup cannot be completed.

---

## 🎉 Completion

After successful setup:

* The first admin is created
* Bootstrap mode is permanently disabled
* The bootstrap token is invalidated
* The system becomes fully active

You can now log in normally.

---

## ❌ What Is NOT Allowed

* ❌ Creating admins directly in the database
* ❌ Re-running bootstrap after success
* ❌ Adding a registration endpoint
* ❌ Bypassing the bootstrap process

Any attempt to do so is considered a security violation.

---

## 🧾 Quick Summary

1. Run the bootstrap CLI command
2. Copy the one-time token
3. Enter it in the browser
4. Create the first admin
5. Enable 2FA
6. Done

---

## 🛡️ Final Note

If the system asks for bootstrap,
it is **working correctly**.

Bootstrap is not a convenience feature —
it is a security guarantee.

---

✔️ **End of First Administrator Setup Guide**

---
