# Canonical Logger Design Standard (STRICT · Library-First · No-Guessing)
**PROJECT:** `maatify/admin-control-panel`  
**STATUS:** LOCKED (non-negotiable)  
**SCOPE:** Rules for designing **any logger subsystem** (existing or future) as an extractable library with a strict core + project-controlled policy.  
**GOAL:** Zero interpretation. Zero “smart guessing”. Every decision is dictated here.

---

## 0) Global Agreement Lock (What we agreed — and MUST NOT drift)

This document is the single source of truth for:
- How we design a logger library.
- How the project integrates it.
- What is allowed/forbidden.
- What is shared vs subsystem-specific.

### 0.1 Key agreements (verbatim meaning)
1) **The library has NO silent exceptions.**  
   The library **MUST throw** custom exceptions for all failures.

2) **Silencing (fail-open/swallow) is a PROJECT policy, not a library feature.**  
   The project decides when/where to swallow exceptions via wrappers.

3) **The library ships ready-to-use MySQL support.**  
   The consumer can:
   - **Pass a DB connection (PDO)** and the library works immediately, OR
   - **Inject storage/driver implementations** via interfaces (wrapper mode).

4) **Enums must be replaceable.**  
   Library Enums are default implementations only.  
   The project can replace them with project-specific enums by implementing interfaces.

5) **No array-based public APIs.**  
   Arrays are allowed ONLY for:
   - DTO serialization (`toArray()` / `jsonSerialize()`).
   Everything else must be DTOs and interfaces.

6) **We may create a shared package for repeated primitives.**  
   It must remain small, domain-agnostic, and app-agnostic.

---

## 1) Terminology Lock (Unbreakable meanings)

### 1.1 Log categories
- **Audit:** authoritative + transactional (fail-closed). *Not this document’s target.*
- **SecurityEvents:** observational / best-effort / non-authoritative.
- **ActivityLog:** operational history / best-effort / non-authoritative.
- **Telemetry:** observability / best-effort / non-authoritative.
- **PSR-3 diagnostics:** filesystem/external diagnostic logs; not a substitute.
- **Data Access Log:** a separate subsystem (deferred) and must NOT be emulated by any other logger.

### 1.2 “No substitution” rules
- No observational logger may pretend to be Audit.
- No logger subsystem may write into another subsystem’s tables.
- No logger may be used as a business-logic source of truth.

---

## 2) The Mandatory Architecture (Two-Core + One-Policy)

Every logger subsystem MUST be designed as:

### 2.1 Core Library (STRICT)
This is what gets extracted as a standalone package.

**Core Library MUST:**
- Ship with ready MySQL implementations (PDO-based).
- Use interfaces for replaceability (storage, types, severity, etc.).
- Provide default enums (but not force them).
- Use DTO-only public APIs.
- **Throw custom exceptions** (never swallow).
- Have zero dependency on the project’s HTTP/container/context.

### 2.2 Project Policy Layer (SILENCING LIVES HERE)
This is project-owned code (Domain/Application), and is NOT part of the extracted library.

**Project Policy Layer MAY:**
- Catch library exceptions and swallow (fail-open).
- Emit PSR-3 warnings.
- Add metrics/fallback behavior.

**Hard lock:**  
✅ **Only the project policy layer may silence.**  
❌ The library may not silence anywhere.

### 2.3 App Glue (request-scoped convenience)
Factories, middleware, controller helpers, context-binding recorders belong here.

**Hard lock:**  
Core library must not know RequestContext/AdminContext.

---

## 3) “Connection or Wrap” Rule (How library is used)

The library MUST support BOTH usage styles:

### 3.1 Direct Connection Mode (Out-of-the-box)
Consumer provides a DB connection:

- Provide `PDO $pdo` to `Mysql<Subsystem>Writer` and/or `Mysql<Subsystem>Reader`.
- Library immediately works without extra setup beyond constructing objects.

**This is mandatory.** The library is usable by just passing the connection.

### 3.2 Wrapper / Injection Mode (Replaceability)
Consumer provides custom implementations:

- Consumer provides `<Subsystem>WriterInterface` / `<Subsystem>ReaderInterface` implementations.
- Consumer can replace MySQL with:
  - Redis
  - file sink
  - null sink
  - async queue
  - HTTP remote logger
  etc.

**This is mandatory.** The library’s contracts enable full replacement.

---

## 4) Replaceable Enums Rule (Enums via Interfaces)

