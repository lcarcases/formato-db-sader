# Implementation Plan: Catálogo de Esquemas por Hostname

**Branch**: `006-catalogo-esquemas-hostname` | **Date**: 2026-08-30 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/006-catalogo-esquemas-hostname/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command. See
`.specify/templates/plan-template.md` for the execution workflow. Per the constitution
(`.specify/memory/constitution.md` v1.1.0 § I "Implementation Enforcement"), the actual
`/speckit-implement` phase for this feature MUST follow the Hexagonal Architecture Specialist
conventions (`.github/skills/arquitectura-hexagonal/SKILL.md` / `hexagonal-architecture-specialist`
agent) — this plan documents the design that phase must follow.

## Summary

Implementar dos endpoints REST de solo lectura para un nuevo catálogo `Esquema`
(`tb_cat_esquema`, 16 valores sembrados) y su relación muchos-a-muchos con `Hostname`
(`tb_r_hostname_esquema`, 48 asociaciones sembradas: 16 esquemas × 3 hostnames). El primero,
`GET /api/v1/admin/hostnames/{idHostname}/esquemas`, retorna los esquemas asociados a un hostname
(anteponiendo siempre la opción sintética "Todos") o 404 si el hostname no existe. El segundo,
`GET /api/v1/admin/esquemas`, retorna el catálogo completo de esquemas activos. Ambos permiten a
un trabajador de la DGTIC acotar con precisión el alcance de acceso a base de datos que solicita al
llenar el formato de BD.

**Approach**: Implementar siguiendo Arquitectura Hexagonal, replicando exactamente el patrón real
y actualmente mergeado de `specs/005-catalogo-hostnames` (mismo bounded context `Admin`, mismas
convenciones de capas verificadas directamente en el código shippeado: `HostnameVO`,
`HostnameOutPort`, `ObtenerHostnamesUseCase`, `HostnameModel`/`HostnameRepository`/
`HostnameOutAdapter`, `ObtenerHostnamesInAdapter` usando `App\Core\Shared\Infraestructure\Respuesta`
en español). Se añade una segunda variante de caso de uso/InAdapter/OutDto para el endpoint anidado
por hostname (que incluye lógica adicional: existencia de hostname vía `HostnameModel` y la entrada
sintética "Todos"), y una nueva excepción de dominio `HostnameNotFoundException` siguiendo el
patrón de `TipoPersonalNotFoundException`/`TipoPermisoNotFoundException`.

## Technical Context

**Language/Version**: PHP 8.4
**Primary Dependencies**: Laravel 13.x (solo capa de infraestructura)
**Storage**: PostgreSQL 16.x — tablas `tb_cat_esquema` (nueva) y `tb_r_hostname_esquema` (nueva,
primer pivot table del repo), más lectura de la ya existente `tb_cat_hostname`
**Testing**: PHPUnit — unit tests (use cases, VO), integration tests (repository/OutAdapter),
feature/contract tests (API)
**Target Platform**: Linux server (API REST)
**Project Type**: REST API backend (Laravel-as-infrastructure)
**Performance Goals**: < 200ms respuesta bajo 50 req/s (SC-001)
**Constraints**:
- Ambos endpoints públicos, sin autenticación, con `throttle:60,1` (igual que catálogos hermanos)
- Respuesta JSON con `App\Core\Shared\Infraestructure\Respuesta` (ortografía española),
  `{success, message, data}`, replicando el patrón real de `ObtenerHostnamesInAdapter` — **no** la
  variante `Illuminate\Http\JsonResponse` inline ni la clase `Respuesta` de ortografía inglesa
- Catálogo y pivote sembrados vía migraciones dedicadas (no `Seeder` de Laravel)
- `EsquemaRepository` hace el chequeo de existencia de hostname consultando `HostnameModel`
  directamente (mismo bounded context, misma capa Infrastructure) — no se modifica
  `HostnameOutPort`/`HostnameOutAdapter` ya mergeados en 005
- La opción sintética "Todos" (`{id: 0, nombre: "Todos"}`) nunca pasa por `EsquemaVO`; se antepone
  solo a nivel de `ObtenerEsquemasPorHostnameOutDto`/`ObtenerEsquemasPorHostnameInAdapter`
**Scale/Scope**: Catálogo pequeño y estático (16 esquemas, 48 asociaciones), dos endpoints de solo
lectura

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.1.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with clear
  domain/application/infrastructure separation?
  - *Sí: `EsquemaVO`/`HostnameNotFoundException` en Domain, Use Cases en Application dependiendo
    solo de `EsquemaOutPort`, Adapters/Models/Repository en Infrastructure — mismo patrón que
    `Hostname` (§ Core Principles I).*
- [x] **Implementation Enforcement (agent mandate)**: Use case implementation will use the
  `hexagonal-architecture-specialist` agent?
  - *Sí: `/speckit-implement` de esta feature se ejecuta siguiendo la skill
    `arquitectura-hexagonal` / `hexagonal-architecture-specialist`, per constitución v1.1.0 § I.*
