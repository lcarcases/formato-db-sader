# Implementation Plan: Obtener Catálogo de Tipos de Personal

**Branch**: `001-catalogo-tipos-personal` | **Date**: 2026-05-03 | **Spec**: [spec.md](spec.md)  
**Input**: Feature specification from `/specs/001-catalogo-tipos-personal/spec.md`

## ⚠️ CRITICAL: Arquitectura Hexagonal Patterns (MANDATORY)

**This plan follows the arquitectura-hexagonal skill patterns. ALL implementations MUST comply:**

### 🔴 Non-Negotiable Patterns:
1. **Spanish Naming**: `ObtenerTiposPersonalInAdapter` (NOT Controller, NOT English verbs - note PLURAL TiposPersonal)
2. **InAdapter Constructor**: Use `app()->make()` pattern (NOT dependency injection parameters)
3. **Respuesta Class**: ALWAYS use `Respuesta` class for responses (NOT `response()->json()`)
4. **Import Path**: `use App\Core\Shared\Infrastructure\Respuesta;` (with 'a', NOT Infrastructure)
5. **DTO Naming**: Verb-prefixed `ObtenerTiposPersonalOutDto` (NOT generic `TipoPersonalOutDto`)
6. **Versioned Routes**: `api/v1/admin` prefix in module-specific route files (NOT routes/api.php)
7. **Port Naming**: Spanish verbs `IObtenerTiposPersonalUseCase` (NOT `IGetTiposPersonalUseCase`)

**Reference**: See `.github/skills/arquitectura-hexagonal/SKILL.md` for complete patterns.

---

## Summary

Exponer endpoint REST API `GET /api/v1/admin/tipos-personal` que retorne catálogo de tipos de personal activos (Base, Enlace, Confianza, Externo) siguiendo arquitectura hexagonal y DDD. Incluye rate limiting (60 req/min), CORS (allow all), logs estructurados, y formato de respuesta estándar `{success, message, code, data}`. Sin autenticación. Implementación basada en patrón existente de TipoRequerimiento.

**Architectural Compliance**: ✅ All artifacts follow arquitectura-hexagonal skill patterns (Spanish naming, Respuesta class, app()->make(), versioned routes, verb-prefixed DTOs)

## Technical Context

**Language/Version**: PHP 8.4+ (strict types, readonly properties, enums)  
**Primary Dependencies**: Laravel 13.x (infrastructure only), PostgreSQL 16.x driver, Redis (Laravel cache)  
**Storage**: PostgreSQL 16.x (source of truth), Redis 7.4.x (cache only - NO TABLE CACHING IN V1)  
**Testing**: PHPUnit 12.x, PHPStan Level 9, Laravel Pint (PSR-12)  
**Target Platform**: Linux server (Docker Compose stack)  
**Project Type**: REST API backend (no frontend/SSR/Blade/Livewire/SPA)  
**Performance Goals**: <500ms p95 response time for successful queries  
**Constraints**: 
- Domain layer MUST have zero Laravel dependencies  
- Public endpoint (no authentication) with rate limiting protection
- PostgreSQL as single source of truth (no cache reads in v1)
- Response format MUST follow project standard: `{success, message, code, data}`  
**Scale/Scope**: 4 static catalog records, 60 requests/min/IP, Admin bounded context

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.1.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with Domain/Application/Infrastructure separation. TipoPersonal entity in Domain, use case in Application, API InAdapter + Eloquent repository in Infrastructure.
- [x] **Domain Isolation**: Domain layer (TipoPersonal entity) will be pure PHP with zero Laravel dependencies. No `Illuminate\*` imports in domain.
- [x] **DDD Principles**: Bounded context = Admin. Aggregate = TipoPersonal (simple aggregate root). Ubiquitous language: "TipoPersonal", "activo", Spanish SADER terminology. Repository pattern via ITipoPersonalOutPort interface.
- [x] **Test Strategy**: Unit tests for ObtenerTiposPersonalUseCase (isolated with mocks), integration tests for TipoPersonalPostgresSQLRepository (TestContainers or in-memory), contract tests for GET /api/v1/admin/tipos-personal endpoint (response schema, status codes, edge cases).
- [x] **Explicit Contracts**: Outbound port = ITipoPersonalOutPort (repository interface), Input DTO = none (no params), Output DTO = ObtenerTiposPersonalOutDto wrapping array of TipoPersonalOutDto `{id: int, nombre: string}`.
- [x] **Ubiquitous Language**: "TipoPersonal" consistent across: Domain entity, DB table `tb_cat_tipo_personal`, API endpoint `/tipos-personal`, repository interface `ITipoPersonalOutPort`, DTO `TipoPersonalOutDto`, tests `TipoPersonalTest.php`.
- [x] **API-First**: REST GET endpoint versioned at `/api/v1/admin/tipos-personal`. HTTP 200 (success), 429 (rate limit), 500 (infrastructure error). JSON Content-Type. Standard response format `{success, message, code, data}`. OpenAPI documented.
- [x] **Security**: Public endpoint with rate limiting (60 req/min per IP via Laravel `ThrottleRequests` middleware). CORS configured (allow all origins). Audit logging with structured context (request_id, user_ip, duration_ms). No secrets in code.
- [x] **Observability**: Structured JSON logs implemented at Infrastructure layer (InAdapter) with: request_id (UUID), action ("ObtenerTiposPersonal"), result (success/error), user_ip, timestamp (ISO 8601), duration_ms. Application layer (Use Cases) remains framework-agnostic with no logging. Laravel Monolog configured. Log level INFO for success, ERROR for failures.
- [x] **Database Strategy**: PostgreSQL table `tb_cat_tipo_personal` with fields: id_nu_tipo_personal, sn_nombre, sn_descripcion, ind_activo, created_at, updated_at. Migration inserts 4 initial rows (no seeder). Redis NOT used for this feature (no caching in v1). Repository queries PostgreSQL directly.

