# Implementation Plan: Catálogo de Ambientes de Desarrollo

**Branch**: `feature/003-catalogo-ambientes-desarrollo` | **Date**: 2026-06-28 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/003-catalogo-ambientes-desarrollo/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Implementar un endpoint REST para obtener el catálogo de ambientes de desarrollo disponibles. Este catálogo permite a los clientes del API seleccionar dinámicamente el ambiente con el cual desean interactuar (por ejemplo: desarrollo, QA, producción). El endpoint debe ser público (sin autenticación), responder en JSON.

**Approach**: Implementar siguiendo Arquitectura Hexagonal con un caso de uso simple de lectura y un inAdapter REST que expone el endpoint `/api/v1/admin/ambientes-desarrollo`. Usar el agente `hexagonal-architecture-specialist` como lo requiere la constitución del proyecto.

## Technical Context

**Language/Version**: PHP 8.4  
**Primary Dependencies**: Laravel 13.x (solo capa de infraestructura) 
**Testing**: PHPUnit para unit tests (use cases), integration tests (adapters), contract tests (API)  
**Target Platform**: Linux server (API REST)
**Project Type**: REST API / Microservicio  
**Performance Goals**: < 200ms respuesta bajo 50 req/s  
**Constraints**: 
- Endpoint público sin autenticación
- Respuesta JSON consistente
- Configuración debe poder actualizarse sin redesplegar aplicación
**Scale/Scope**: Catálogo pequeño (~3-10 ambientes típicamente), endpoints de solo lectura

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Verify compliance with SADER API Constitution (v1.0.0):**

- [x] **Hexagonal Architecture**: Feature design respects ports & adapters pattern with clear domain/application/infrastructure separation?
  - *Sí: Use case en Application, VALUE OBJECT en Domain, separación clara de capas*
- [x] **Domain Isolation**: Domain layer remains framework-agnostic with zero Laravel dependencies?
  - *Sí: ValueObject AmbienteVO es PHP puro, sin dependencias Laravel*
- [x] **DDD Principles**: Bounded contexts defined, aggregates identified, ubiquitous language consistent?
  - *Sí: Bounded context Admin, Ambiente es un Value Object, lenguaje consistente*
- [x] **Test Strategy**: Unit tests for use cases, integration tests for adapters, contract tests for APIs planned?
  - *Sí: Se planificarán todos los niveles de tests*
- [x] **Explicit Contracts**: Input/Output DTOs defined, ports (interfaces) identified for all external interactions?
  - *Sí: DTOs para output definidos claramente*
- [x] **Ubiquitous Language**: Domain terminology consistent across code, database, APIs, tests, docs?
  - *Sí: "Ambiente" usado consistentemente*
- [x] **API-First**: REST endpoints designed following conventions (versioning, status codes, error formats)?
  - *Sí: `/api/v1/admin/ambientes-desarrollo`, JSON responses, status codes estándar*
- [x] **Security**: Authentication/authorization strategy defined, audit logging planned?
  - *N/A: Endpoint público por requisito FR-004, no requiere autenticación*
- [x] **Observability**: Structured logging strategy defined with appropriate context?
  - *Sí: Log con request_id context*
- [x] **Database Strategy**: PostgreSQL as source of truth, Redis only for caching, migration strategy defined?
  - *Sí: PostgreSQL tabla tb_cat_ambiente_desarrollo como source of truth, sin Redis (catálogo estático), migrations definidas (schema + seed), ind_activo en lugar de soft deletes*

**Complexity Justification**: No hay violaciones. Este es un caso de uso simple que cumple completamente con la constitución.

## Project Structure

### Documentation (this feature)

```text
specs/003-catalogo-ambientes-desarrollo/
├── plan.md              # Este archivo
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── ambientes-api.md
└── tasks.md             # Phase 2 output (generado por /speckit.tasks)
```

### Source Code (repository root)