### 4.1 Why
Project may need:
- additional events/actions
- renamed events
- different grouping
- versioned keys

### 4.2 Canonical contract
The library MUST define interfaces such as:
- `<Subsystem>ActionInterface`
- `<Subsystem>SeverityInterface` (if severity exists)

Minimum requirement:
- `public function key(): string;`

**Default enums inside library implement these interfaces**, but are never required for consumers.

---

## 5) DTO-Only Rule (No arrays anywhere except DTO serialization)

### 5.1 Strict ban
Forbidden in public APIs:
- `write(array $payload)`
- `paginate(array $filters)`
- `log(array $meta)`
- `count(array $filters)`

### 5.2 Allowed arrays (only here)
Allowed ONLY:
- `DTO::toArray(): array`
- `DTO::jsonSerialize(): mixed`

### 5.3 Metadata representation (no raw array input)
Metadata MUST be one of:
- `MetaDTO` (preferred typed object), OR
- `string $metadataJson` (validated JSON string)

Raw metadata arrays are not allowed as inputs.

---

## 6) Exception Policy (STRICT) — Library throws, Project may silence

### 6.1 Library exceptions taxonomy (mandatory)
Each subsystem library MUST define:
- `<Subsystem>StorageException`  
  wraps PDO/transport errors and includes **sanitized** context
- `<Subsystem>MappingException`  
  thrown for deterministic mapping failure: row → ReadDTO
- `<Subsystem>ValidationException`  
  thrown when DTO invariants are violated

### 6.2 What library MUST do
- Throw these exceptions.
- Never swallow exceptions.
- Never return partial/ambiguous results on failure.

### 6.3 What project MAY do
- Catch these exceptions and swallow (best-effort).
- Or rethrow if the project wants fail-closed for that subsystem.

**Hard lock:**  
✅ Exceptions become “silent” ONLY when the project catches them.  
❌ Library never makes them silent.

### 6.4 Sanitization rule (mandatory)
Library exceptions MUST NOT leak sensitive data (tokens/passwords/PII payloads).
They MAY include:
- SQLSTATE
- operation name
- table/column names
- safe identifiers (request_id, actor_id if non-PII)
- correlation IDs (trace_id, session_id)

---

## 7) Canonical Package Layout (Applies to ANY logger subsystem)

For a subsystem named `<Subsystem>`:

```

Modules/<Subsystem>/
├── Contracts/
│   ├── <Subsystem>WriterInterface.php
│   ├── <Subsystem>ReaderInterface.php
│   ├── <Subsystem>ActionInterface.php            # or TypeInterface
│   ├── <Subsystem>SeverityInterface.php          # if applicable
│   └── ClockInterface.php                        # OPTIONAL (time injection)
├── DTO/
│   ├── <Subsystem>WriteDTO.php
│   ├── <Subsystem>ReadDTO.php
│   ├── <Subsystem>QueryDTO.php
│   └── <Subsystem>MetaDTO.php                    # OPTIONAL typed metadata
├── Enums/
│   ├── Default<Subsystem>ActionEnum.php
│   └── Default<Subsystem>SeverityEnum.php
├── Exceptions/
│   ├── <Subsystem>StorageException.php
│   ├── <Subsystem>MappingException.php
│   ├── <Subsystem>ValidationException.php
├── Infrastructure/
│   └── Mysql/
│       ├── Mysql<Subsystem>Writer.php             # PDO-based
│       └── Mysql<Subsystem>Reader.php             # PDO-based
└── README.md

```

**Mandatory naming convention:**
- Interfaces end with `Interface`
- DTOs end with `DTO`

---

## 8) Canonical Public Contracts (Signatures are locked)

### 8.1 Writer
- accepts only WriteDTO
- throws StorageException

**Canonical:**
- `public function write(<Subsystem>WriteDTO $dto): void;`

### 8.2 Reader
- accepts only QueryDTO
- returns typed result DTO
- throws StorageException / MappingException

**Canonical (minimum):**
- `public function paginate(<Subsystem>QueryDTO $query): PageResultDTO;`
- `public function count(<Subsystem>QueryDTO $query): int;` (if needed)

### 8.3 Action/type interface
**Canonical minimum:**
- `public function key(): string;`

### 8.4 DTO serialization
Every DTO MUST implement:
- `toArray(): array`
- `jsonSerialize(): mixed` (or JsonSerializable)

---

