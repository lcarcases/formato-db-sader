---
description: "Implementation tasks for Obtener Catálogo de Tipos de Personal"
---

# Tasks: Obtener Catálogo de Tipos de Personal

**Input**: Design documents from `/specs/001-catalogo-tipos-personal/`
**Prerequisites**: plan.md ✅, spec.md ✅
**Feature Branch**: `001-catalogo-tipos-personal`

**Organization**: Tasks organized by implementation phase, with User Story 1 as the core MVP

## Format: `- [ ] [ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1 for User Story 1)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Research and understand existing patterns before implementation

- [X] T001 Read arquitectura-hexagonal skill documentation at `.github/skills/arquitectura-hexagonal/SKILL.md`
- [X] T002 Analyze TipoRequerimiento pattern: review `app/Core/Admin/Application/Ports/In/IObtenerTipoRequerimientoUseCase.php`
- [X] T003 [P] Analyze TipoRequerimiento outbound port: review `app/Core/Admin/Application/Ports/Out/ITipoRequerimientoOutPort.php`
- [X] T004 [P] Analyze TipoRequerimiento DTOs: review `app/Core/Admin/Application/DTOs/Out/ObtenerTipoRequerimientoOutDto.php`
- [X] T005 [P] Analyze use case implementation: review `app/Core/Admin/Application/UseCases/ObtenerTiposRequerimientosUseCase.php`
- [X] T006 Analyze InAdapter pattern: review `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerTiposRequerimientosInAdapter.php`
- [X] T007 [P] Analyze repository pattern: review `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/TipoRequerimientoPostgresSQLRepository.php`
- [X] T008 [P] Analyze route registration: review `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php`
- [X] T009 [P] Analyze service provider bindings: review `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`
- [X] T010 [P] Verify Respuesta class exists at `app/Core/Shared/Infrastructure/Respuesta.php`
- [X] T011 Document rate limiting approach: research Laravel 13 ThrottleRequests middleware with 60 req/min per IP
- [X] T012 [P] Document CORS configuration: check `config/cors.php` for allow-all origins setting
- [X] T013 [P] Document structured logging: search for existing Log::info patterns with context in `app/Core/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database infrastructure that MUST be complete before User Story 1

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T014 Create database migration schema in `database/migrations/YYYY_MM_DD_HHMMSS_create_tb_cat_tipo_personal_table.php`
- [X] T015 Create database migration for seeding in `database/migrations/YYYY_MM_DD_HHMMSS_seed_tb_cat_tipo_personal_table.php`
- [X] T016 Run migrations to create table and insert 4 initial records (Base, Enlace, Confianza, Externo)

**Checkpoint**: Database ready - User Story 1 implementation can now begin in parallel

---

## Phase 3: User Story 1 - Consultar Catálogo de Tipos de Personal (Priority: P1) 🎯 MVP

**Goal**: Exponer endpoint REST API `GET /api/v1/admin/tipos-personal` que retorne catálogo de tipos de personal activos siguiendo arquitectura hexagonal

**Independent Test**: Llamar `GET /api/v1/admin/tipos-personal` y verificar que retorna 200 OK con array JSON de 4 tipos de personal `{id, nombre}` envuelto en respuesta estándar `{success: true, message, code: 200, data}`

### Domain Layer (Pure PHP, framework-agnostic)

- [X] T017 [P] [US1] Create TipoPersonal entity in `app/Core/Admin/Domain/Entities/TipoPersonal.php` (Use @Hexagonal Architecture Specialist agent)
- [X] T018 [P] [US1] Create TipoPersonalNotFoundException domain exception in `app/Core/Admin/Domain/Exceptions/TipoPersonalNotFoundException.php` (Use @Hexagonal Architecture Specialist agent)

### Application Layer (Use cases, ports, DTOs)

- [X] T019 [P] [US1] Define ITipoPersonalOutPort repository interface in `app/Core/Admin/Application/Ports/Out/ITipoPersonalOutPort.php` (Use @Hexagonal Architecture Specialist agent)
- [X] T020 [P] [US1] Create TipoPersonalOutDto item DTO in `app/Core/Admin/Application/DTOs/Out/TipoPersonalOutDto.php` (Use @Hexagonal Architecture Specialist agent)
- [X] T021 [P] [US1] Create ObtenerTiposPersonalOutDto wrapper DTO in `app/Core/Admin/Application/DTOs/Out/ObtenerTiposPersonalOutDto.php` (Use @Hexagonal Architecture Specialist agent)
- [X] T022 [US1] Define IObtenerTiposPersonalUseCase inbound port interface in `app/Core/Admin/Application/Ports/In/IObtenerTiposPersonalUseCase.php` (Use @Hexagonal Architecture Specialist agent - note: use PLURAL TiposPersonal to match returned collection)
- [X] T023 [US1] Implement ObtenerTiposPersonalUseCase application service in `app/Core/Admin/Application/UseCases/ObtenerTiposPersonalUseCase.php` (Use @Hexagonal Architecture Specialist agent)

### Infrastructure Layer (Laravel-specific adapters)

- [X] T024 [P] [US1] Create TipoPersonalEloquentModel in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/TipoPersonalEloquentModel.php`
- [X] T025 [US1] Implement TipoPersonalPostgresSQLRepository in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoPersonalPostgresSQLRepository.php`
- [X] T026 [US1] Create TipoPersonalPostgresSQLOutAdapter orchestrator in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/TipoPersonalPostgresSQLOutAdapter.php`
- [X] T027 [US1] Create ObtenerTiposPersonalInAdapter HTTP controller in `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerTiposPersonalInAdapter.php` (note: InAdapter name uses PLURAL TiposPersonal to match use case)
- [X] T028 [US1] Register tipos-personal route in `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` with rate limiting middleware (apply ThrottleRequests::class.':60,1' to limit 60 requests per minute per IP)
- [X] T029 [US1] Wire dependency injection in `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`