```text
app/Core/Admin/
├── Domain/
│   └── ValueObjects/
│       └── AmbienteVO.php        # Value Object inmutable para Ambiente
├── Application/
│   ├── DTOs/
│   │   └── Out/
│   │       └── ObtenerAmbientesOutDto.php       # DTO de salida (usado por InAdapter)
│   ├── Ports/
│   │   └── Out/
│   │       └── AmbienteDesarrolloOutPort.php    # Port Out (interface)
│   └── UseCases/
│       └── ObtenerAmbientesUseCase.php          # Use case (retorna array<AmbienteVO>)
└── Infrastructure/
    └── Adapters/
        ├── In/
        │   └── Api/
        │       └── ObtenerAmbientesInAdapter.php         # InAdapter REST (crea OutDto)
        └── Out/
            └── PostgresSQL/
                ├── Models/
                │   └── AmbienteDesarrolloModel.php       # Eloquent model
                ├── Repositories/
                │   └── AmbienteDesarrolloRepository.php  # Repository (usa Model)
                └── AmbienteDesarrolloOutAdapter.php      # OutAdapter (usa Repository)

database/
└── migrations/
    ├── 2026_06_28_000001_create_tb_cat_ambiente_desarrollo_table.php  # Schema
    └── 2026_06_28_000002_seed_tb_cat_ambiente_desarrollo_table.php   # Seed data

routes/
└── api.php                         # Registro de ruta /api/v1/admin/ambientes-desarrollo

tests/
├── Unit/
│   └── Core/
│       └── Admin/
│           └── Application/
│               └── UseCases/
│                   └── ObtenerAmbientesUseCaseTest.php
├── Integration/
│   └── Infrastructure/
│       └── Adapters/
│           └── Out/
│               └── PostgresSQL/
│                   ├── Repositories/
│                   │   └── AmbienteDesarrolloRepositoryTest.php
│                   └── AmbienteDesarrolloOutAdapterTest.php
└── Feature/
    └── Api/
        └── ObtenerAmbientesApiTest.php
```

**Structure Decision**: Estructura de proyecto único (single project). Todo el código vive en `app/Core/Admin/` siguiendo la estructura hexagonal estándar. Este bounded context (Admin) manejará catálogos y configuraciones del sistema.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No hay violaciones. Esta sección permanece vacía.

---

## Phase 0: Research

*To be completed by research sub-agents or manual investigation*

### Research Tasks

1. **Esquema de tabla tb_cat_ambiente_desarrollo**: Definir estructura de tabla PostgreSQL (id_nu_ambiente_desarrollo, sn_nombre, ind_activo)
2. **Migrations en Laravel 13**: Estrategia para schema migration + seed data migration
3. **Convención de nombres de columnas**: Seguir convención de tablas existentes (prefijo id_nu_ para PK, sn_ para strings)
4. **Estructura de Value Objects**: Confirmar el patrón para Value Objects inmutables en PHP 8.4
5. **Repository Pattern**: Implementación con Eloquent en Infrastructure layer
6. **Testing de endpoints públicos**: Estrategias para testing de endpoints que no requieren autenticación
7. **JSON API responses**: Formato estándar para respuestas del proyecto (ya definido en constitución)
8. **ind_activo vs soft deletes**: Estrategia para filtrar registros activos sin usar soft deletes de Laravel

### Research Output

*Output will be documented in `research.md`*

---

## Phase 1: Design Artifacts

### Data Model

*To be defined in `data-model.md`*

### API Contracts

*To be defined in `contracts/ambientes-api.md`*

### Quick Start Guide

*To be defined in `quickstart.md`*

---

## Phase 2: Task Breakdown

*To be generated by `/speckit.tasks` command - NOT part of `/speckit.plan` output*

---

## Agent Context Update

After Phase 1 completion, update `.github/copilot-instructions.md` to reference this plan:

```markdown
<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan at:
specs/003-catalogo-ambientes-desarrollo/plan.md
<!-- SPECKIT END -->
```
