# Canonical Logging Libraries Architecture Spec
**PROJECT:** `maatify/admin-control-panel`  
**SCOPE:** Structural architecture for extracting **three standalone libraries** (ActivityLog, SecurityEvents, Telemetry) + optional shared kernel  
**STATUS:** LOCKED (Structure-only)  
**GOAL:** Any engineer can implement/extract without interpretation or “smart guessing”.

---

## 0) Non-Negotiable Terminology Lock

### 0.1 Log Categories (Canonical Meanings)
1) **Audit Logs**  
- **Authoritative + Transactional**  
- Failure MUST block the transaction  
- **NOT in scope** for this document (mentioned only for boundaries)

2) **SecurityEvents**  
- **Observational / Best-effort / Non-authoritative**  
- Used for monitoring, intrusion detection, debugging security flows  
- Failure MUST NOT block the main flow (but policy is enforced by the project layer, not the library)

3) **ActivityLog**  
- **Operational history / Best-effort / Non-authoritative**  
- Used for UX/admin visibility (“what actions occurred”)  
- Failure MUST NOT block main flow (policy enforced by project layer)

4) **Telemetry**  
- **Observability / Best-effort / Non-authoritative**  
- Used for tracing, latency, internal errors, debugging  
- Failure MUST NOT block main flow (policy enforced by project layer)

5) **PSR-3 Diagnostic Logs**  
- Filesystem / external sinks for diagnostics  
- NOT a substitute for SecurityEvents/ActivityLog/Telemetry/Audit

6) **Data Access Logs**  
- **Deferred / Not Implemented / Not Allowed** until a dedicated ADR + schema + privacy policy exist  
- MUST NOT be emulated using ActivityLog/SecurityEvents/Telemetry/Audit

### 0.2 Boundary Rules (Hard)
- **Telemetry MUST NOT write to audit tables.**
- **ActivityLog MUST NOT be used for “view/read/open” actions** (Data Access Logs are deferred).
- **No subsystem may act as the source of truth for business logic decisions.**
- **Library code is NEVER allowed to swallow exceptions silently.** (Silencing is project policy.)

---

## 1) Design Philosophy (The “3-Layer” Rule)

Each subsystem MUST be modelled as three distinct layers:

### 1.1 Core Library (Portable)
- Contains: Contracts + DTOs + Enums (default implementations) + Exceptions + MySQL driver
- Accepts a DB connection (PDO) OR accepts storage via interface implementation
- **Throws custom exceptions** on any failure
- Contains **NO application context** (no RequestContext/AdminContext/no container/no HTTP)

### 1.2 Project Policy Wrapper (App/Domain)
- Responsible for “best-effort” semantics:
  - try/catch + swallow
  - optional PSR-3 warning logging
  - fallback strategies (if desired)
- Responsible for context enrichment:
  - request_id / ip / user_agent
  - actor_type / actor_id
  - route_name
- Converts “app context” into the library DTOs

### 1.3 App Glue (HTTP/Framework)
- Factories / recorders that bind RequestContext/AdminContext
- Middleware/controller helpers
- Anything request-scoped belongs here, not inside the core library

> ✅ Result: Core library is strict + predictable.  
> ✅ Project decides best-effort behavior.  
> ✅ Extraction to standalone package is clean.

---

## 2) Non-Error-Prone Rules (No “Interpretation” Allowed)

### 2.1 Connection & Driver Rule
The library MUST ship with a ready-to-use MySQL implementation:
- The user can do either:
  1) **Pass PDO directly** into the MySQL driver, OR
  2) Inject a custom `StorageInterface` / `WriterInterface` implementation

**Canonical requirement:** MySQL driver exists inside the library, but depends only on PDO and internal contracts.

### 2.2 Interfaces & Replaceability Rule
- Everything that is “project-specific” MUST be replaceable by interface:
  - Actions/Types/Severities use **Interfaces**, not hard dependency on the library’s enums
- The library provides default enums implementing those interfaces
- Projects may provide their own enums implementing those interfaces

### 2.3 Exception Rule (STRICT)
- The library MUST NOT swallow any error.
- The library MUST throw:
  - Storage errors → `*StorageException`
  - Mapping errors → `*MappingException`
  - Validation errors → `*ValidationException` (only if library validates DTO invariants)

### 2.4 DTO-Only Rule (No Arrays)
- Public APIs MUST accept only DTOs and interfaces (no arrays).
- Arrays are permitted ONLY in:
  - `DTO::toArray()`
  - `DTO::jsonSerialize()`
  - optional `DTO::fromArray()` (if defined)
- Any “filters” MUST be a `QueryDTO` (typed), not `array $filters`.