- [x] **Domain Isolation**: Domain layer remains framework-agnostic with zero Laravel dependencies?
  - *Sí: `EsquemaVO` (readonly, PHP puro) y `HostnameNotFoundException` (`extends \Exception`) sin
    imports `Illuminate\*` (§ III Domain Isolation & Framework Independence).*
- [x] **DDD Principles**: Bounded contexts defined, aggregates identified, ubiquitous language
  consistent?
  - *Sí: Bounded context `Admin` (compartido); `Esquema` es un Value Object, la asociación
    Hostname-Esquema es una relación pura de infraestructura sin entidad de dominio propia
    (§ II DDD).*
- [x] **Test Strategy**: Unit tests for use cases, integration tests for adapters, contract tests
  for APIs planned?
  - *Sí: se planifican los 3 niveles para ambos casos de uso, más `EsquemaVOTest` (§ IV Test-First
    & Quality Assurance).*
- [x] **Explicit Contracts**: Input/Output DTOs defined, ports (interfaces) identified for all
  external interactions?
  - *Sí: `ObtenerEsquemaOutDto` (item), `ObtenerEsquemasOutDto` / `ObtenerEsquemasPorHostnameOutDto`
    (colecciones) y `EsquemaOutPort` definidos en Phase 1 (§ V Explicit Contracts & Immutability).*
- [x] **Ubiquitous Language**: Domain terminology consistent across code, database, APIs, tests,
  docs?
  - *Sí: "Esquema", "Hostname", "Todos" usados consistentemente (§ VI Ubiquitous Language
    Consistency).*
- [x] **API-First**: REST endpoints designed following conventions (versioning, status codes,
  error formats)?
  - *Sí: `GET /api/v1/admin/esquemas` y `GET /api/v1/admin/hostnames/{idHostname}/esquemas`, JSON
    responses, status codes 200/404/500 (§ VIII API-First Design).*
- [x] **Security**: Authentication/authorization strategy defined, audit logging planned?
  - *N/A: endpoints públicos por requisito FR-011 del spec, mismo criterio que el resto de
    catálogos `Admin` (información técnica interna de la DGTIC, no sensible).*
- [x] **Observability**: Structured logging strategy defined with appropriate context?
  - *Sí: excepciones logueadas vía `Respuesta::errorResponse()` (que expone detalles solo en modo
    debug) — mismo patrón que `ObtenerHostnamesInAdapter`.*
- [x] **Database Strategy**: PostgreSQL as source of truth, Redis only for caching, migration
  strategy defined?
  - *Sí: PostgreSQL, sin Redis (catálogos estáticos), migrations definidas (schema + seed para
    ambas tablas nuevas), `ind_activo` en lugar de soft deletes (§ Database & Persistence
    Policies).*

**Complexity Justification**: No hay violaciones. Esta feature introduce la primera tabla pivote
(`tb_r_hostname_esquema`) del repositorio; se justifica porque el spec (FR-007) exige una
relación muchos-a-muchos explícita entre `Esquema` y `Hostname`, y la convención de nomenclatura
`tb_{context}_{entity}` se extiende con el infijo `r` (documentado en
`specs/006-catalogo-esquemas-hostname/open-questions-response.md`, Stage 0 pregunta 2) para
distinguir tablas de relación pura de las de catálogo (`cat`).

## Project Structure

### Documentation (this feature)