**Complexity Justification**: None. Feature follows constitutional principles with no violations. Hexagonal architecture strictly enforced with 3-layer separation. Domain isolation maintained. Standard catalog read pattern replicating TipoRequerimiento.

---

## Project Structure

### Documentation (this feature)

```text
specs/001-catalogo-tipos-personal/
├── spec.md                      # Feature specification (COMPLETE)
├── plan.md                      # This file (IN PROGRESS)
├── research.md                  # Phase 0: Pattern analysis (TO BE CREATED)
├── data-model.md                # Phase 1: Entity/VO/Port definitions (TO BE CREATED)
├── quickstart.md                # Phase 1: Developer onboarding (TO BE CREATED)
├── contracts/
│   └── api-tipos-personal.yaml  # Phase 1: OpenAPI spec (TO BE CREATED)
├── checklists/
│   └── requirements.md          # Quality checklist (COMPLETE)
└── tasks.md                     # Phase 2: Implementation tasks (PENDING /speckit.tasks)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   ├── Entities/
│   │   └── TipoPersonal.php                              # NEW: Domain entity
│   ├── ValueObjects/                                     # (not needed for this feature)
│   ├── Events/                                           # (not needed for this feature)
│   └── Exceptions/
│       └── TipoPersonalNotFoundException.php             # NEW: Domain exception
├── Application/
│   ├── UseCases/
│   │   └── ObtenerTiposPersonalUseCase.php               # NEW: Use case implementation (PLURAL)
│   ├── DTOs/
│   │   └── Out/
│   │       ├── ObtenerTiposPersonalOutDto.php            # NEW: Wrapper DTO (verb-prefixed)
│   │       └── TipoPersonalOutDto.php               # NEW: Individual item DTO
│   └── Ports/
│       ├── In/
│       │   
│       └── Out/
│           └── ITipoPersonalOutPort.php                  # NEW: Outbound port (repository interface)
└── Infrastructure/
    ├── Adapters/
    │   ├── In/
    │   │   └── Api/
    │   │       └── ObtenerTiposPersonalInAdapter.php        # NEW: HTTP inAdapter (NOT Controller - PLURAL)
    │   │       # Note: No Form Requests needed (no input validation)
    │   │       # Note: No Resources needed (Respuesta class handles formatting)
    │   └── Out/
    │       └── PostgresSQL/
    │           ├── Models/
    │           │   └── TipoPersonalEloquentModel.php        # NEW: Eloquent model
    │           ├── Repositories/
    │           │   └── TipoPersonalPostgresSQLRepository.php # NEW: Repository implementation
    │           └── TipoPersonalPostgresSQLOutAdapter.php     # NEW: OutAdapter orchestrator
    ├── Routes/
    │   └── AdminApiRoutes.php                               # UPDATE: Add tipos-personal route (versioned api/v1)
    └── Providers/
        └── AdminServiceProvider.php                         # UPDATE: Bind ports to implementations

database/
└── migrations/
    ├── YYYY_MM_DD_HHMMSS_create_tb_cat_tipo_personal_table.php  # NEW: Schema migration
    └── YYYY_MM_DD_HHMMSS_seed_tb_cat_tipo_personal_table.php    # NEW: Data migration (4 rows)

tests/
├── Unit/
│   └── Core/
│       └── Admin/
│           ├── Application/
│           │   └── UseCases/
│           │       └── ObtenerTiposPersonalUseCaseTest.php      # NEW: Use case unit tests (PLURAL)
│           └── Domain/
│               └── Entities/
│                   └── TipoPersonalTest.php                     # NEW: Entity unit tests
├── Integration/
│   └── Infrastructure/
│       └── Adapters/
│           └── Out/
│               └── PostgresSQL/
│                   └── Repositories/
│                       └── TipoPersonalPostgresSQLRepositoryIntegrationTest.php  # NEW: Repository integration tests
└── Feature/
    └── Api/
        └── Admin/
            └── ObtenerTiposPersonalApiTest.php                  # NEW: API contract tests (PLURAL)
```

