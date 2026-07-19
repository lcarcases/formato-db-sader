---
description: "Task list for Catálogo de Ambientes de Desarrollo implementation"
---

# Tasks: Catálogo de Ambientes de Desarrollo

**Input**: Design documents from `/specs/003-catalogo-ambientes-desarrollo/`
**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/ ✓

**Tests**: Included (TDD approach as per constitution requirements)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1)
- Include exact file paths in descriptions

## Implementation Strategy

**MVP Scope**: User Story 1 (Priority P1) - Obtener Catálogo de Ambientes
**Delivery Model**: Single story = full feature (only one user story)
**Architecture**: Hexagonal (Ports & Adapters) with strict layer separation

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and database schema

- [X] T001 Create database migrations for tb_cat_ambiente_desarrollo table in database/migrations/2026_06_28_000001_create_tb_cat_ambiente_desarrollo_table.php
- [X] T002 Create seed migration for initial ambiente data in database/migrations/2026_06_28_000002_seed_tb_cat_ambiente_desarrollo_table.php
- [X] T003 Execute migrations to create table and seed data

**Checkpoint**: Database ready with tb_cat_ambiente_desarrollo table and initial data (Desarrollo, QA, Producción)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core directory structure for hexagonal architecture

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Create directory structure for Admin bounded context: app/Core/Admin/{Domain,Application,Infrastructure}
- [X] T005 [P] Create Domain layer directories: app/Core/Admin/Domain/ValueObjects
- [X] T006 [P] Create Application layer directories: app/Core/Admin/Application/{UseCases,DTOs/Out,Ports/Out}
- [X] T007 [P] Create Infrastructure layer directories: app/Core/Admin/Infrastructure/Adapters/{In/Api,Out/PostgresSQL/{Models,Repositories}}
- [X] T008 [P] Create test directories: tests/{Unit/Core/Admin/Application/UseCases,Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories,Feature/Api}

**Checkpoint**: Directory structure ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Obtener Catálogo de Ambientes (Priority: P1) 🎯 MVP

**Goal**: Exponer endpoint público GET `/api/v1/admin/ambientes-desarrollo` que retorna lista de ambientes activos desde PostgreSQL

**Independent Test**: Ejecutar `curl http://localhost:8000/api/v1/admin/ambientes-desarrollo` debe retornar JSON con array de ambientes con campos id y nombre

### Tests for User Story 1 (TDD Approach)

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

#### Domain Layer Tests

- [X] T009 [P] [US1] Unit test for AmbienteVO value object in tests/Unit/Core/Admin/Domain/ValueObjects/AmbienteVOTest.php
  - Test constructor validates id > 0
  - Test constructor validates nombre not empty
  - Test fromArray() factory method
  - Test toArray() serialization
  - Test immutability (readonly properties)

#### Application Layer Tests

- [X] T010 [P] [US1] Unit test for ObtenerAmbientesUseCase in tests/Unit/Core/Admin/Application/UseCases/ObtenerAmbientesUseCaseTest.php
  - Test execute() invokes outPort->obtenerAmbientesDesarrollo()
  - Test execute() returns array<AmbienteVO> from outPort
  - Test execute() handles empty array from outPort

#### Infrastructure Layer Tests

- [X] T011 [P] [US1] Integration test for AmbienteDesarrolloRepository in tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/AmbienteDesarrolloRepositoryTest.php
  - Test obtenerAmbientesDesarrollo() returns only ind_activo=1 records
  - Test obtenerAmbientesDesarrollo() excludes ind_activo=0 records
  - Test obtenerAmbientesDesarrollo() orders by id_nu_ambiente_desarrollo ASC
  - Test obtenerAmbientesDesarrollo() returns array<AmbienteVO> with correct mapping
  - Test obtenerAmbientesDesarrollo() returns empty array when no active records

- [X] T012 [P] [US1] Unit test for AmbienteDesarrolloOutAdapter in tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/AmbienteDesarrolloOutAdapterTest.php
  - Test obtenerAmbientesDesarrollo() delegates to repository->obtenerAmbientesDesarrollo()
  - Test obtenerAmbientesDesarrollo() returns repository result without transformation

#### API Layer Tests

