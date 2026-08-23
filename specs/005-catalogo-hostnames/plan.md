# Implementation Plan: Catálogo de Hostnames

**Branch**: `005-catalogo-hostnames` | **Date**: 2026-08-22 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/005-catalogo-hostnames/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Implementar un endpoint REST de solo lectura para obtener el catálogo de hostnames/direcciones IP
disponibles (11 valores sembrados: 7 hostnames de servidor + 4 direcciones IP). Este catálogo
permite a los trabajadores de la DGTIC seleccionar el servidor objetivo al llenar el formato de
solicitud de acceso a base de datos. El endpoint debe ser público (sin autenticación) y responder
en JSON.

**Approach**: Implementar siguiendo Arquitectura Hexagonal con un caso de uso simple de lectura y
un InAdapter REST que expone el endpoint `/api/v1/admin/hostnames`, replicando exactamente el
patrón ya implementado y mergeado en `specs/004-catalogo-bases-datos` (mismo bounded context
`Admin`, mismos nombres de capas, solo cambia el concepto de dominio: "BaseDatos" → "Hostname").
La constitución del proyecto exige usar el agente `hexagonal-architecture-specialist` para la
implementación de use cases (fase `/speckit-implement`); este plan documenta el diseño que ese
agente deberá seguir.

## Technical Context

**Language/Version**: PHP 8.4
**Primary Dependencies**: Laravel 13.x (solo capa de infraestructura)
**Storage**: PostgreSQL 16.x — tabla `tb_cat_hostname`
**Testing**: PHPUnit — unit tests (use case), integration tests (repository/OutAdapter), feature/contract tests (API)
**Target Platform**: Linux server (API REST)
**Project Type**: REST API backend (Laravel-as-infrastructure)
**Performance Goals**: < 200ms respuesta bajo 50 req/s (SC-001)
**Constraints**:
- Endpoint público sin autenticación
- Respuesta JSON consistente con el envelope `{data, message, code, success}`, construido inline
  con `response()->json([...])` en el InAdapter — mismo patrón que `ObtenerBasesDatosInAdapter`
  (NO se usa ninguna de las dos clases `Respuesta` compartidas)
- Catálogo sembrado vía migración (no seeder de Laravel), datos deben estar siempre presentes
- Sin distinción estructural entre "hostname" e "IP": ambos son cadenas planas en la misma columna
**Scale/Scope**: Catálogo pequeño y estático (11 registros: 7 hostnames + 4 IPs), endpoint de solo lectura

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.1.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with clear domain/application/infrastructure separation?
  - *Sí: Use case en Application, Value Object en Domain, separación clara de capas, mismo patrón que `ObtenerBasesDatosUseCase` (§ Core Principles I, `.specify/memory/constitution.md`).*
- [x] **Implementation Enforcement (agent mandate)**: Use case implementation will use the `hexagonal-architecture-specialist` agent?
  - *Sí: la fase `/speckit-implement` de esta feature DEBE invocarse vía el agente `@hexagonal-architecture-specialist`, per constitución v1.1.0 § I "Implementation Enforcement".*
- [x] **Domain Isolation**: Domain layer remains framework-agnostic with zero Laravel dependencies?
  - *Sí: `HostnameVO` es PHP puro, sin dependencias Laravel, igual que `BaseDatosVO` (§ III Domain Isolation & Framework Independence).*
- [x] **DDD Principles**: Bounded contexts defined, aggregates identified, ubiquitous language consistent?
  - *Sí: Bounded context `Admin` (compartido con BasesDatos/Ambientes/Tipos), `Hostname` es un Value Object, no un aggregate (§ II DDD).*
- [x] **Test Strategy**: Unit tests for use cases, integration tests for adapters, contract tests for APIs planned?
  - *Sí: se planifican los 3 niveles, igual que en 004 (§ IV Test-First & Quality Assurance).*
- [x] **Explicit Contracts**: Input/Output DTOs defined, ports (interfaces) identified for all external interactions?
  - *Sí: `ObtenerHostnamesOutDto` (colección) + `ObtenerHostnameOutDto` (item) y `HostnameOutPort` definidos en Phase 1 (§ V Explicit Contracts & Immutability).*
- [x] **Ubiquitous Language**: Domain terminology consistent across code, database, APIs, tests, docs?
  - *Sí: "Hostname" usado consistentemente en código, tabla, endpoint y tests (§ VI Ubiquitous Language Consistency).*
- [x] **API-First**: REST endpoints designed following conventions (versioning, status codes, error formats)?
  - *Sí: `GET /api/v1/admin/hostnames`, JSON responses, status codes estándar (200/500) (§ VIII API-First Design).*
- [x] **Security**: Authentication/authorization strategy defined, audit logging planned?
  - *N/A: endpoint público por requisito FR-003 del spec, no requiere autenticación (mismo criterio que `bases-datos`/`ambientes-desarrollo`, dato no sensible — información técnica interna de la DGTIC).*
- [x] **Observability**: Structured logging strategy defined with appropriate context?
  - *Sí: log de errores en el InAdapter con contexto de excepción, igual que `ObtenerBasesDatosInAdapter` (§ Observability & Logging).*
- [x] **Database Strategy**: PostgreSQL as source of truth, Redis only for caching, migration strategy defined?
  - *Sí: PostgreSQL tabla `tb_cat_hostname` como source of truth, sin Redis (catálogo estático), migrations definidas (schema + seed), `ind_activo` en lugar de soft deletes (§ Database & Persistence Policies).*

**Complexity Justification**: No hay violaciones. Este es un caso de uso simple que replica un
patrón ya validado y mergeado (004) con un concepto de dominio distinto y sin lógica adicional
(no hay distinción estructural hostname/IP, per decisión cerrada del spec).

