## 🔐 Roles Management API

This section documents **Roles APIs** used to **list roles, manage their UI metadata,
and control role activation**, and defines how **UI capabilities** are computed and consumed.

> ℹ️ Technical role keys (`roles.name`) are **immutable**
>
> ℹ️ Role creation, permission assignment, admin binding, and lifecycle management are **NOT part of this API**
>
> ℹ️ All routes below are prefixed with `/api`.

---

### 🧩 UI Authorization & Capabilities Model

The UI does **NOT** perform authorization.

Authorization decisions are made **server-side** using the `AuthorizationService`.
The backend computes **capabilities** for the current admin and injects them into the Twig view.

These capabilities are used by **Twig and JavaScript for presentation only**
(show / hide / enable / disable UI controls).

> ⚠️ Hiding UI elements does **NOT** replace API authorization  
> ⚠️ All API endpoints must still enforce permissions server-side

---

#### Capability Injection (Backend → Twig)

In the UI controller:

```php
$capabilities = [
    'can_create'       => $authorizationService->hasPermission($adminId, 'roles.create'),
    'can_update_meta' => $authorizationService->hasPermission($adminId, 'roles.metadata.update'),
    'can_rename'      => $authorizationService->hasPermission($adminId, 'roles.rename'),
    'can_toggle'      => $authorizationService->hasPermission($adminId, 'roles.toggle'),
];

return $this->view->render($response, 'pages/roles.twig', [
    'capabilities' => $capabilities
]);
````

---

#### Usage in Twig

```twig
{% if capabilities.can_create %}
  <button id="add-role-btn">Add Role</button>
{% endif %}
```

```twig
{% if capabilities.can_rename %}
  <button class="rename-role">Rename</button>
{% endif %}
```

```twig
{% if capabilities.can_toggle %}
  <input type="checkbox" class="toggle-role" />
{% endif %}
```

---

#### Usage in JavaScript

```twig
<script>
  window.rolesCapabilities = {{ capabilities|json_encode|raw }};
</script>
```

```js
if (!window.rolesCapabilities.can_toggle) {
  document.querySelectorAll('.toggle-role').forEach(el => el.remove());
}
```

---

#### UI Rules (Mandatory)

* ❌ Twig MUST NOT check permissions by name
* ❌ JavaScript MUST NOT infer authorization
* ❌ UI MUST NOT assume API access
* ✅ Backend capabilities are the single UI contract
* ✅ API authorization is always enforced server-side

---

### 📋 List Roles

Returns a paginated list of all roles with derived grouping and UI metadata.

#### Endpoint

```http
POST /api/roles/query
```

**Auth Required:** Yes
**Permission:** `roles.query`

---

#### Request Body (List Query)

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "admins",
    "columns": {
      "group": "admins"
    }
  },
  "date": {
    "from": "2026-01-01",
    "to": "2026-12-31"
  }
}
```

---

#### Search Rules

| Scope       | Field                      |
|-------------|----------------------------|
| Global      | `roles.name`               |
| Column      | `id`, `name`, `group`      |
| Group Logic | substring before first `.` |

---

#### Sorting

```text
ORDER BY group ASC, name ASC
```

---

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": 1,
      "name": "admins.manage",
      "group": "admins",
      "display_name": "Admin Management",
      "description": "Full access to admin management features",
      "is_active": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 12,
    "filtered": 3
  }
}
```

---

#### Notes

* `group` is **derived**, not stored
* `display_name` and `description` may be `null`
* Technical role keys are **read-only**
* Role activation state is **returned**, not mutated here

---

### ✏️ Update Role Metadata

Updates **UI metadata only** for a single role.

> ⚠️ This endpoint does **NOT** modify:
>
> * role key (`name`)
> * role activation state (`is_active`)
> * role-permission mapping
> * admin-role assignment
> * authorization behavior

---

#### Endpoint

```http
POST /api/roles/{id}/metadata
```

**Auth Required:** Yes
**Permission:** `roles.metadata.update`

---

#### Request Body

```json
{
  "display_name": "Admin Management",
  "description": "Full access to admin management features"
}
```

*All fields are optional, but at least one must be provided.*

---

#### Behavior Summary

* If **both fields are missing** → no-op
* If role does not exist → server error
* Partial updates are allowed
* Update is **idempotent**

---

#### Responses

**200 OK**

```json
{}
```

**204 No Content**
Returned when no updatable fields are provided.

---

#### Possible Errors

| Code | Reason            |
|------|-------------------|
| 403  | Permission denied |
| 500  | Role not found    |
| 500  | Update failed     |

---

### 🔄 Toggle Role Activation

Controls whether a role participates in **authorization decisions**.

---

#### Endpoint

```http
POST /api/roles/{id}/toggle
```

**Auth Required:** Yes
**Permission:** `roles.toggle`

---

#### Request Body

```json
{
  "is_active": false
}
```

---

#### Behavior Rules

* Updates **only** the `is_active` flag
* Does **NOT** modify:

    * role key (`name`)
    * role metadata
    * role-permission mapping
    * admin-role assignments
* Operation is **idempotent**
* Reversible at any time
* No deletion occurs

---

#### Authorization Impact

* `is_active = false`

    * Role is ignored during authorization
    * Permissions are not evaluated
* `is_active = true`

    * Role participates normally in authorization

---

#### Responses

**200 OK**

```json
{}
```

---

#### Possible Errors

| Code | Reason            |
|------|-------------------|
| 403  | Permission denied |
| 500  | Role not found    |
| 500  | Update failed     |

---

### 🧱 Planned Role APIs (Not Implemented)

#### ➕ Create Role

```http
POST /api/roles
```

**Permission:** `roles.create`

**Status:** ⏳ Planned

---

#### ✏️ Rename Role (Technical Key)

```http
POST /api/roles/{id}/rename
```

**Permission:** `roles.rename`

**Status:** ⏳ Planned

---

### 📊 Role Fields

| Field          | Description                      | Mutable           |
|----------------|----------------------------------|-------------------|
| `id`           | Internal role identifier         | ❌                 |
| `name`         | Technical role key               | ❌                 |
| `group`        | Derived from `name`              | ❌                 |
| `display_name` | UI label (future i18n ready)     | ✅                 |
| `description`  | UI help text                     | ✅                 |
| `is_active`    | Authorization participation flag | ✅ *(toggle only)* |

---

### 🧠 Design Principles

* Roles are **RBAC aggregators**, not lifecycle entities
* UI metadata is **presentation-only**
* Authorization logic is **decoupled**
* No role deletion via API
* No permission assignment via this API
* Role activation is **explicit and reversible**

---

### 🔒 Status

**LOCKED — Roles Management & Activation Contract**

Any change requires updating:

* Controller behavior
* Repository logic
* Validation schemas
* UI capabilities mapping
* This documentation

---

### ✅ Current Implementation Status

| Feature                  | Status |
|--------------------------|--------|
| Roles listing            | ✅ DONE |
| Group derivation         | ✅ DONE |
| Search & filtering       | ✅ DONE |
| Metadata update API      | ✅ DONE |
| Role activation toggle   | ✅ DONE |
| UI capabilities contract | ✅ DONE |
| Role creation            | ⏳ NEXT |
| Role-permission mapping  | ⏳ NEXT |
| Admin-role assignment    | ⏳ NEXT |

---