```text
specs/006-catalogo-esquemas-hostname/
├── plan.md              # Este archivo
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── esquemas-api.md
├── open-questions-response.md
└── tasks.md             # Phase 2 output (generado por /speckit-tasks)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   ├── ValueObjects/
│   │   └── EsquemaVO.php                          # Value Object inmutable para Esquema
│   └── Exceptions/
│       └── HostnameNotFoundException.php          # Excepción de dominio (404)
├── Application/
│   ├── DTOs/
│   │   └── Out/
│   │       ├── ObtenerEsquemaOutDto.php                    # DTO de salida por item
│   │       ├── ObtenerEsquemasOutDto.php                   # DTO colección (catálogo completo)
│   │       └── ObtenerEsquemasPorHostnameOutDto.php        # DTO colección (anida "Todos")
│   ├── Ports/
│   │   └── Out/
│   │       └── EsquemaOutPort.php                          # Port Out (interface)
│   └── UseCases/
│       ├── ObtenerEsquemasUseCase.php                      # Catálogo completo
│       └── ObtenerEsquemasPorHostnameUseCase.php           # Por hostname (throws HostnameNotFoundException)
└── Infrastructure/
    ├── Adapters/
    │   ├── In/
    │   │   └── Api/
    │   │       ├── ObtenerEsquemasInAdapter.php            # InAdapter REST (catálogo completo)
    │   │       └── ObtenerEsquemasPorHostnameInAdapter.php # InAdapter REST (por hostname)
    │   └── Out/
    │       └── PostgresSQL/
    │           ├── Models/
    │           │   ├── EsquemaModel.php                    # Eloquent model (tb_cat_esquema)
    │           │   └── HostnameEsquemaModel.php            # Eloquent model pivot (tb_r_hostname_esquema)
    │           ├── Repositories/
    │           │   └── EsquemaRepository.php               # Repository (usa EsquemaModel, HostnameEsquemaModel, HostnameModel)
    │           └── EsquemaOutAdapter.php                   # OutAdapter (implementa EsquemaOutPort)
    ├── Providers/
    │   └── AdminServiceProvider.php          # (modificado) registra binding EsquemaOutPort → EsquemaOutAdapter
    └── Routes/
        └── AdminApiRoutes.php                # (modificado) registra GET /esquemas y GET /hostnames/{idHostname}/esquemas

database/
└── migrations/
    ├── 2026_08_30_000001_create_tb_cat_esquema_table.php            # Schema tb_cat_esquema
    ├── 2026_08_30_000002_seed_tb_cat_esquema_table.php              # Seed 16 esquemas
    ├── 2026_08_30_000003_create_tb_r_hostname_esquema_table.php   # Schema tb_r_hostname_esquema
    └── 2026_08_30_000004_seed_tb_r_hostname_esquema_table.php     # Seed 48 asociaciones

tests/
├── Unit/
│   └── Core/
│       └── Admin/
│           ├── Domain/
│           │   └── ValueObjects/
│           │       └── EsquemaVOTest.php
│           └── Application/
│               └── UseCases/
│                   ├── ObtenerEsquemasUseCaseTest.php
│                   └── ObtenerEsquemasPorHostnameUseCaseTest.php
├── Integration/
│   └── Core/
│       └── Admin/
│           └── Infrastructure/
│               └── Adapters/
│                   └── Out/
│                       └── PostgresSQL/
│                           ├── Repositories/
│                           │   └── EsquemaRepositoryIntegrationTest.php
│                           └── EsquemaOutAdapterIntegrationTest.php
└── Feature/
    └── Core/
        └── Admin/
            └── Api/
                ├── ObtenerEsquemasApiTest.php
                └── ObtenerEsquemasPorHostnameApiTest.php
```

**Structure Decision**: Estructura de proyecto único (single project), mismo bounded context
`Admin` ya usado por `Hostname`/`BaseDatos`/Ambientes/Tipos. No se crea un nuevo contexto; se
añaden archivos hermanos a los ya existentes de `ObtenerHostnames*`, y se modifican
`AdminServiceProvider` y `AdminApiRoutes` (no se crean archivos nuevos para provider/routes). Las
rutas de tests y migraciones siguen las ubicaciones **reales** verificadas en el árbol del repo
para 005 (`tests/Unit/Core/Admin/...`, `tests/Integration/Core/Admin/...`,
`tests/Feature/Core/Admin/Api/...`), incluyendo el patrón real (verificado en código, no solo en
`data-model.md` de 005) donde `{Entity}Repository` retorna Eloquent models crudos y el
`{Entity}OutAdapter` hace el mapeo a Value Object.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No hay violaciones. Esta sección permanece vacía.

---

## Phase 0: Research

Ver `research.md` para el detalle completo. Resumen de decisiones:

1. Esquema de tablas `tb_cat_esquema` y `tb_r_hostname_esquema` (primera tabla pivote del repo).
2. Migrations en Laravel 13: 4 archivos (2 schema + 2 seed), mismo patrón que 004/005.
3. Convención de nombres: `id_nu_` (PK), `sn_` (string), `ind_` (flag), y el nuevo infijo `r`
   para tablas de relación pura.
4. Repository pattern: `EsquemaRepository` retorna Eloquent models crudos (`EsquemaModel` /
   `HostnameEsquemaModel`), igual que `HostnameRepository` — el mapeo a `EsquemaVO` ocurre en
   `EsquemaOutAdapter`.
5. Verificación de existencia de hostname sin modificar `HostnameOutPort`/`HostnameOutAdapter` de
   005: `EsquemaRepository` consulta `HostnameModel::query()->find($idHostname)` directamente.
6. Estrategia para la entrada sintética "Todos": nunca persistida, nunca pasa por `EsquemaVO`;
   se antepone en `ObtenerEsquemasPorHostnameOutDto::toArray()` (o en el InAdapter, según se
   confirme en Phase 1) como `{id: 0, nombre: 'Todos'}`.
7. Distinción `null` (hostname no encontrado) vs `[]` (hostname válido sin asociaciones) en
   `EsquemaOutPort::obtenerEsquemasPorHostname()`.
8. Formato de respuesta JSON: `App\Core\Shared\Infraestructure\Respuesta` (español),
   confirmado contra el código real de `ObtenerHostnamesInAdapter`.

## Phase 1: Design Artifacts

### Data Model

Ver `data-model.md`.

### API Contracts

Ver `contracts/esquemas-api.md`.

### Quick Start Guide

Ver `quickstart.md`.

---

## Phase 2: Task Breakdown

*To be generated by `/speckit-tasks` command - NOT part of `/speckit-plan` output*

---

## Agent Context Update

Después de completar Phase 1, actualizar `.github/copilot-instructions.md` para referenciar este
plan:

```markdown
<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan at:
specs/006-catalogo-esquemas-hostname/plan.md
<!-- SPECKIT END -->
```