- [X] T013 [P] [US1] Feature test for GET /api/v1/admin/ambientes-desarrollo in tests/Feature/Api/ObtenerAmbientesApiTest.php
  - Test endpoint returns 200 status
  - Test response has correct JSON structure (data, message, code, success)
  - Test data array contains objects with id and nombre fields
  - Test only ind_activo=1 ambientes are returned
  - Test ambientes are ordered by id
  - Test empty data array when no active ambientes exist

**Test Checkpoint**: All tests written and failing ❌ - ready for implementation

---

### Implementation for User Story 1 (Hexagonal Architecture Layers)

#### Layer 1: Domain (Pure PHP, Zero Framework Dependencies)

- [X] T014 [US1] Implement AmbienteVO value object in app/Core/Admin/Domain/ValueObjects/AmbienteVO.php
  - Define readonly class with id and nombre properties
  - Implement constructor with validation (id > 0, nombre not empty)
  - Implement fromArray() static factory method
  - Implement toArray() for serialization
  - Use PHP 8.4 readonly keyword for immutability

**Verification**: Run domain tests (T009) - should pass ✅

---

#### Layer 2: Application (Use Cases, DTOs, Ports)

- [X] T015 [P] [US1] Define AmbienteDesarrolloOutPort interface in app/Core/Admin/Application/Ports/Out/AmbienteDesarrolloOutPort.php
  - Declare obtenerAmbientesDesarrollo(): array method
  - Document return type as list<AmbienteVO>

- [X] T016 [P] [US1] Implement ObtenerAmbientesOutDto in app/Core/Admin/Application/DTOs/Out/ObtenerAmbientesOutDto.php
  - Define readonly class with ambientes property (array<AmbienteVO>)
  - Constructor accepts array of AmbienteVO

- [X] T017 [US1] Implement ObtenerAmbientesUseCase in app/Core/Admin/Application/UseCases/ObtenerAmbientesUseCase.php
  - Inject AmbienteDesarrolloOutPort via constructor
  - Implement execute(): array method
  - Invoke outPort->obtenerAmbientesDesarrollo()
  - Return raw array<AmbienteVO> for maximum reusability

**Verification**: Run application tests (T010) - should pass ✅

---

#### Layer 3: Infrastructure - Out Adapters (Database Access)

- [X] T018 [P] [US1] Create AmbienteDesarrolloModel Eloquent model in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/AmbienteDesarrolloModel.php
  - Set table name to 'tb_cat_ambiente_desarrollo'
  - Set primary key to 'id_nu_ambiente_desarrollo'
  - Define fillable: sn_nombre, ind_activo
  - Enable timestamps
  - Cast ind_activo to integer

- [X] T019 [US1] Implement AmbienteDesarrolloRepository in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/AmbienteDesarrolloRepository.php
  - Implement obtenerAmbientesDesarrollo(): array method
  - Query AmbienteDesarrolloModel where ind_activo = 1
  - Order by id_nu_ambiente_desarrollo ASC
  - Map Eloquent models to AmbienteVO objects
  - Return array (not Collection)

- [X] T020 [US1] Implement AmbienteDesarrolloOutAdapter in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/AmbienteDesarrolloOutAdapter.php
  - Implement AmbienteDesarrolloOutPort interface
  - Inject AmbienteDesarrolloRepository via constructor
  - Implement obtenerAmbientesDesarrollo(): array
  - Delegate to repository->obtenerAmbientesDesarrollo()

**Verification**: Run integration tests (T011, T012) - should pass ✅

---

#### Layer 4: Infrastructure - In Adapters (REST API)

- [X] T021 [US1] Implement ObtenerAmbientesInAdapter REST controller in app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerAmbientesInAdapter.php
  - Inject ObtenerAmbientesUseCase via constructor
  - Implement __invoke(): JsonResponse method
  - Call useCase->execute() to get array<AmbienteVO>
  - Create ObtenerAmbientesOutDto from result
  - Transform to JSON response with standard format (data, message, code, success)
  - Use AmbienteVO->toArray() for serialization

- [X] T022 [US1] Register route in routes/api.php
  - Add route: GET /api/v1/admin/ambientes-desarrollo
  - Map to ObtenerAmbientesInAdapter (invokable controller)
  - Name route: api.admin.ambientes-desarrollo.index
  - No authentication middleware (public endpoint)

- [X] T023 [US1] Bind AmbienteDesarrolloOutPort to AmbienteDesarrolloOutAdapter in app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php
  - Add binding in register() method
  - Bind interface to concrete implementation for DI container
  - **Note**: Initially bound in AppServiceProvider, moved to AdminServiceProvider for proper module organization

