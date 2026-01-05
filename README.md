# 🛡️ Admin Control Panel

**A security-first administrative control panel designed for serious environments.**

This project provides a **backend-heavy, infrastructure-grade admin system**
with a strong focus on **security, auditability, and explicit control**.

It is **not** a UI framework, **not** a CMS, and **not** a quick demo panel.

---

## ✨ What This Project Is

* A secure administrative backend
* Designed for production-grade architecture
* Explicit ownership and access control
* Zero-trust by default
* Open source and auditable

---

## 🚫 What This Project Is NOT

* ❌ No default admin users
* ❌ No public registration
* ❌ No demo credentials
* ❌ No feature-first shortcuts
* ❌ No insecure “dev mode”

If you are looking for a fast prototype or a UI-first admin panel,
this project is **not** the right choice.

---

## ⚠️ Project Status (Important)

🚧 **This project is currently under active development.**

Although the core architecture and security model are implemented,
the system is **not yet considered complete for general use**.

**Usage is not officially supported until Version 2.0.**

Before v2.0:

* Some features are incomplete
* Some flows are still evolving
* The system should be evaluated, not deployed

📌 Attempting to use this project in real environments **before v2.0**
will result in an **incomplete experience**.

---

## 🔒 Security-First by Design

This system is intentionally strict:

* Ownership must be **explicit**
* Privileges are **never implicit**
* Sensitive actions are **audited**
* Convenience never overrides security

Some steps may feel manual — that is intentional.

For details, see:

```
SECURITY.md
```

---

## 🚪 Initial State (Locked)

When you first run the system:

* No admin users exist
* No one can log in
* The system is locked by design

This prevents:

* Accidental exposure
* Forgotten default passwords
* Unsafe deployments

---

## 🔑 First Administrator (Bootstrap)

To activate the system, **one initial administrator** must be created
using a **one-time bootstrap process**.

Key points:

* Happens **once**
* Requires deliberate action
* Uses a one-time token
* Cannot be repeated

This ensures system ownership is intentional.

📘 **Step-by-step instructions:**

```
docs/FIRST_ADMIN_SETUP.md
```

---

## 🚀 Quick Start (High-Level)

> ⚠️ This is a **high-level overview only**.
> The system is still under development and **not production-ready before v2.0**.

1. Clone the repository
2. Install PHP dependencies with Composer
3. Create a database and import the provided SQL schema
4. Copy `.env.example` → `.env` and configure database access
5. Start the local PHP server
6. Perform the one-time bootstrap to create the first admin

For full details, see:

```
docs/ONBOARDING.md
```

---

## 🧰 Documentation

| File                        | Purpose                            |
|-----------------------------|------------------------------------|
| `README.md`                 | Project overview & status          |
| `docs/ONBOARDING.md`        | Full setup & usage guide           |
| `docs/FIRST_ADMIN_SETUP.md` | First admin bootstrap process      |
| `SECURITY.md`               | Security model & policy            |
| `.env.example`              | Environment configuration template |

---

## 🧑‍💻 Frontend / UI Work

* Views are implemented using **Twig**
* UI developers can work safely without modifying security logic
* Authorization and identity are enforced server-side

All UI work should follow the rules described in:

```
docs/ONBOARDING.md
```

---

## 📦 Open Source Usage

This project is open source to enable:

* Transparency
* Review
* Trust
* Reuse in serious systems

Open source **does not** mean production-ready by default.

---

## 🐞 Security Issues

If you discover a security vulnerability:

* ❌ Do not open a public issue
* ❌ Do not publish exploit details

Please follow the responsible disclosure guidelines in:

```
SECURITY.md
```

---

## ✅ Final Notes

This project is designed to protect systems
**even from operator mistakes**.

If something feels strict or inconvenient,
it is likely doing its job.

---

✔️ **Production-grade architecture — functional usage starts at v2.0**

---