### 2.5 Naming Conventions (Locked)
- All interfaces end with `Interface`
- All DTO classes end with `DTO`

---

## 3) Canonical Package Layout (Applies to ALL 3 Subsystems)

This is the locked folder blueprint for each subsystem library:

```

<Subsystem>/
├── Contracts/
│   ├── <Subsystem>WriterInterface.php
│   ├── <Subsystem>ReaderInterface.php
│   ├── <Subsystem>ActionInterface.php          # or TypeInterface (domain-specific naming)
│   └── <Subsystem>SeverityInterface.php        # optional if severity exists
├── DTO/
│   ├── <Subsystem>WriteDTO.php
│   ├── <Subsystem>ReadDTO.php
│   └── <Subsystem>QueryDTO.php
├── Enums/
│   ├── Default<Subsystem>ActionEnum.php        # implements ActionInterface
│   └── Default<Subsystem>SeverityEnum.php      # implements SeverityInterface (if used)
├── Exceptions/
│   ├── <Subsystem>StorageException.php
│   ├── <Subsystem>MappingException.php
│   └── <Subsystem>ValidationException.php      # if needed
├── Infrastructure/
│   └── Mysql/
│       ├── Mysql<Subsystem>Writer.php          # implements WriterInterface, requires PDO
│       └── Mysql<Subsystem>Reader.php          # implements ReaderInterface, requires PDO
└── README.md

```

> ✅ Core library is “closed” (strict) but “replaceable” (interfaces).

---

## 4) Canonical Public API Contracts (Strict)

### 4.1 Writer Contract
`<Subsystem>WriterInterface`
- MUST accept a single WriteDTO
- MUST return void
- MUST throw `<Subsystem>StorageException` on failure

**Canonical signature shape:**
- `public function write(<Subsystem>WriteDTO $dto): void;`

### 4.2 Reader Contract
`<Subsystem>ReaderInterface`
- MUST accept a single QueryDTO
- MUST return a typed result DTO (see pagination below)
- MUST throw `<Subsystem>StorageException` or `<Subsystem>MappingException`

**Canonical:**
- `public function paginate(<Subsystem>QueryDTO $query): PageResultDTO;`
- `public function count(<Subsystem>QueryDTO $query): int;` (optional but consistent)

### 4.3 Action/Type Replaceability Contract
`<Subsystem>ActionInterface`
- MUST provide a deterministic string key for storage
- MUST NOT require enum usage

**Canonical expectation:**
- `public function key(): string;`

> If using PHP Enums, implement `key()` and map to `$enum->value` internally.

### 4.4 DTO Serialization Contract
Each DTO MUST implement:
- `public function toArray(): array;`
- `public function jsonSerialize(): mixed;` (or implement JsonSerializable)

DTOs MUST be immutable (readonly where possible).

---

## 5) Pagination Canon (Shared Across Readers)

All read-side queries MUST use the same pagination primitives.

### 5.1 PageRequestDTO
Fields (typed):
- `page` (int, >=1)
- `perPage` (int, bounded; canonical clamp in DTO constructor)

### 5.2 PageResultDTO
Fields:
- `items` (array of ReadDTO)
- `total` (int)
- `page` (int)
- `perPage` (int)

No arrays as input; arrays are allowed only in `toArray()`.

---

## 6) Shared Kernel: When it Helps vs When it Hurts (Locked Policy)

### 6.1 Allowed Shared (“Kernel”) Content
A small shared package IS allowed **only** if it is domain-agnostic and app-agnostic.

**Allowed shared components:**
1) Base exceptions:
   - `StorageExceptionBase`
   - `MappingExceptionBase`
   - `ValidationExceptionBase`
2) PDO execution helper (no ORM, no query builder):
   - standard prepare/execute wrapper that throws `StorageExceptionBase`
3) Pagination DTOs:
   - `PageRequestDTO`, `PageResultDTO`
4) Serialization interface:
   - `ArraySerializableInterface` with `toArray()`

### 6.2 Forbidden Shared Content (Will Make Project Worse)
1) Any RequestContext/AdminContext/HTTP knowledge
2) Any “Unified logger framework” that forces all subsystems into one abstraction
3) Shared enums for event/action/type across subsystems
4) A shared “mega DTO” that mixes fields from different domains

> ✅ Recommendation: Shared kernel must remain SMALL, stable, and boring.

---

## 7) Policy Wrapper (Project-Side) — Canonical Pattern

### 7.1 Why Wrapper Exists
- Library must throw (strict)
- Project must decide best-effort (swallow/PSR-3/fallback)