### Cross-cutting Concerns

- [X] T030 [US1] Add structured logging to ObtenerTiposPersonalInAdapter (NOT use case) with context: request_id (UUID::v4()), action ("ObtenerTiposPersonal"), result ("success"/"error"), user_ip (request()->ip()), timestamp (now()->toIso8601String()), duration_ms (calculate from microtime). Logging MUST be in Infrastructure layer to maintain hexagonal architecture (Application layer must be framework-agnostic).
- [X] T031 [US1] Verify CORS configuration in `config/cors.php` allows all origins for `/api/v1/admin/*` routes
- [X] T032 [US1] Test endpoint manually: `curl http://localhost/api/v1/admin/tipos-personal`
- [X] T033 [US1] Validate response format matches spec: `{success: true, message: string, code: 200, data: [{id, nombre}]}`
- [X] T034 [US1] Test rate limiting: verify 429 response after 60 requests/minute from same IP

**Checkpoint**: User Story 1 complete and testable independently

---

## Phase 4: Quality Assurance & Testing

**Purpose**: Ensure code quality and architectural compliance

- [X] T035 [P] Write unit test for TipoPersonal domain entity in `tests/Unit/Core/Admin/Domain/Entities/TipoPersonalTest.php`
- [X] T036 [P] Write unit test for ObtenerTiposPersonalUseCase in `tests/Unit/Core/Admin/Application/UseCases/ObtenerTiposPersonalUseCaseTest.php`
- [X] T037 [P] Write integration test for TipoPersonalPostgresSQLRepository in `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoPersonalPostgresSQLRepositoryIntegrationTest.php`
- [X] T038 [P] Write API contract test for GET endpoint in `tests/Feature/Api/Admin/ObtenerTiposPersonalApiTest.php`
- [X] T039 Run PHPStan level 9 analysis: `vendor/bin/phpstan analyse`
- [X] T040 Run Laravel Pint (PSR-12) formatting: `vendor/bin/pint`
- [X] T041 Run PHPUnit test suite: `vendor/bin/phpunit`
- [X] T042 Verify all tests pass with 100% success rate
- [ ] T043 [P] [OPTIONAL] Performance load test: Verify p95 response time <500ms using Apache Bench or similar tool (100 concurrent requests, measure 95th percentile). Run: `ab -n 1000 -c 100 http://localhost/api/v1/admin/tipos-personal`

---

## Phase 5: Documentation & Polish

**Purpose**: OpenAPI specification and final documentation

- [X] T044 Create OpenAPI specification in `specs/001-catalogo-tipos-personal/contracts/api-tipos-personal.yaml`
- [X] T045 [P] Document endpoint in OpenAPI: GET /api/v1/admin/tipos-personal (200, 429, 500 responses)
- [X] T046 [P] Document response schema in OpenAPI: success, message, code, data structure
- [X] T047 Review plan.md and verify all Phase 0 research decisions are documented (or create research.md if needed)
- [X] T048 Review checklist in `specs/001-catalogo-tipos-personal/checklists/requirements.md`
- [X] T049 Update CHANGELOG.md with feature description and endpoint documentation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion (T001-T013) - BLOCKS User Story 1
- **User Story 1 (Phase 3)**: Depends on Foundational phase (T014-T016) completion
- **Quality Assurance (Phase 4)**: Depends on User Story 1 implementation (T017-T034)
- **Documentation (Phase 5)**: Can run in parallel with Phase 4 or after Phase 3

### User Story 1 Internal Dependencies

**Domain Layer** (T017-T018): No internal dependencies, can run in parallel

**Application Layer** (T019-T023):
- DTOs (T020-T021) depend on Domain entity (T017)
- Outbound port (T019) depends on Domain entity (T017)
- Inbound port (T022) depends on DTOs (T020-T021)
- Use case (T023) depends on all ports and DTOs (T019-T022)

**Infrastructure Layer** (T024-T029):
- Eloquent model (T024) independent
- Repository (T025) depends on outbound port (T019) and model (T024)
- OutAdapter (T026) depends on repository (T025)
- InAdapter (T027) depends on inbound port (T022) and use case (T023)
- Routes (T028) depend on InAdapter (T027)
- Service provider (T029) depends on all adapters (T026, T027)

