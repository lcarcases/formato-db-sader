# Implementation Plan: Obtener Catálogo de Tipos de Permiso

**Branch**: `002-catalogo-tipos-permiso` | **Date**: 2026-05-31 | **Spec**: [spec.md](spec.md)  
**Input**: Feature specification from `/specs/002-catalogo-tipos-permiso/spec.md`

**Note**: This plan is filled in by the `/speckit.plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Implementar endpoint REST `GET /api/v1/admin/tipos-permiso` que expone el catálogo de tipos de permiso para base de datos (Consulta, Cambios, Eliminación, Consulta y Cambios). El endpoint retorna solo tipos activos en formato JSON estándar `{success, message, code, data}`. La implementación sigue arquitectura hexagonal dentro del bounded context Admin, replicando el patrón existente de TipoPersonal/TipoRequerimiento con persistencia en PostgreSQL y mapeo de nombres de columna prefijadas (`id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`) a modelo de dominio limpio (`id`, `nombre`, `activo`).

## Technical Context

**Language/Version**: PHP 8.4+  
**Primary Dependencies**: Laravel 13.x (infrastructure only), PHPStan 1.x, Laravel Pint  
**Storage**: PostgreSQL 16.x (source of truth), Redis 7.4.x (caching only)  
**Testing**: PHPUnit 11.x (unit/integration tests), Pest (optional), TestContainers (DB integration)  
**Target Platform**: Linux server (Docker containerized)  
**Project Type**: REST API (backend-only, no frontend)  
**Performance Goals**: <200ms response time (p95), 60 requests/minute rate limit per IP  
**Constraints**: Hexagonal architecture mandatory, domain framework-agnostic, PHPStan level 9, PSR-12  
**Scale/Scope**: Single bounded context (Admin), 4 static catalog entries, public endpoint (no auth)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.0.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with clear domain/application/infrastructure separation ✅
  - Domain: TipoPermiso entity (pure PHP, no Laravel)
  - Application: UseCase, DTOs, OutPort interface
  - Infrastructure: InAdapter (controller), OutAdapter, Repository, Eloquent model
  
- [x] **Domain Isolation**: Domain layer remains framework-agnostic with zero Laravel dependencies ✅
  - TipoPermiso.php uses only PHP primitives
  - Validation logic in domain entity
  - No Illuminate imports in domain/application layers
  
- [x] **DDD Principles**: Bounded contexts defined, aggregates identified, ubiquitous language consistent ✅
  - Bounded Context: Admin (same as TipoPersonal/TipoRequerimiento)
  - Aggregate Root: TipoPermiso (simple aggregate)
  - Ubiquitous Language: Spanish terms (TipoPermiso, Consulta, Cambios, etc.)
  
- [x] **Test Strategy**: Unit tests for use cases, integration tests for adapters, contract tests for APIs planned ✅
  - Unit: TipoPermiso entity, ObtenerTiposPermisoUseCase
  - Integration: TipoPermisoPostgresSQLRepository
  - Contract: GET /api/v1/admin/tipos-permiso endpoint
  
- [x] **Explicit Contracts**: Input/Output DTOs defined, ports (interfaces) identified for all external interactions ✅
  - OutDTO: TipoPermisoOutDto, ObtenerTiposPermisoOutDto
  - OutPort: ITipoPermisoOutPort interface
  - Response: Respuesta class with success/message/code/data
  
- [x] **Ubiquitous Language**: Domain terminology consistent across code, database, APIs, tests, docs ✅
  - Code: TipoPermiso, ObtenerTiposPermisoUseCase
  - Database: tb_cat_tipo_permiso (with convention prefix)
  - API: /api/v1/admin/tipos-permiso
  - Tests: TipoPermisoTest, ObtenerTiposPermisoApiTest
  
- [x] **API-First**: REST endpoints designed following conventions (versioning, status codes, error formats) ✅
  - Versioned: /api/v1/admin/tipos-permiso
  - Status codes: 200 (success), 429 (rate limit), 500 (error)
  - Error format: {success: false, message, code, data: []}
  - OpenAPI 3.0 specification defined
  
- [x] **Security**: Authentication/authorization strategy defined, audit logging planned ✅
  - Endpoint is public (no auth required per FR-011)
  - Rate limiting: 60 requests/minute per IP (FR-008)
  - Audit logging not required for read-only catalog endpoint
  
- [x] **Observability**: Structured logging strategy defined with appropriate context ✅
  - JSON logs with: request_id, action, result, user_ip, duration_ms (per FR-009)
  - Error logging in OutAdapter with context
  - Success/error logging in InAdapter
  
- [x] **Database Strategy**: PostgreSQL as source of truth, Redis only for caching, migration strategy defined ✅
  - PostgreSQL: tb_cat_tipo_permiso as source of truth
  - No Redis caching (catalog is static, 4 entries)
  - Migrations: Schema migration + seed data migration
  - No soft deletes (use ind_activo flag instead)

**Phase 1 Re-evaluation Result**: ✅ **ALL CHECKS PASSED** - Feature fully compliant with constitution

**Complexity Justification**: None - feature follows established patterns without constitutional exceptions.

## Project Structure

### Documentation (this feature)

```text
specs/002-catalogo-tipos-permiso/
├── plan.md              # This file (/speckit.plan command output)
├── spec.md              # Feature specification (already exists)
├── research.md          # Phase 0 output (to be generated)
├── data-model.md        # Phase 1 output (to be generated)
├── quickstart.md        # Phase 1 output (to be generated)
├── contracts/           # Phase 1 output (to be generated)
│   └── api-tipos-permiso.yaml
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created yet)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   ├── Entities/
│   │   └── TipoPermiso.php           # Entity with id, nombre, activo
│   └── Exceptions/
│       └── TipoPermisoNotFoundException.php
├── Application/
│   ├── DTOs/
│   │   └── Out/
│   │       ├── TipoPermisoOutDto.php        # Single item DTO {id, nombre}
│   │       └── ObtenerTiposPermisoOutDto.php # Collection wrapper
│   ├── Ports/
│   │   └── Out/
│   │       └── ITipoPermisoOutPort.php      # Repository interface
│   └── UseCases/
│       └── ObtenerTiposPermisoUseCase.php   # Main use case
└── Infrastructure/
    ├── Adapters/
    │   ├── In/
    │   │   └── Api/
    │   │       └── ObtenerTiposPermisoInAdapter.php  # Controller
    │   └── Out/
    │       └── PostgresSQL/
    │           ├── TipoPermisoPostgresSQLOutAdapter.php   # OutPort implementation
    │           ├── Models/
    │           │   └── TipoPermisoEloquentModel.php
    │           └── Repositories/
    │               └── TipoPermisoPostgresSQLRepository.php
    ├── Providers/
    │   └── AdminServiceProvider.php   # Update with TipoPermiso bindings
    └── Routes/
        └── AdminApiRoutes.php         # Update with /tipos-permiso route

