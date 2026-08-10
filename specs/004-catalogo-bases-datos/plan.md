# Implementation Plan: Catálogo de Bases de Datos

**Branch**: `feature/004-catalogo-bases-datos` | **Date**: 2026-08-07 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/004-catalogo-bases-datos/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Implementar un endpoint REST de solo lectura para obtener el catálogo de bases de datos disponibles (PPB, SURI, XAMAN, OTROS). Este catálogo permite a los trabajadores de la DGTIC seleccionar la base de datos sobre la cual solicitan acceso al llenar el formato de BD. El endpoint debe ser público (sin autenticación) y responder en JSON.

**Approach**: Implementar siguiendo Arquitectura Hexagonal con un caso de uso simple de lectura y un InAdapter REST que expone el endpoint `/api/v1/admin/bases-datos`, replicando exactamente el patrón ya implementado y mergeado en `specs/003-catalogo-ambientes-desarrollo` (mismo bounded context `Admin`, mismos nombres de capas, solo cambia el concepto de dominio: "Ambiente" → "BaseDatos"). La constitución del proyecto exige usar el agente `hexagonal-architecture-specialist` para la implementación de use cases (fase `/speckit-implement`); este plan documenta el diseño que ese agente deberá seguir.

## Technical Context

**Language/Version**: PHP 8.4
**Primary Dependencies**: Laravel 13.x (solo capa de infraestructura)
**Storage**: PostgreSQL 16.x — tabla `tb_cat_base_datos`
**Testing**: PHPUnit — unit tests (use case), integration tests (repository/OutAdapter), feature/contract tests (API)
**Target Platform**: Linux server (API REST)
**Project Type**: REST API backend (Laravel-as-infrastructure)
**Performance Goals**: < 200ms respuesta bajo 50 req/s (SC-001)
**Constraints**:
- Endpoint público sin autenticación
- Respuesta JSON consistente con el envelope `{data, message, code, success}` ya usado por `ObtenerAmbientesInAdapter`
- Catálogo sembrado vía migración (no seeder de Laravel), datos deben estar siempre presentes
**Scale/Scope**: Catálogo pequeño y estático (4 registros: PPB, SURI, XAMAN, OTROS), endpoint de solo lectura

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.1.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with clear domain/application/infrastructure separation?
  - *Sí: Use case en Application, Value Object en Domain, separación clara de capas, mismo patrón que `ObtenerAmbientesUseCase`.*
- [x] **Implementation Enforcement (agent mandate)**: Use case implementation will use the `hexagonal-architecture-specialist` agent?
  - *Sí: la fase `/speckit-implement` de esta feature DEBE invocarse vía el agente `@hexagonal-architecture-specialist`, per constitución v1.1.0.*
- [x] **Domain Isolation**: Domain layer remains framework-agnostic with zero Laravel dependencies?
  - *Sí: `BaseDatosVO` es PHP puro, sin dependencias Laravel, igual que `AmbienteVO`.*
- [x] **DDD Principles**: Bounded contexts defined, aggregates identified, ubiquitous language consistent?
  - *Sí: Bounded context `Admin` (compartido con Ambientes/Tipos), `BaseDatos` es un Value Object, no un aggregate.*
- [x] **Test Strategy**: Unit tests for use cases, integration tests for adapters, contract tests for APIs planned?
  - *Sí: se planifican los 3 niveles, igual que en 003.*
- [x] **Explicit Contracts**: Input/Output DTOs defined, ports (interfaces) identified for all external interactions?
  - *Sí: `ObtenerBasesDatosOutDto` y `BaseDatosOutPort` definidos en Phase 1.*
- [x] **Ubiquitous Language**: Domain terminology consistent across code, database, APIs, tests, docs?
  - *Sí: "BaseDatos" usado consistentemente en código, tabla, endpoint y tests.*
- [x] **API-First**: REST endpoints designed following conventions (versioning, status codes, error formats)?
  - *Sí: `GET /api/v1/admin/bases-datos`, JSON responses, status codes estándar (200/500).*
- [x] **Security**: Authentication/authorization strategy defined, audit logging planned?
  - *N/A: endpoint público por requisito FR-003 del spec, no requiere autenticación (mismo criterio que ambientes-desarrollo, dato no sensible).*
- [x] **Observability**: Structured logging strategy defined with appropriate context?
  - *Sí: log de errores en el InAdapter con contexto de excepción, igual que `ObtenerAmbientesInAdapter`.*
- [x] **Database Strategy**: PostgreSQL as source of truth, Redis only for caching, migration strategy defined?
  - *Sí: PostgreSQL tabla `tb_cat_base_datos` como source of truth, sin Redis (catálogo estático), migrations definidas (schema + seed), `ind_activo` en lugar de soft deletes.*