**Cross-cutting** (T030-T034): Depends on complete infrastructure (T024-T029)

### Parallel Opportunities

**Phase 1 (Setup)**: Tasks T002-T013 can run in parallel (independent file reviews)

**Phase 2 (Foundational)**: Tasks T014-T015 can run in parallel (different migration files), T016 must run after both

**Phase 3 (User Story 1)**:
- Domain: T017, T018 can run in parallel
- Application: T019, T020, T021 can run in parallel (after T017)
- Infrastructure: T024 can start immediately in parallel with Application layer tasks
- Cross-cutting: T031 can run independently of other tasks

**Phase 4 (QA)**: Tasks T035-T038 can run in parallel (different test files)

**Phase 5 (Documentation)**: Tasks T044-T045 can run in parallel (different sections of same file)

---

## Parallel Example: User Story 1

```bash
# After Phase 2 completes, these tasks can launch together:

# Domain Layer (parallel)
Task T017: "Create TipoPersonal entity"
Task T018: "Create TipoPersonalNotFoundException"

# Application Layer (parallel after domain)
Task T019: "Define ITipoPersonalOutPort interface"
Task T020: "Create TipoPersonalOutDto"
Task T021: "Create ObtenerTiposPersonalOutDto"
Task T024: "Create TipoPersonalEloquentModel" (infrastructure, but independent)

# Then in sequence:
Task T022: "Define IObtenerTipoPersonalUseCase" (needs T020, T021)
Task T023: "Implement ObtenerTipoPersonalUseCase" (needs T019, T022)
```

---

## Implementation Strategy

### MVP First (Recommended)

1. **Complete Phase 1**: Setup and pattern research (T001-T013) - ~2 hours
2. **Complete Phase 2**: Database foundation (T014-T016) - ~1 hour
3. **Complete Phase 3**: User Story 1 full stack (T017-T034) - ~6 hours
4. **STOP and VALIDATE**: Test endpoint manually with curl
5. **Complete Phase 4**: Quality assurance (T035-T042) - ~4 hours
6. **Complete Phase 5**: Documentation (T043-T048) - ~2 hours

**Total Estimated Time**: ~15 hours for complete MVP with tests and documentation

### Incremental Delivery

1. **Foundation Ready**: After Phase 2 → Database exists, can insert test data manually
2. **MVP Ready**: After Phase 3 → Functional endpoint, deployable for early testing
3. **Production Ready**: After Phase 4 → All tests passing, quality checks passed
4. **Fully Documented**: After Phase 5 → OpenAPI spec complete, ready for frontend integration

### Parallel Team Strategy

With 2 developers:

1. **Both**: Complete Phase 1 together (research and understanding)
2. **Developer A**: Phase 2 (database migrations)
3. Once Phase 2 done:
   - **Developer A**: Domain + Application layers (T017-T023)
   - **Developer B**: Infrastructure layer (T024-T029)
4. **Developer A**: Cross-cutting concerns (T030-T034)
5. **Both**: Phase 4 tests (T035-T043) in parallel (includes optional load test)
6. **Developer B**: Phase 5 documentation (T044-T049)

---

## Task Summary

- **Total Tasks**: 49
- **Phase 1 (Setup)**: 13 tasks
- **Phase 2 (Foundational)**: 3 tasks
- **Phase 3 (User Story 1)**: 18 tasks
  - Domain: 2 tasks
  - Application: 5 tasks
  - Infrastructure: 6 tasks
  - Cross-cutting: 5 tasks
- **Phase 4 (Quality Assurance)**: 8 tasks
- **Phase 5 (Documentation)**: 6 tasks

**User Story Coverage**:
- User Story 1 (P1): 18 implementation tasks + 8 QA tasks = 26 tasks

**Parallel Opportunities**: 22 tasks marked [P] can run in parallel within their phase

**Independent Test Criteria**: User Story 1 can be validated independently by calling `GET /api/v1/admin/tipos-personal` and verifying JSON response with 4 tipos personal

---

## Notes

- All [US1] tasks follow hexagonal architecture patterns from `arquitectura-hexagonal` skill
- **Agent Usage**: Tasks T017-T023 MUST use @Hexagonal Architecture Specialist agent per constitution v1.1.0
- **Naming Convention**: IObtenerTiposPersonalUseCase (PLURAL - matches collection returned), ObtenerTiposPersonalInAdapter (PLURAL - matches use case)
- Spanish naming conventions enforced: ObtenerTiposPersonalInAdapter (NOT Controller)
- Respuesta class MUST be used for responses (NOT response()->json())
- InAdapter constructor uses app()->make() pattern (NOT dependency injection parameters)
- Versioned routes: api/v1/admin prefix in AdminApiRoutes.php (NOT routes/api.php)
- Commit after each logical task group (e.g., after Domain layer, after Application layer)
- Run `vendor/bin/pint` and `vendor/bin/phpstan` frequently during development
- Each checkpoint is a good moment to validate work and adjust if needed