## 9) Query Design (Typed, deterministic, no arrays)

### 9.1 QueryDTO contents (general)
A QueryDTO MUST be typed and validate invariants.

Typical fields (only if applicable):
- correlation: `requestId`, `traceId`, `sessionId`
- actor: `actorType`, `actorId`
- action/type: `ActionInterface` or string key
- severity
- time range: `from`, `to` (full timestamps only if that is your rule)
- pagination: `PageRequestDTO`
- ordering: typed sort field + direction (enums/interfaces)

### 9.2 Ordering (if supported)
- Must not accept arbitrary strings.
- Must use a typed enum/interface for allowed fields.

---

## 10) Shared Kernel (Optional, allowed, but STRICT)

We MAY create a shared kernel for repeated primitives.

### 10.1 Allowed in shared kernel (domain-agnostic + app-agnostic)
1) Base exception classes:
   - `StorageExceptionBase`
   - `MappingExceptionBase`
   - `ValidationExceptionBase`
2) PDO helper for prepare/execute (no ORM)
3) Pagination DTOs:
   - `PageRequestDTO`
   - `PageResultDTO`
4) Serialization interface:
   - `ArraySerializableInterface` (requires `toArray()`)

### 10.2 Forbidden in shared kernel
- RequestContext/AdminContext/HTTP/container
- shared enums across different subsystems
- “unified mega logger framework”
- mega DTO that mixes unrelated fields

---

## 11) Project Integration Standard (How the app uses the library)

### 11.1 Safe wrapper (policy) pattern (recommended)
If a subsystem is best-effort at runtime, project implements:

- `Safe<Subsystem>Recorder` (or `BestEffort<Subsystem>Service`)

Responsibilities:
- take project contexts (RequestContext/AdminContext) + business params
- construct `<Subsystem>WriteDTO`
- call library writer
- catch `<Subsystem>StorageException` and swallow
- optionally PSR-3 warn

**Hard lock:**  
This wrapper is where silencing lives. Not in the library.

### 11.2 Factory pattern (request-scoped convenience)
Allowed for HTTP usage:
- `Http<Subsystem>RecorderFactory`
- returns a recorder bound to request context

Still: this is project glue, not library.

---

## 12) ASCII Execution Flows (Locked)

### 12.1 Core library flow (always throws)
```

Caller
  │
  ▼
<Subsystem>WriterInterface::write(WriteDTO)
  │
  ▼
Mysql<Subsystem>Writer (PDO)
  │
  ▼
Database
  │
  └─✖ Failure
        ↓
   <Subsystem>StorageException (thrown)

```

### 12.2 Project best-effort flow (project silences)
```
Application / Domain Layer
  │
  ▼
Safe<Subsystem>Recorder
  │   (Project-level failure policy)
  ▼
Library Writer (throws StorageException)
  │
  └─✖ Exception raised
        ↓
   Safe<Subsystem>Recorder
      • catch StorageException
      • optional PSR-3 warning
      • swallow (policy decision)
        ↓
   Application continues normally

```

---

## 13) Acceptance Gate (Pass/Fail — no debate)

A logger subsystem is ACCEPTED only if:

### 13.1 Core Library
- [ ] MySQL writer/reader exist and accept PDO
- [ ] Public API is DTO-only (no arrays)
- [ ] Query uses QueryDTO (no filter arrays)
- [ ] Enums are replaceable via interfaces
- [ ] Library throws custom exceptions for all failures
- [ ] Library contains ZERO silent swallow/catch for policy
- [ ] DTOs immutable + `toArray()` + `jsonSerialize()`
- [ ] No dependencies on RequestContext/AdminContext/HTTP/container
- [ ] Tests exist for: DTO invariants, MySQL write failure -> StorageException, mapping failure -> MappingException

### 13.2 Project Policy
- [ ] Best-effort swallow exists only in project wrapper
- [ ] Context enrichment exists only in project code
- [ ] Optional PSR-3 warnings are emitted only by project code

### 13.3 Shared Kernel (if used)
- [ ] domain-agnostic + app-agnostic only
- [ ] contains only: base exceptions, PDO helper, pagination, serialization interface

---

## 14) Meaning of “Done”
We consider the structure complete when:
- Any new logger subsystem can be built by following this document without any additional decisions.
- The library is strict (throws), portable, DTO-only, MySQL-ready, replaceable via interfaces.
- The project controls silencing via wrappers.
- Shared kernel is optional but safe.

---