**Complexity Justification**: No hay violaciones. Este es un caso de uso simple que replica un patrón ya validado y mergeado (003) con un concepto de dominio distinto.

## Project Structure

### Documentation (this feature)

```text
specs/004-catalogo-bases-datos/
├── plan.md              # Este archivo
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── bases-datos-api.md
└── tasks.md             # Phase 2 output (generado por /speckit-tasks)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   └── ValueObjects/
│       └── BaseDatosVO.php               # Value Object inmutable para BaseDatos
├── Application/
│   ├── DTOs/
│   │   └── Out/
│   │       └── ObtenerBasesDatosOutDto.php    # DTO de salida (usado por InAdapter)
│   ├── Ports/
│   │   └── Out/
│   │       └── BaseDatosOutPort.php           # Port Out (interface)
│   └── UseCases/
│       └── ObtenerBasesDatosUseCase.php       # Use case (retorna array<BaseDatosVO>)
└── Infrastructure/
    ├── Adapters/
    │   ├── In/
    │   │   └── Api/
    │   │       └── ObtenerBasesDatosInAdapter.php     # InAdapter REST
    │   └── Out/
    │       └── PostgresSQL/
    │           ├── Models/
    │           │   └── BaseDatosModel.php              # Eloquent model
    │           ├── Repositories/
    │           │   └── BaseDatosRepository.php         # Repository (usa Model)
    │           └── BaseDatosOutAdapter.php              # OutAdapter (implementa OutPort)
    ├── Providers/
    │   └── AdminServiceProvider.php          # (modificado) registra binding BaseDatosOutPort → BaseDatosOutAdapter
    └── Routes/
        └── AdminApiRoutes.php                # (modificado) registra GET /bases-datos

database/
└── migrations/
    ├── 2026_08_07_000001_create_tb_cat_base_datos_table.php   # Schema
    └── 2026_08_07_000002_seed_tb_cat_base_datos_table.php     # Seed data (PPB, SURI, XAMAN, OTROS)

tests/
├── Unit/
│   └── Core/
│       └── Admin/
│           └── Application/
│               └── UseCases/
│                   └── ObtenerBasesDatosUseCaseTest.php
├── Integration/
│   └── Infrastructure/
│       └── Adapters/
│           └── Out/
│               └── PostgresSQL/
│                   ├── Repositories/
│                   │   └── BaseDatosRepositoryTest.php
│                   └── BaseDatosOutAdapterTest.php
└── Feature/
    └── Api/
        └── ObtenerBasesDatosApiTest.php
```

**Structure Decision**: Estructura de proyecto único (single project), mismo bounded context `Admin` ya usado por Ambientes de Desarrollo y los catálogos de Tipos. No se crea un nuevo contexto; se añaden archivos hermanos a los ya existentes de `ObtenerAmbientes*`, y se modifican `AdminServiceProvider` y `AdminApiRoutes` (no se crean archivos nuevos para provider/routes).

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No hay violaciones. Esta sección permanece vacía.

---

## Phase 0: Research

*To be completed by research sub-agents or manual investigation*

### Research Tasks

1. **Esquema de tabla `tb_cat_base_datos`**: Definir estructura de tabla PostgreSQL (`id_nu_base_datos`, `sn_nombre`, `ind_activo`), replicando la convención de `tb_cat_ambiente_desarrollo`.
2. **Migrations en Laravel 13**: Estrategia para schema migration + seed data migration (2 archivos separados, mismo patrón que 003).
3. **Convención de nombres de columnas**: Seguir convención de tablas existentes (prefijo `id_nu_` para PK, `sn_` para strings, `ind_` para flags).
4. **Estructura de Value Objects**: Confirmar el patrón para Value Objects inmutables en PHP 8.4 (mismo patrón que `AmbienteVO`).
5. **Repository Pattern**: Implementación con Eloquent en Infrastructure layer.
6. **Testing de endpoints públicos**: Estrategias para testing de endpoints que no requieren autenticación.
7. **JSON API responses**: Formato estándar para respuestas del proyecto (ya definido en constitución y en `ObtenerAmbientesInAdapter`).
8. **`ind_activo` vs soft deletes**: Estrategia para filtrar registros activos sin usar soft deletes de Laravel.
9. **Manejo de "OTROS"**: Confirmar que, por alcance cerrado del spec, "OTROS" es una fila más del catálogo sin captura de texto libre — no requiere columna adicional ni lógica especial en el modelo de datos.

### Research Output

*Output will be documented in `research.md`*

---

## Phase 1: Design Artifacts

### Data Model

*To be defined in `data-model.md`*

### API Contracts

*To be defined in `contracts/bases-datos-api.md`*

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
specs/004-catalogo-bases-datos/plan.md
<!-- SPECKIT END -->
```
