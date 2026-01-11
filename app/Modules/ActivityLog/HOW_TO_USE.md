# How To Use Activity Log

This guide shows **how to use Activity Log safely and correctly**.

---

## 1️⃣ Basic Usage

### Inject the Service

```php
use App\Modules\ActivityLog\Service\ActivityLogService;

final class UserService
{
    public function __construct(
        private ActivityLogService $activityLog,
    ) {}
}
````

---

### Log an Activity

```php
$this->activityLog->log(
    action    : 'admin.user.update',
    actorType : 'admin',
    actorId   : 1,
    entityType: 'user',
    entityId  : 42,
    metadata  : ['changed' => ['email']],
);
```

---

## 2️⃣ Using Enums (Recommended)

Define canonical actions:

```php
enum CoreActivityAction: string implements ActivityActionInterface
{
    case ADMIN_USER_UPDATE = 'admin.user.update';

    public function toString(): string
    {
        return $this->value;
    }
}
```

Usage:

```php
$this->activityLog->log(
    action    : CoreActivityAction::ADMIN_USER_UPDATE,
    actorType : 'admin',
    actorId   : 1,
    entityType: 'user',
    entityId  : 42,
);
```

---

## 3️⃣ Metadata Guidelines

Metadata should be:

✔️ Contextual
✔️ Non-sensitive
✔️ Serializable

Good example:

```php
metadata: [
    'fields' => ['email', 'status'],
    'source' => 'admin_panel'
]
```

❌ Do NOT store:

* Passwords
* Tokens
* Secrets
* Full request payloads

---

## 4️⃣ Fail-Open Behavior (IMPORTANT)

Activity Log **never throws** to the caller.

Internally:

* Writer failures are swallowed
* User flow continues

This is **intentional**.

If you need guaranteed persistence, use **Audit Logs instead**.

---

## 5️⃣ Static / Legacy Usage (Optional)

For legacy or static contexts:

```php
use App\Modules\ActivityLog\Traits\ActivityLogStaticTrait;

ActivityLogStaticTrait::setActivityLogService($service);

self::logActivityStatic(
    action    : 'system.bootstrap',
    actorType : 'system',
    actorId   : null,
);
```

⚠️ This requires explicit bootstrap initialization.

---

## 6️⃣ Database (MySQL Driver)

Schema example:

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_type VARCHAR(32),
    actor_id BIGINT,
    action VARCHAR(128),
    entity_type VARCHAR(64),
    entity_id BIGINT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    request_id VARCHAR(64),
    occurred_at DATETIME(6)
);
```

---

## 7️⃣ When NOT to Use Activity Log

❌ Security decisions
❌ Authorization checks
❌ Compliance / audit trails
❌ Transaction control

Use **Audit Logs** for those cases.

---

## 8️⃣ Summary

Activity Log answers one question:

> **"What happened?"**

It must never answer:

> **"Should this be allowed?"**

Keep that separation strict.