### 7.2 Canonical Safe Recorder Pattern
For each subsystem, the project MAY implement:

- `Safe<ActivityLog>Recorder`
- `Safe<SecurityEvents>Recorder`
- `Safe<Telemetry>Recorder`

Responsibilities:
- Build WriteDTO using app contexts
- Call library writer
- Catch library exceptions and swallow
- Optionally emit PSR-3 warning

**Hard rule:** swallowing happens ONLY in this wrapper, not in the library.

---

## 8) Context Injection Strategy (Project Side Only)

### 8.1 Context Fields (Canonical Set)
When available, project wrapper SHOULD enrich:
- `request_id`
- `route_name` (if known)
- `ip_address`
- `user_agent`
- `actor_type` (admin/system/guest as applicable)
- `actor_id` (if any)
- `metadata` (structured)

### 8.2 Telemetry Factory Pattern (Allowed in App Glue)
Factories like:
- `HttpTelemetryRecorderFactory`
are allowed in App Glue only, because they are request-scoped.

The core library must never reference RequestContext.

---

## 9) Canonical Failure Semantics (ASCII)

### 9.1 Library Core (Always Throws)
```

Caller
|
v
Library Writer/Reader
|
v
Mysql Driver (PDO)
|
v
DB
|
X (error)
|
v
throws <Subsystem>StorageException / MappingException

```

### 9.2 Project Policy Wrapper (Best-Effort)
```

App/Domain Code
|
v
Safe<Subsystem>Recorder (project)
|
+--> builds DTO from RequestContext/AdminContext
|
v
Library Writer (throws)
|
X exception
|
v
Safe recorder catches -> swallow (optional PSR-3 warn)
|
v
Main request continues

```

---

## 10) Current-State Snapshot (Descriptive Only, No Fix Plan)

### 10.1 ActivityLog (Observed Traits)
- Has a module service that may swallow errors (this is NOT allowed in the future library standard)
- Has duplicated MySQL writers (module vs infrastructure)
- Read-side exists outside the module (fragmentation)

### 10.2 SecurityEvents (Observed Traits)
- Contains module reader/writer + enums + DTOs
- Has a contract honesty issue in at least one branch (doc vs throw behavior)
- Has legacy vs modern split in the repository (structure noise)

### 10.3 Telemetry (Observed Traits)
- Strong typed query DTO approach
- Honest “throws at module, swallow at wrapper” exists in practice (closest to target)
- Application glue factory exists (correct placement if kept outside core)

> Note: This section exists only to ensure we do not lose institutional knowledge during restructuring.

---

## 11) PR Gate Checklist (Pass/Fail, No Debate)

A subsystem library PR is ACCEPTED only if ALL are true:

### Core Library Requirements
- [ ] MySQL driver exists and accepts PDO
- [ ] All public methods accept DTOs/interfaces only (no arrays)
- [ ] Querying uses QueryDTO (no array filters)
- [ ] DTOs are immutable and implement `toArray()`
- [ ] No silent catch/swallow exists in library code
- [ ] Library throws only custom exceptions (no raw PDOException leaks)
- [ ] Enums are optional; interfaces exist for replaceability
- [ ] No reference to RequestContext/AdminContext/HTTP/Container exists in core

### Wrapper/Glue Requirements (Project Side)
- [ ] Best-effort swallowing exists only in project wrapper
- [ ] Context enrichment occurs only in project code
- [ ] Any PSR-3 diagnostic logging is done only by project code

### Shared Kernel Requirements (If used)
- [ ] Shared code contains no domain-specific knowledge
- [ ] Shared code contains no application-specific knowledge
- [ ] Shared code is limited to exceptions, PDO helper, pagination, serialization interface

---

## 12) Final Target Outcome (What “Done” Means)
When finished, we will have:
1) Three strict core libraries:
   - ActivityLog core
   - SecurityEvents core
   - Telemetry core
2) Optional tiny shared kernel
3) Project wrappers that enforce best-effort behavior consistently
4) Zero array-based APIs, zero silent exceptions, full DTO discipline, full custom exception taxonomy
5) Maximum replaceability via interfaces (including enums through interfaces)

---

## Appendix A) Minimal Required Class Set (Per Subsystem)
- 2 contracts: WriterInterface, ReaderInterface
- 3 DTOs: WriteDTO, ReadDTO, QueryDTO
- 1–2 exceptions: StorageException, MappingException
- 1 action/type interface (+ optional default enum)
- Mysql writer + Mysql reader (PDO-based)
- README

> If a subsystem does not need reading in v1, the ReaderInterface MAY exist but be shipped with a “MysqlReader” as optional — decision must be explicit in README (no implicit missing pieces).