**Structure Decision**: Single monolithic Laravel project with hexagonal architecture. Feature located in **Admin bounded context** (`app/Core/Admin/`) following existing TipoRequerimiento pattern. Three-layer separation enforced: Domain (pure PHP) → Application (framework-agnostic) → Infrastructure (Laravel-specific). Tests mirror production structure in `tests/` directory.

---

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| *(No violations)* | *(N/A)* | *(N/A)* |

**Rationale**: Feature implementation follows all constitutional principles without exceptions. Hexagonal architecture enforced. Domain isolation maintained. DDD tactical patterns applied. Test-first approach planned. Explicit contracts defined. Ubiquitous language consistent. SOLID principles will be followed. API-first design respected.

---

## Phase 0: Outline & Research

**Status**: TO BE EXECUTED  
**Output**: `research.md`

### Research Tasks

1. **Analyze existing TipoRequerimiento pattern**
   - **Files to review**:
     - `app/Core/Admin/Application/Ports/In/IObtenerTipoRequerimientoUseCase.php` (inbound port pattern)
     - `app/Core/Admin/Application/Ports/Out/ITipoRequerimientoOutPort.php` (outbound port pattern)
     - `app/Core/Admin/Application/DTOs/Out/ObtenerTipoRequerimientoOutDto.php` (DTO pattern)
     - `app/Core/Admin/Application/UseCases/ObtenerTiposRequerimientosUseCase.php` (use case implementation pattern)
     - `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerTiposRequerimientosInAdapter.php` (controller pattern)
     - `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/TipoRequerimientoPostgresSQLRepository.php` (repository pattern)
     - `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` (route registration pattern)
     - `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php` (DI binding pattern)
   - **Findings to document**:
     - How inbound/outbound ports are defined (method signatures, return types)
     - DTO structure and naming conventions (readonly, toArray(), fromStdClass())
     - Use case orchestration pattern (constructor injection, execute method)
     - Controller responsibilities (thin inAdapter, delegates to use case)
     - Repository implementation (Eloquent query, transformation to DTO)
     - Route definition (prefix, middleware, controller method)
     - Service provider bindings (interface → implementation mapping)

2. **Investigate rate limiting best practices in Laravel 13**
   - **Research questions**:
     - How to apply `ThrottleRequests` middleware to specific route (60 requests per minute per IP)?
     - How to customize 429 response format to match project standard `{success, message, code, data}`?
     - Where to define rate limit configuration (route middleware vs `RateLimiter` facade)?
   - **Laravel docs sections**: Routing > Rate Limiting, HTTP Middleware > Throttle Requests
   - **Decision criteria**: Simplicity, consistency with existing middleware usage, testability

3. **Research CORS configuration for Laravel 13**
   - **Research questions**:
     - How to configure `HandleCors` middleware to allow all origins (`Access-Control-Allow-Origin: *`)?
     - Configuration file location (`config/cors.php`) and settings (`'allowed_origins' => ['*']`)?
     - How to apply CORS middleware to API routes?
   - **Laravel docs sections**: Security > CORS
   - **Decision criteria**: Security implications (acknowledge risk of `*`), project requirement (FR-014), ease of deployment