## Project Structure

### Documentation (this feature)

```text
specs/005-catalogo-hostnames/
├── plan.md              # Este archivo
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── hostnames-api.md
└── tasks.md             # Phase 2 output (generado por /speckit-tasks)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   └── ValueObjects/
│       └── HostnameVO.php                 # Value Object inmutable para Hostname
├── Application/
│   ├── DTOs/
│   │   └── Out/
│   │       ├── ObtenerHostnameOutDto.php      # DTO de salida por item
│   │       └── ObtenerHostnamesOutDto.php     # DTO de salida colección (usado por InAdapter)
│   ├── Ports/
│   │   └── Out/
│   │       └── HostnameOutPort.php            # Port Out (interface)
│   └── UseCases/
│       └── ObtenerHostnamesUseCase.php        # Use case (retorna array<HostnameVO>)
└── Infrastructure/
    ├── Adapters/
    │   ├── In/
    │   │   └── Api/
    │   │       └── ObtenerHostnamesInAdapter.php   # InAdapter REST
    │   └── Out/
    │       └── PostgresSQL/
    │           ├── Models/
    │           │   └── HostnameModel.php            # Eloquent model
    │           ├── Repositories/
    │           │   └── HostnameRepository.php       # Repository (usa Model)
    │           └── HostnameOutAdapter.php            # OutAdapter (implementa OutPort)
    ├── Providers/
    │   └── AdminServiceProvider.php          # (modificado) registra binding HostnameOutPort → HostnameOutAdapter
    └── Routes/
        └── AdminApiRoutes.php                # (modificado) registra GET /hostnames

database/
└── migrations/
    ├── 2026_08_22_000001_create_tb_cat_hostname_table.php   # Schema
    └── 2026_08_22_000002_seed_tb_cat_hostname_table.php     # Seed data (11 valores)

tests/
├── Unit/
│   └── Core/
│       └── Admin/
│           └── Application/
│               └── UseCases/
│                   └── ObtenerHostnamesUseCaseTest.php
├── Integration/
│   └── Core/
│       └── Admin/
│           └── Infrastructure/
│               └── Adapters/
│                   └── Out/
│                       └── PostgresSQL/
│                           ├── Repositories/
│                           │   └── HostnameRepositoryIntegrationTest.php
│                           └── HostnameOutAdapterIntegrationTest.php
└── Feature/
    └── Core/
        └── Admin/
            └── Api/
                └── ObtenerHostnamesApiTest.php
```

**Structure Decision**: Estructura de proyecto único (single project), mismo bounded context
`Admin` ya usado por Bases de Datos, Ambientes de Desarrollo y los catálogos de Tipos. No se crea
un nuevo contexto; se añaden archivos hermanos a los ya existentes de `ObtenerBasesDatos*`, y se
modifican `AdminServiceProvider` y `AdminApiRoutes` (no se crean archivos nuevos para
provider/routes). Las rutas de tests y migraciones mirroran las ubicaciones **reales** verificadas
en el árbol del repo para la feature 004 (`tests/Unit/Core/Admin/...`,
`tests/Integration/Core/Admin/...`, `tests/Feature/Core/Admin/Api/...`), no las descritas
originalmente (con ligeras imprecisiones) en `specs/004-catalogo-bases-datos/plan.md`.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No hay violaciones. Esta sección permanece vacía.

---

## Phase 0: Research

*To be completed by research sub-agents or manual investigation*

### Research Tasks

1. **Esquema de tabla `tb_cat_hostname`**: Definir estructura de tabla PostgreSQL
   (`id_nu_hostname`, `sn_nombre`, `ind_activo`), replicando la convención de `tb_cat_base_datos`.
2. **Migrations en Laravel 13**: Estrategia para schema migration + seed data migration (2 archivos
   separados, mismo patrón que 004).
3. **Convención de nombres de columnas**: Seguir convención de tablas existentes (prefijo `id_nu_`
   para PK, `sn_` para strings, `ind_` para flags).
4. **Estructura de Value Objects**: Confirmar el patrón para Value Objects inmutables en PHP 8.4
   (mismo patrón que `BaseDatosVO`).
5. **Repository Pattern**: Implementación con Eloquent en Infrastructure layer.
6. **Testing de endpoints públicos**: Estrategias para testing de endpoints que no requieren
   autenticación.
7. **JSON API responses**: Formato estándar para respuestas del proyecto, construido inline en el
   InAdapter (ya definido en constitución y en `ObtenerBasesDatosInAdapter`).
8. **`ind_activo` vs soft deletes**: Estrategia para filtrar registros activos sin usar soft
   deletes de Laravel.
9. **Ausencia de distinción hostname/IP**: Confirmar que, por alcance cerrado del spec, no existe
   columna de tipo ni validación de formato — ambos conviven como cadenas planas en `sn_nombre`.
10. **Normalización de valores**: Confirmar que, a diferencia de `tb_cat_base_datos` (códigos
    normalizados a mayúsculas), los valores de hostname se almacenan exactamente como fueron
    provistos, sin normalización.

### Research Output

*Output will be documented in `research.md`*

---

## Phase 1: Design Artifacts

### Data Model

*To be defined in `data-model.md`*

### API Contracts

*To be defined in `contracts/hostnames-api.md`*

### Quick Start Guide

*To be defined in `quickstart.md`*

---

## Phase 2: Task Breakdown

*To be generated by `/speckit-tasks` command - NOT part of `/speckit-plan` output*

---

## Agent Context Update

After Phase 1 completion, update `.github/copilot-instructions.md` to reference this plan:

```markdown
<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan at:
specs/005-catalogo-hostnames/plan.md
<!-- SPECKIT END -->
```
