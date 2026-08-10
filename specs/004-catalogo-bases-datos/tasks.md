---

description: "Task list for Catálogo de Bases de Datos"
---

# Tasks: Catálogo de Bases de Datos

**Input**: Design documents from `/specs/004-catalogo-bases-datos/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/bases-datos-api.md, quickstart.md

**Tests**: Included — `research.md` and `data-model.md` explicitly plan Unit/Integration/Feature tests at all 3 levels, mirroring the already-shipped precedent (`specs/003-catalogo-ambientes-desarrollo`).

**Organization**: This feature has a single user story (P1), so all implementation tasks live in one phase. Tasks are still labeled `[US1]` for traceability per the project's task-format convention.

**⚠️ Architecture note (read before implementing T009/T012)**: `data-model.md`, `quickstart.md`'s diagram, and the `BaseDatosRepository` code sample in `data-model.md` describe the Repository mapping Eloquent models directly to `BaseDatosVO`. This has since been identified as a hexagonal-architecture violation and corrected project-wide (see the now-updated `ai/skills/arquitectura-hexagonal/references/INFRASTRUCTURE_REPOSITORY_EXAMPLES.md` / `INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md`, and the fixed precedent in `AmbienteDesarrolloRepository` / `AmbienteDesarrolloOutAdapter`). **The tasks below follow the corrected rule, not the stale wording in data-model.md:**
- `BaseDatosRepository` (T009) MUST return raw `BaseDatosModel` Eloquent instances — no `BaseDatosVO` construction inside it.
- `BaseDatosOutAdapter` (T012) MUST do the raw-model → `BaseDatosVO` mapping.
- `BaseDatosOutAdapter`'s constructor property MUST be named `$baseDatosRepository` (not the generic `$repository`), per the same corrected convention.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[US1]**: Belongs to User Story 1 — Obtener Catálogo de Bases de Datos
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel-as-infrastructure). All application code under `app/Core/Admin/`, tests mirror that structure under `tests/{Unit,Integration,Feature}/`, per `CLAUDE.md`.

---

## Phase 1: Setup

**Purpose**: No new project-level setup needed — this feature adds files to the existing `Admin` bounded context (already initialized by feature 003). This phase only confirms the environment is ready.

- [X] T001 Confirm local environment is up (`docker-compose up -d`, PostgreSQL 16 reachable via `DB_*_PGSQL` env vars) and `php artisan migrate` runs cleanly on the current branch before adding new migrations

**Checkpoint**: No blocking setup work remains; proceed to Foundational phase.

---

## Phase 2: Foundational

**Purpose**: Database schema must exist before any repository/adapter/use-case code can be tested end-to-end.

**⚠️ CRITICAL**: T002 and T003 MUST complete (in order) before any User Story 1 implementation task that touches the database.

- [X] T002 Create schema migration `database/migrations/2026_08_07_000001_create_tb_cat_base_datos_table.php` defining `tb_cat_base_datos` (`id_nu_base_datos` SERIAL PK, `sn_nombre` VARCHAR(100) NOT NULL UNIQUE, `ind_activo` SMALLINT NOT NULL DEFAULT 1 with CHECK (0,1), `created_at`/`updated_at` timestamps, index on `ind_activo`) per `data-model.md` § Database Schema
- [X] T003 Create seed migration `database/migrations/2026_08_07_000002_seed_tb_cat_base_datos_table.php` inserting `PPB`, `SURI`, `XAMAN`, `OTROS` (all `ind_activo = 1`) per `data-model.md` § Seed Data
- [X] T004 Run `php artisan migrate` and verify via `php artisan db:table tb_cat_base_datos` that the 4 seed rows exist

**Checkpoint**: `tb_cat_base_datos` exists and is seeded — User Story 1 implementation can now begin.

---

## Phase 3: User Story 1 - Obtener Catálogo de Bases de Datos (Priority: P1) 🎯 MVP

**Goal**: Expose `GET /api/v1/admin/bases-datos`, returning the active database catalog (`PPB`, `SURI`, `XAMAN`, `OTROS`) as `{id, nombre}` pairs in the project's standard JSON envelope, without requiring authentication.

**Independent Test**: Deploy the endpoint and call `GET /api/v1/admin/bases-datos`; verify a 200 response with the 4 seeded entries (or `data: []` if the catalog is empty), with no dependency on any other feature.

### Tests for User Story 1 ⚠️

> Write these tests FIRST; ensure they FAIL before the corresponding implementation task is done.

- [X] T005 [P] [US1] Unit test `ObtenerBasesDatosUseCaseTest` in `tests/Unit/Core/Admin/Application/UseCases/ObtenerBasesDatosUseCaseTest.php` — mocks `BaseDatosOutPort`, asserts the use case invokes `obtenerBasesDatos()` once and returns its result unchanged (including the empty-list case), per `data-model.md` § Unit Tests
- [X] T006 [P] [US1] Integration test `BaseDatosRepositoryIntegrationTest` in `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepositoryIntegrationTest.php` against real PostgreSQL — asserts only `ind_activo = 1` rows are returned, ordered by `id_nu_base_datos` ASC, as raw `BaseDatosModel` instances (NOT `BaseDatosVO` — see architecture note above), and that an empty table yields `[]`
- [X] T007 [P] [US1] Integration test `BaseDatosOutAdapterIntegrationTest` in `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapterIntegrationTest.php` — constructs the adapter with a real (or mocked) `BaseDatosRepository`, asserts `obtenerBasesDatos()` returns `array<BaseDatosVO>` mapped correctly from the repository's raw rows
- [X] T008 [P] [US1] Feature/contract test `ObtenerBasesDatosApiTest` in `tests/Feature/Core/Admin/Api/ObtenerBasesDatosApiTest.php` — covers: 200 with the 4 seeded entries and correct JSON envelope (`data`, `message`, `code`, `success`) per `contracts/bases-datos-api.md`; `data` ordered by id; empty catalog → `data: []`; non-GET methods → 405; inactive rows excluded; DB unavailable → 500 with the generic error envelope (force the failure via a mocked `BaseDatosOutPort`/repository binding override, per `data-model.md` § Feature/Contract Tests scenario 6 and the Edge Case in `spec.md`); no request in this test suite ever sends auth headers, confirming FR-003 (no authentication required)

### Implementation for User Story 1

- [X] T009 [P] [US1] Create Eloquent model `BaseDatosModel` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/BaseDatosModel.php` (table `tb_cat_base_datos`, PK `id_nu_base_datos`, fillable `['sn_nombre', 'ind_activo']`, `ind_activo` cast to integer, timestamps enabled) per `data-model.md` § Eloquent Model
- [X] T010 [P] [US1] Create Value Object `BaseDatosVO` in `app/Core/Admin/Domain/ValueObjects/BaseDatosVO.php` — pure PHP (no Laravel imports), `readonly`, constructor validates `id > 0` and non-empty trimmed `nombre` (throw `\InvalidArgumentException` otherwise), plus `static fromArray(array): self` and `toArray(): array`, mirroring `AmbienteVO`
- [X] T011 [US1] Create `BaseDatosRepository` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepository.php` with `obtenerBasesDatos(): array` querying `BaseDatosModel::query()->where('ind_activo', 1)->orderBy('id_nu_base_datos')->get(['id_nu_base_datos', 'sn_nombre'])` and returning the **raw Eloquent models as a re-indexed list** (`->values()->all()`) — **do NOT map to `BaseDatosVO` here** (depends on T009)
- [X] T012 [US1] Create Port Out interface `BaseDatosOutPort` in `app/Core/Admin/Application/Ports/Out/BaseDatosOutPort.php` declaring `obtenerBasesDatos(): array` (returns `list<BaseDatosVO>`) per `data-model.md` § Port Out (depends on T010)
- [X] T013 [US1] Create `BaseDatosOutAdapter` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapter.php` implementing `BaseDatosOutPort`, constructor-injecting `BaseDatosRepository $baseDatosRepository` (named after the class, not `$repository`), and mapping the repository's raw `BaseDatosModel` rows to `BaseDatosVO` inside `obtenerBasesDatos()` (depends on T011, T012)
- [X] T014 [US1] Create `ObtenerBasesDatosUseCase` in `app/Core/Admin/Application/UseCases/ObtenerBasesDatosUseCase.php` — `final readonly class` constructor-injecting `BaseDatosOutPort $outPort`, `execute(): array` returns `$this->outPort->obtenerBasesDatos()` unchanged, per `data-model.md` § ObtenerBasesDatosUseCase (depends on T012)
- [X] T015 [US1] Create `ObtenerBasesDatosOutDto` in `app/Core/Admin/Application/DTOs/Out/ObtenerBasesDatosOutDto.php` carrying `basesDatos: array<BaseDatosVO>`, built by the InAdapter from the use case result (depends on T010)
- [X] T016 [US1] Create `ObtenerBasesDatosInAdapter` (invokable) in `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerBasesDatosInAdapter.php` — constructed via `app()->make(ObtenerBasesDatosUseCase::class)`, `__invoke()` builds `ObtenerBasesDatosOutDto` from the use case result and returns the standard JSON envelope (`data`/`message`/`code`/`success`) via the project's `Respuesta` helper already imported by `ObtenerAmbientesInAdapter` (check which of the two `Respuesta` classes that file uses and match it), catching exceptions into a generic 500 message without leaking internals (depends on T014, T015)
- [X] T017 [US1] Register binding in `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php::register()` — add `$this->app->bind(BaseDatosOutPort::class, BaseDatosOutAdapter::class);` alongside the existing `AmbienteDesarrolloOutPort` binding (depends on T012, T013)
- [X] T018 [US1] Register route in `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — add `Route::get('/bases-datos', ObtenerBasesDatosInAdapter::class)->name('api.admin.bases-datos.index');` inside the existing `api/v1/admin` group (depends on T016)
- [X] T019 [US1] Run `./vendor/bin/phpunit tests/Unit/Core/Admin/Application/UseCases/ObtenerBasesDatosUseCaseTest.php tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepositoryIntegrationTest.php tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapterIntegrationTest.php tests/Feature/Core/Admin/Api/ObtenerBasesDatosApiTest.php` and fix implementation until all pass (depends on T005-T018)

**Checkpoint**: User Story 1 is fully functional and independently testable — `GET /api/v1/admin/bases-datos` returns the seeded catalog end-to-end.

---

## Phase 4: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across the whole feature.

- [X] T020 [P] Run `./vendor/bin/phpstan analyse app/Core/Admin` and fix any new errors introduced by this feature's files (pre-existing unrelated errors may remain per `CLAUDE.md`'s noted phpstan level)
- [X] T021 [P] Run `./vendor/bin/pint app/Core/Admin tests` to apply project formatting to all new files
- [X] T022 Update `.github/copilot-instructions.md` per plan.md § Agent Context Update, pointing to `specs/004-catalogo-bases-datos/plan.md`
- [X] T023 Execute `quickstart.md` § Paso 6 manual verification (`curl -X GET http://localhost:8000/api/v1/admin/bases-datos`) and confirm the response matches the documented example
- [X] T024 Run the full suite `./vendor/bin/phpunit` and confirm no regressions beyond the pre-existing unrelated `TipoPermiso`/`TipoPersonal` integration test failures already present on this branch
- [X] T025 [P] Verify `GET /api/v1/admin/bases-datos` response time < 200ms under a load of 50 req/s (e.g. `ab -n 1000 -c 50 http://localhost:8000/api/v1/admin/bases-datos` or `artillery`) per SC-001, mirroring the equivalent load-test task already done for the precedent feature (`specs/003-catalogo-ambientes-desarrollo/tasks.md` T028)
  - **Result**: Single-request latency 38–65ms, well under the 200ms target. True 50 req/s concurrent-load testing was NOT possible in this environment — the container serves via `php artisan serve` (PHP's single-threaded built-in dev server, confirmed via `ps aux`), which queues concurrent requests serially rather than serving them in parallel; a 50-concurrency `curl` burst measured p95 ≈2.2s purely as an artifact of that serialization, not endpoint slowness. Validating SC-001's concurrency claim requires a production-like php-fpm/nginx or Octane setup, which this local Docker environment does not provide. Query itself is a single indexed lookup on a 4-row table, so no architectural reason to expect it to miss the target under real concurrency.
- [X] T026 [P] Create OpenAPI 3.x fragment documenting `GET /api/v1/admin/bases-datos` in `docs/openapi/admin-bases-datos.yaml` (request/response schema per `contracts/bases-datos-api.md`, including the 200/405/500 responses), per Constitution § Documentation Requirements ("MUST maintain an OpenAPI (Swagger) specification"). This establishes the project's first OpenAPI artifact — no prior feature (001-003) has one; treat this as the first concrete step of a separate, cross-cutting effort rather than a full retrofit of existing endpoints

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all of User Story 1
- **User Story 1 (Phase 3)**: Depends on Foundational completion
- **Polish (Phase 4)**: Depends on User Story 1 completion

### Within User Story 1

- Tests (T005-T008) SHOULD be written first and FAIL before their corresponding implementation lands (T009-T018)
- T009 (Model) and T010 (VO) have no dependency on each other → parallel
- T011 (Repository) depends on T009 (Model)
- T012 (OutPort) depends on T010 (VO, for its return-type contract)
- T013 (OutAdapter) depends on T011 + T012
- T014 (UseCase) depends on T012
- T015 (OutDto) depends on T010
- T016 (InAdapter) depends on T014 + T015
- T017 (ServiceProvider binding) depends on T012 + T013
- T018 (Route) depends on T016
- T019 (test run) depends on everything above

### Parallel Opportunities

- T005, T006, T007, T008 (all four test files) can be written in parallel — different files
- T009 and T010 can be implemented in parallel — different files, no shared dependency
- T020, T021, T025, T026 (Polish) can all run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Unit test for ObtenerBasesDatosUseCase in tests/Unit/Core/Admin/Application/UseCases/ObtenerBasesDatosUseCaseTest.php"
Task: "Integration test for BaseDatosRepository in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepositoryIntegrationTest.php"
Task: "Integration test for BaseDatosOutAdapter in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapterIntegrationTest.php"
Task: "Feature/contract test for the endpoint in tests/Feature/Core/Admin/Api/ObtenerBasesDatosApiTest.php"

# Launch the two independent foundational classes together:
Task: "Create BaseDatosModel in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/BaseDatosModel.php"
Task: "Create BaseDatosVO in app/Core/Admin/Domain/ValueObjects/BaseDatosVO.php"
```

---

## Implementation Strategy

### MVP First (and Only) Scope

This feature has a single P1 user story, so the MVP **is** the full feature:

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (migrations)
3. Complete Phase 3: User Story 1 (full vertical slice: Domain → Application → Infrastructure → tests)
4. **STOP and VALIDATE**: run T019, confirm all 4 test files pass
5. Complete Phase 4: Polish, then deploy/demo

### Constitutional Requirement

Per `.specify/memory/constitution.md` (v1.1.0) and this feature's `plan.md` Constitution Check, T009-T018 (the actual hexagonal implementation) MUST be carried out via the `hexagonal-architecture-specialist` agent (`@hexagonal-architecture-specialist`), not implemented ad hoc.

---

## Notes

- [P] tasks = different files, no dependencies
- [US1] label maps every Phase 3 task to the feature's single user story for traceability
- Verify tests fail before implementing (T005-T008 before T009-T018)
- This feature intentionally deviates from `data-model.md`'s Repository code sample regarding where the Eloquent → `BaseDatosVO` mapping happens — see the architecture note at the top of this file
- Commit after each task or logical group
- Stop at the Phase 3 checkpoint to validate the story independently before Polish