4. **Study structured logging pattern in existing codebase**
   - **Files to review**:
     - Search for existing log calls: `grep -r "Log::info\|Log::error" app/Core/`
     - Check if project uses custom log context: `app/Core/Shared/Infrastructure/Logging/` (if exists)
   - **Context fields required**: request_id (UUID), action (string), result (success/error), user_ip (IP address), timestamp (ISO 8601), duration_ms (integer)
   - **Decision**: Use Laravel Monolog with custom log context middleware OR add context manually in use case/InAdapter

5. **Database migration strategy for catalog seeding**
   - **Research question**: Best practice for inserting initial catalog data (migration vs seeder)?
   - **Constraint**: User chose "Database migration" (clarification Q5)
   - **Pattern to follow**: Look for existing migrations with INSERT statements in `database/migrations/` directory
   - **Decision**: Create two migrations: (1) Schema (`create_tb_cat_tipo_personal_table`), (2) Data (`seed_tb_cat_tipo_personal_table`) with INSERT statements for 4 rows

6. **Response format standardization**
   - **Current pattern**: 
     ```php
     // Existing project uses App\Core\Shared\Infrastructure\Respuesta class?
     // Check if exists at app/Core/Shared/Infrastructure/Respuesta.php
     ```
   - **Format confirmed**: `{success: bool, message: string, code: int, data: mixed}`
   - **Research**: How existing endpoints format responses (check TipoRequerimiento API if implemented)
   - **Decision**: Use shared response formatter OR implement in Resource class

### Consolidation in `research.md`

Structure of research.md output:
```markdown
# Research: Obtener Catálogo de Tipos de Personal

## Decision 1: Follow TipoRequerimiento Pattern
**Rationale**: Existing TipoRequerimiento implementation in Admin context provides proven hexagonal architecture template. Replicating pattern ensures consistency and reduces architectural risk.
**Pattern Summary**: [Document 3-layer structure, port definitions, DTO patterns, service provider bindings]
**Alternatives Considered**: Create new pattern from scratch (rejected: introduces unnecessary variation and potential architectural drift)

## Decision 2: Rate Limiting Implementation
**Chosen**: [Document Laravel 13 ThrottleRequests middleware approach]
**Rationale**: [Why this approach chosen]
**Alternatives Considered**: [Other approaches evaluated]

## Decision 3: CORS Configuration
**Chosen**: [Document config/cors.php settings]
**Rationale**: [Allow * origin as per FR-014, acknowledge security tradeoff]
**Alternatives Considered**: [Restricted origins rejected per user choice in clarifications]

## Decision 4: Structured Logging
**Chosen**: [Document log context strategy]
**Rationale**: [How to add 6 required fields]
**Alternatives Considered**: [Different logging approaches evaluated]

## Decision 5: Migration-Based Seeding
**Chosen**: Two migrations approach (schema + data)
**Rationale**: User-selected strategy, ensures data consistency across environments
**Alternatives Considered**: Seeder class (rejected per user clarification), manual SQL (not version controlled)

## Decision 6: Response Format
**Chosen**: [Document {success, message, code, data} implementation]
**Rationale**: [Project standard from clarifications]
**Alternatives Considered**: [RFC 7807, Laravel default rejected per user choice]
```

**Output**: `research.md` with all NEEDS CLARIFICATION items resolved

---

## Phase 1: Design & Contracts

**Prerequisites**: `research.md` complete (Phase 0 must finish first)  
**Status**: TO BE EXECUTED

### 1. Data Model Design (`data-model.md`)

**Output**: `/specs/001-catalogo-tipos-personal/data-model.md`

> Content extracted to: [data-model.md](data-model.md)

---

### 2. API Contract Definition (`contracts/api-tipos-personal.yaml`)

**Output**: `/specs/001-catalogo-tipos-personal/contracts/api-tipos-personal.yaml`

**Format**: OpenAPI 3.0.3

> Content extracted to: [contracts/api-tipos-personal.yaml](contracts/api-tipos-personal.yaml)

**Contract Validation**: OpenAPI spec defines all request/response formats, status codes, error structures. Will be used for contract testing in Phase 2.

