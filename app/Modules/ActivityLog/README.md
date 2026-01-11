# Activity Log

Lightweight, non-authoritative activity logging library designed for
**user actions, UI events, and business activities** that must **never**
affect system authority, security, or control flow.

This library is intentionally **fail-open** and **side-effect only**.

---

## 🎯 Purpose

Activity Log is used to record **what happened** in the system for:

- UX history
- Admin visibility
- Debugging
- Operational insights

It is **NOT** used for:

- Security decisions
- Authorization
- Auditing authority changes
- Enforcement or blocking logic

---

## 🔒 What This Is NOT

| Concern                       | Use This Library? |
|-------------------------------|-------------------|
| Security Events               | ❌ No              |
| Permission Changes            | ❌ No              |
| Authentication / Login Audits | ❌ No              |
| Legal / Compliance Audits     | ❌ No              |
| Fail-closed operations        | ❌ No              |

For those, use a **dedicated Audit or Security Log system**.

---

## 🧠 Design Principles

- **Fail-Open**  
  Logging failures must never break user flow.

- **Side-Effect Only**  
  No return values, no control decisions.

- **Explicit Intent**  
  Activity meaning is defined by canonical action strings or enums.

- **Driver-Based Persistence**  
  Storage is abstracted via `ActivityLogWriterInterface`.

---

## 📦 Architecture Overview

```

ActivityLogService
│
▼
ActivityLogWriterInterface
│
├── MySQLActivityLogWriter
├── (Future: MongoDB, Queue, File, etc.)

```

---

## 🧩 Core Components

### ActivityLogService
Main entry point used by application code.

### ActivityLogDTO
Immutable data carrier describing a single activity.

### ActivityLogWriterInterface
Contract for persistence drivers.

### Drivers
Concrete implementations (e.g. MySQL).

---

## 🏷️ Activity Actions

Actions can be provided as:

- **Enum implementing `ActivityActionInterface`**
- **Plain string** (fallback / custom actions)

Example canonical action:
```

admin.user.update

```

---

## 🧪 Testing

The library is fully testable using:

- Fake writers for unit tests
- Real drivers for integration tests

---

## 📄 License

MIT (or project license)

---

## ✨ Status

- Stable
- Extractable as standalone library
- No framework dependency