database/migrations/
├── 2026_05_31_000001_create_tb_cat_tipo_permiso_table.php    # Schema
└── 2026_05_31_000002_seed_tb_cat_tipo_permiso_table.php      # Seed data

tests/
├── Unit/Core/Admin/
│   ├── Application/UseCases/
│   │   └── ObtenerTiposPermisoUseCaseTest.php
│   └── Domain/Entities/
│       └── TipoPermisoTest.php
├── Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/
│   └── TipoPermisoPostgresSQLRepositoryIntegrationTest.php
└── Feature/Core/Admin/Api/
    └── ObtenerTiposPermisoApiTest.php
```

**Structure Decision**: Hexagonal architecture within existing Admin bounded context. Follows established TipoPersonal/TipoRequerimiento pattern. Domain remains pure PHP, infrastructure adapters handle Laravel/PostgreSQL integration.

## Complexity Tracking

No constitutional violations. Feature adheres to all architectural principles without exceptions.

---

## Phase 0: Outline & Research

### Unknowns from Technical Context

All technical decisions are clear from constitution and existing patterns:
1. ✅ Language: PHP 8.4+
2. ✅ Framework: Laravel 13.x (infrastructure only)
3. ✅ Database: PostgreSQL with prefixed columns
4. ✅ Architecture: Hexagonal (ports & adapters)
5. ✅ Pattern: Replicate TipoPersonal implementation

### Research Tasks

1. **Analyze existing TipoPersonal pattern**: Review `app/Core/Admin/.../TipoPersonal*` files for architectural blueprint
2. **Database column naming strategy**: Confirm mapping between domain attributes (`id`, `nombre`, `activo`) and DB columns (`id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`)
3. **Response format standard**: Verify `{success, message, code, data}` implementation in existing TipoRequerimiento endpoint
4. **Rate limiting implementation**: Research Laravel's built-in rate limiting middleware configuration
5. **Structured logging pattern**: Review existing log formatting in Admin context

**Next Step**: Generate [research.md](research.md) with consolidated findings.