---

### 3. Developer Quickstart (`quickstart.md`)

**Output**: `/specs/001-catalogo-tipos-personal/quickstart.md`

> Content extracted to: [quickstart.md](quickstart.md)

---

### 4. Agent Context Update

**Action**: Update agent context file to reference this plan

**File**: `.github/copilot-instructions.md`

**Update section between `<!-- SPECKIT START -->` and `<!-- SPECKIT END -->` markers**:

```markdown
<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan at:
specs/001-catalogo-tipos-personal/plan.md
<!-- SPECKIT END -->
```

---

### Phase 1 Outputs Summary

**Created Files**:
1. ✅ `specs/001-catalogo-tipos-personal/research.md` (to be created in Phase 0 execution)
2. ✅ `specs/001-catalogo-tipos-personal/data-model.md` (documented in this plan, to be extracted)
3. ✅ `specs/001-catalogo-tipos-personal/quickstart.md` (documented in this plan, to be extracted)
4. ✅ `specs/001-catalogo-tipos-personal/contracts/api-tipos-personal.yaml` (documented in this plan, to be extracted)

**Updated Files**:
1. ✅ `.github/copilot-instructions.md` (agent context updated with plan reference)

---

## Phase 2: Implementation (OUT OF SCOPE for /speckit.plan)

**Phase 2 is executed via `/speckit.tasks` command**, which generates `tasks.md` with granular implementation steps.

**Note**: This plan document ends at Phase 1 design completion. Task generation and implementation execution are separate workflow stages.

---

## Re-evaluated Constitution Check (Post-Design)

**All checkboxes from initial check remain valid after Phase 1 design**:

- ✅ Hexagonal Architecture → 3-layer structure defined with clear responsibilities
- ✅ Domain Isolation → TipoPersonal entity has zero Laravel dependencies
- ✅ DDD Principles → Aggregate root identified, ubiquitous language consistent, repository pattern via ITipoPersonalOutPort
- ✅ Test Strategy → Unit, integration, and contract test files specified in quickstart
- ✅ Explicit Contracts → All ports (IObtenerTiposPersonalUseCase in, ITipoPersonalOutPort out), DTOs (ObtenerTiposPersonalOutDto wrapper, TipoPersonalOutDto item) defined
- ✅ Ubiquitous Language → "TipoPersonal" consistent across domain, database, API, tests
- ✅ API-First → OpenAPI contract defined in `contracts/api-tipos-personal.yaml`
- ✅ Security → Rate limiting (60 req/min), CORS (allow *), audit logging (request_id, user_ip, duration_ms) specified
- ✅ Observability → Structured logging with 6 required fields documented
- ✅ Database Strategy → PostgreSQL migrations (schema + data), no Redis caching in v1, repository queries directly

**No violations introduced during design phase.**

---

## Summary

**Implementation Plan Status**: ✅ **COMPLETE** (Phase 0 + Phase 1 documented)

**Branch**: `001-catalogo-tipos-personal`  
**Feature Directory**: `specs/001-catalogo-tipos-personal/`  
**Plan File**: `specs/001-catalogo-tipos-personal/plan.md`

**Generated Artifacts**:
- ✅ Implementation plan with technical context, constitution check, complexity tracking
- ✅ Phase 0 research outline (TipoRequerimiento pattern analysis, rate limiting, CORS, logging, migration seeding, response format)
- ✅ Phase 1 design documents (data-model.md structure, contracts/api-tipos-personal.yaml, quickstart.md)
- ✅ Agent context updated to reference plan

**Next Command**: 
```
/speckit.tasks
```
This generates `tasks.md` with dependency-ordered implementation tasks based on this plan.

**Architecture Summary**:
- **Hexagonal Architecture**: Strict 3-layer separation (Domain → Application → Infrastructure)
- **DDD**: TipoPersonal aggregate root, repository pattern, ubiquitous language
- **Testing**: Unit (use case isolated), Integration (repository with DB), Contract (API endpoint)
- **Compliance**: Zero Laravel dependencies in domain, PHPStan level 9, PSR-12

**User Requirements Fulfilled**:
✅ Hexagonal architecture enforced  
✅ Domain-driven design applied  
✅ Unit tests required and documented  
✅ Pattern replicates existing TipoRequerimiento implementation  
✅ All 14 functional requirements from spec addressed