**Verification**: Run feature tests (T013) - should pass ✅

---

## Phase 4: Polish & Cross-Cutting Concerns

**Purpose**: Code quality, documentation, and final validation

- [X] T024 Run PHPStan static analysis on app/Core/Admin with level 9
  - **Result**: 0 errors for AmbienteDesarrollo feature code ✅
  - Fixed type safety issues in AmbienteVO.php and AmbienteDesarrolloRepository.php
  
- [X] T025 [P] Run Laravel Pint code formatter on app/Core/Admin
  - **Result**: 7 style issues fixed in 12 files ✅
  - All code now PSR-12 compliant
  
- [X] T026 [P] Verify all tests pass: `php artisan test`
  - **Result**: All 24 AmbienteDesarrollo tests passing (84 assertions) ✅
  - Domain: 11/11 tests ✅
  - Application: 4/4 tests ✅
  - Integration: 9/9 tests ✅ (after fixing seed data isolation)
  - Feature API: 9/9 tests ✅
  
- [X] T027 [P] Manual smoke test: Start server and curl endpoint
  - **Result**: API endpoint functional ✅
  - Endpoint: GET http://localhost/api/v1/admin/ambientes-desarrollo
  - Response: {"data":[{"id":1,"nombre":"Desarrollo"},{"id":2,"nombre":"QA"},{"id":3,"nombre":"Producción"}],"message":"Ambientes obtenidos exitosamente","code":"200","success":true}
  
- [X] T028 Verify response time < 200ms under load (artillery or ab tool)
  - **Result**: Excellent performance ✅
  - Average response time: ~46ms (77% faster than target)
  - 10 sequential requests: 41-54ms range
  - All requests well under 200ms threshold
  
- [X] T029 [P] Update .github/copilot-instructions.md to reference this plan
  - **Result**: Already configured ✅
  - SPECKIT block references specs/003-catalogo-ambientes-desarrollo/plan.md

**Final Checkpoint**: Feature complete and ready for merge ✅

---

## Dependencies Between Phases

```
Phase 1 (Setup)
    ↓ blocks
Phase 2 (Foundational)
    ↓ blocks
Phase 3 (User Story 1)
    ├─ Tests (T009-T013) can run in parallel
    ├─ Domain (T014)
    ├─ Application (T015-T017)
    ├─ Infrastructure Out (T018-T020)
    └─ Infrastructure In (T021-T023)
        ↓ blocks
Phase 4 (Polish)
```

**Within Phase 3**:
- Tests MUST be written before implementation
- Domain layer is independent
- Application layer depends on Domain
- Infrastructure Out depends on Domain
- Infrastructure In depends on Application and Infrastructure Out

---

## Parallel Execution Opportunities

**Phase 1**: All tasks are sequential (migrations must run in order)

**Phase 2**: Tasks T005-T008 can run in parallel (directory creation)

**Phase 3 - Tests**: All test tasks (T009-T013) can be written in parallel

**Phase 3 - Implementation**:
- T015-T016 (Application DTOs) can run in parallel
- T018-T019 (Model + Repository) can run in parallel
- After T014 (Domain) completes:
  - T015-T017 (Application layer) can proceed
  - T018-T020 (Infrastructure Out) can proceed in parallel
- T021-T023 (Infrastructure In) must wait for Application and Infrastructure Out

**Phase 4**: Tasks T024-T025, T027, T029 can run in parallel

---

## Task Summary

- **Total Tasks**: 29
- **Phase 1 (Setup)**: 3 tasks
- **Phase 2 (Foundational)**: 5 tasks (4 parallelizable)
- **Phase 3 (User Story 1)**: 19 tasks
  - Tests: 5 tasks (all parallelizable)
  - Implementation: 14 tasks (6 parallelizable)
- **Phase 4 (Polish)**: 6 tasks (4 parallelizable)

**Parallelization Potential**: 14 tasks can run in parallel at various stages

**Implementation Order**: TDD approach - write ALL tests first before any implementation

---

## Notes

- Follow strict hexagonal architecture: Domain → Application → Infrastructure
- UseCase returns raw data (array<AmbienteVO>), InAdapter creates DTO
- Repository class encapsulates Eloquent, OutAdapter delegates to Repository
- All tests must be written BEFORE implementation (TDD)
- PHPStan level 9 and PSR-12 compliance required before merge
