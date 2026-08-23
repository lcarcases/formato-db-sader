---

description: "Task list for Catálogo de Hostnames"
---

# Tasks: Catálogo de Hostnames

**Input**: Design documents from `/specs/005-catalogo-hostnames/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/hostnames-api.md, quickstart.md

**Tests**: Included — `research.md` and `data-model.md` explicitly plan Unit/Integration/Feature tests at all 3 levels, mirroring the already-shipped precedent (`specs/004-catalogo-bases-datos`).

**Organization**: This feature has a single user story (P1), so all implementation tasks live in one phase. Tasks are still labeled `[US1]` for traceability per the project's task-format convention.

**⚠️ Architecture note (read before implementing T009/T012)**: Following the corrected, already-shipped precedent in `BaseDatosRepository`/`BaseDatosOutAdapter` (verified directly in
`app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepository.php` and
`app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapter.php`):
- `HostnameRepository` (T009) MUST return raw `HostnameModel` Eloquent instances — no `HostnameVO` construction inside it.
- `HostnameOutAdapter` (T012) MUST do the raw-model → `HostnameVO` mapping.
- `HostnameOutAdapter`'s constructor property MUST be named `$hostnameRepository` (not the generic `$repository`), per the same convention.

**⚠️ Precedent note (read before implementing T016)**: The real, currently-shipped `ObtenerBasesDatosInAdapter.php` (verified on disk) uses **inline `response()->json([...])`** (NOT either shared `Respuesta` helper class) and **constructor-promoted dependency injection** (`public function __construct(private ObtenerBasesDatosUseCase $useCase) {}`), not the `app()->make(...)` pattern described as canonical elsewhere in `CLAUDE.md`. This feature's enriched user story explicitly mandates mirroring `ObtenerBasesDatosInAdapter` exactly, so `ObtenerHostnamesInAdapter` (T016) MUST follow the real precedent (inline JSON response + constructor-promoted DI), not the `CLAUDE.md` canonical description.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[US1]**: Belongs to User Story 1 — Obtener Catálogo de Hostnames
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel-as-infrastructure). All application code under `app/Core/Admin/`, tests mirror that structure under `tests/{Unit,Integration,Feature}/`, per `CLAUDE.md`.

---

## Phase 1: Setup

**Purpose**: No new project-level setup needed — this feature adds files to the existing `Admin` bounded context (already initialized by prior features). This phase only confirms the environment is ready.

- [X] T001 Confirm local environment is up (`docker-compose up -d`, PostgreSQL 16 reachable via `DB_*_PGSQL` env vars) and `php artisan migrate` runs cleanly on the current branch before adding new migrations

**Checkpoint**: No blocking setup work remains; proceed to Foundational phase.

---

## Phase 2: Foundational

**Purpose**: Database schema must exist before any repository/adapter/use-case code can be tested end-to-end.

**⚠️ CRITICAL**: T002 and T003 MUST complete (in order) before any User Story 1 implementation task that touches the database.

- [X] T002 Create schema migration `database/migrations/2026_08_22_000001_create_tb_cat_hostname_table.php` defining `tb_cat_hostname` (`id_nu_hostname` SERIAL PK, `sn_nombre` VARCHAR(100) NOT NULL UNIQUE, `ind_activo` SMALLINT NOT NULL DEFAULT 1 with CHECK (0,1), `created_at`/`updated_at` timestamps, index on `ind_activo`, table comment) per `data-model.md` § Database Schema, mirroring `2026_08_07_000001_create_tb_cat_base_datos_table.php` exactly
- [X] T003 Create seed migration `database/migrations/2026_08_22_000002_seed_tb_cat_hostname_table.php` inserting, in this exact order, all `ind_activo = 1`, stored exactly as provided (no case normalization): `pgrdesbds09`, `sridesbds09`, `pgrprdbdsmz02`, `sriprdbdsmz02`, `divprdbds01`, `pgrqabds08`, `sriqabds08`, `10.1.35.50`, `10.1.21.95`, `10.1.20.25`, `10.54.49.100`, per `data-model.md` § Seed Data, mirroring `2026_08_07_000002_seed_tb_cat_base_datos_table.php`
- [X] T004 Run `php artisan migrate` and verify via `php artisan db:table tb_cat_hostname` that the 11 seed rows exist in the exact order and content specified

**Checkpoint**: `tb_cat_hostname` exists and is seeded — User Story 1 implementation can now begin.

---

## Phase 3: User Story 1 - Obtener Catálogo de Hostnames (Priority: P1) 🎯 MVP

**Goal**: Expose `GET /api/v1/admin/hostnames`, returning the active hostname/IP catalog (11 seeded values) as `{id, nombre}` pairs in the project's standard JSON envelope, without requiring authentication.

**Independent Test**: Deploy the endpoint and call `GET /api/v1/admin/hostnames`; verify a 200 response with the 11 seeded entries (or `data: []` if the catalog is empty), with no dependency on any other feature.

### Tests for User Story 1 ⚠️

> Write these tests FIRST; ensure they FAIL before the corresponding implementation task is done.

- [X] T005 [P] [US1] Unit test `ObtenerHostnamesUseCaseTest` in `tests/Unit/Core/Admin/Application/UseCases/ObtenerHostnamesUseCaseTest.php` — mocks `HostnameOutPort`, asserts the use case invokes `obtenerHostnames()` once and returns its result unchanged (including the empty-list case), per `data-model.md` § Unit Tests
- [X] T006 [P] [US1] Integration test `HostnameRepositoryIntegrationTest` in `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepositoryIntegrationTest.php` against real PostgreSQL — asserts only `ind_activo = 1` rows are returned, ordered by `id_nu_hostname` ASC, as raw `HostnameModel` instances (NOT `HostnameVO` — see architecture note above), and that an empty table yields `[]`
- [X] T007 [P] [US1] Integration test `HostnameOutAdapterIntegrationTest` in `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapterIntegrationTest.php` — constructs the adapter with a real (or mocked) `HostnameRepository`, asserts `obtenerHostnames()` returns `array<HostnameVO>` mapped correctly from the repository's raw rows
- [X] T008 [P] [US1] Feature/contract test `ObtenerHostnamesApiTest` in `tests/Feature/Core/Admin/Api/ObtenerHostnamesApiTest.php` — covers: 200 with the 11 seeded entries in seed order and correct JSON envelope (`data`, `message`, `code`, `success`) per `contracts/hostnames-api.md`; empty catalog → `data: []`; inactive rows excluded; non-GET methods (e.g. POST) → 405 per `contracts/hostnames-api.md` § 405 example; DB unavailable → 500 with the generic error envelope (force the failure via a mocked `HostnameOutPort`/repository binding override, per `data-model.md` § Feature/Contract Tests scenario and the Edge Case in `spec.md`); no request in this test suite ever sends auth headers, confirming FR-003 (no authentication required)

### Implementation for User Story 1

- [X] T009 [P] [US1] Create Eloquent model `HostnameModel` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameModel.php` (table `tb_cat_hostname`, PK `id_nu_hostname`, fillable `['sn_nombre', 'ind_activo']`, `ind_activo` cast to integer, timestamps enabled) per `data-model.md` § Eloquent Model, mirroring `BaseDatosModel`
- [X] T010 [P] [US1] Create Value Object `HostnameVO` in `app/Core/Admin/Domain/ValueObjects/HostnameVO.php` — pure PHP (no Laravel imports), `readonly`, constructor validates `id > 0` and non-empty trimmed `nombre` (throw `\InvalidArgumentException` otherwise), plus `static fromArray(array): self` and `toArray(): array`, mirroring `BaseDatosVO` exactly (no hostname/IP format regex)
- [X] T011 [US1] Create `HostnameRepository` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepository.php` with `obtenerHostnames(): array` querying `HostnameModel::query()->where('ind_activo', 1)->orderBy('id_nu_hostname')->get(['id_nu_hostname', 'sn_nombre'])` and returning the **raw Eloquent models as a re-indexed list** (`->values()->all()`) — **do NOT map to `HostnameVO` here** (depends on T009)
- [X] T012 [US1] Create Port Out interface `HostnameOutPort` in `app/Core/Admin/Application/Ports/Out/HostnameOutPort.php` declaring `obtenerHostnames(): array` (returns `list<HostnameVO>`) per `data-model.md` § Port Out (depends on T010)
- [X] T013 [US1] Create `HostnameOutAdapter` in `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapter.php` implementing `HostnameOutPort`, constructor-injecting `HostnameRepository $hostnameRepository` (named after the class, not `$repository`), and mapping the repository's raw `HostnameModel` rows to `HostnameVO` inside `obtenerHostnames()` (depends on T011, T012)
- [X] T014 [US1] Create `ObtenerHostnamesUseCase` in `app/Core/Admin/Application/UseCases/ObtenerHostnamesUseCase.php` — `final readonly class` constructor-injecting `HostnameOutPort $outPort` (or `$hostnameOutPort`, matching `ObtenerBasesDatosUseCase`'s convention), `execute(): array` returns `$this->outPort->obtenerHostnames()` unchanged, per `data-model.md` § ObtenerHostnamesUseCase (depends on T012)
- [X] T015 [US1] Create `ObtenerHostnameOutDto` (single item) in `app/Core/Admin/Application/DTOs/Out/ObtenerHostnameOutDto.php` (`id: int`, `nombre: string`, `toArray(): array{id: int, nombre: string}`) and `ObtenerHostnamesOutDto` (collection) in `app/Core/Admin/Application/DTOs/Out/ObtenerHostnamesOutDto.php` (`public array $hostnames` of `list<ObtenerHostnameOutDto>`, `toArray(): list<array{id:int,nombre:string}>`), both to be built by the InAdapter from the use case result — never inside the UseCase/Domain/Infrastructure — mirroring `ObtenerBaseDatosOutDto`/`ObtenerBasesDatosOutDto` (depends on T010)
- [X] T016 [US1] Create `ObtenerHostnamesInAdapter` (invokable) in `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerHostnamesInAdapter.php` — `final readonly class` with constructor-promoted `private ObtenerHostnamesUseCase $useCase`, `__invoke(): JsonResponse` maps the use case's `array<HostnameVO>` to `ObtenerHostnameOutDto` items wrapped in `ObtenerHostnamesOutDto`, and returns the standard JSON envelope built **inline** via `response()->json(['data' => ..., 'message' => 'Hostnames obtenidos exitosamente', 'code' => '200', 'success' => true], 200)`, catching `\Throwable` into a generic `500` response (`success: false`, `data: null`) and logging the exception via `logger()->error(...)` — mirroring `ObtenerBasesDatosInAdapter` exactly (see precedent note above; do NOT use either shared `Respuesta` class, do NOT use `app()->make(...)`) (depends on T014, T015)
- [X] T017 [US1] Register binding in `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php::register()` — add `$this->app->bind(HostnameOutPort::class, HostnameOutAdapter::class);` alongside the existing `BaseDatosOutPort` binding (depends on T012, T013)
- [X] T018 [US1] Register route in `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — add `Route::get('/hostnames', ObtenerHostnamesInAdapter::class)->name('api.admin.hostnames.index');` inside the existing `api/v1/admin` group, alongside the `bases-datos` route (depends on T016)
- [X] T019 [US1] Run `./vendor/bin/phpunit tests/Unit/Core/Admin/Application/UseCases/ObtenerHostnamesUseCaseTest.php tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepositoryIntegrationTest.php tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapterIntegrationTest.php tests/Feature/Core/Admin/Api/ObtenerHostnamesApiTest.php` and fix implementation until all pass (depends on T005-T018)

**Checkpoint**: User Story 1 is fully functional and independently testable — `GET /api/v1/admin/hostnames` returns the seeded catalog end-to-end.

---

## Phase 4: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across the whole feature.

- [X] T020 [P] Run `./vendor/bin/phpstan analyse app/Core/Admin` and fix any new errors introduced by this feature's files (pre-existing unrelated errors may remain per `CLAUDE.md`'s noted phpstan level)
  - **Result**: 4 pre-existing errors remain, all in `IGetTipoRequerimientoUseCase.php`, `ObtenerTiposRequerimientosInAdapter.php`, and `TipoPersonalEloquentModel.php` — none in any Hostname file. No new errors introduced by this feature.
- [X] T021 [P] Run `./vendor/bin/pint app/Core/Admin tests` to apply project formatting to all new files
  - **Result**: 12 style issues fixed across 83 files scanned; the only Admin-context production file touched was `AdminServiceProvider.php` (import ordering). All new Hostname files were already Pint-clean. A handful of pre-existing, unrelated `TipoPermiso`/`TipoPersonal` test files also received pure whitespace/formatting fixes (verified via `git diff` — no logic changes).
- [X] T022 Update `.github/copilot-instructions.md` per plan.md § Agent Context Update, pointing to `specs/005-catalogo-hostnames/plan.md`
- [X] T023 Execute `quickstart.md` § manual verification (`curl -X GET http://localhost:8000/api/v1/admin/hostnames`) and confirm the response matches the documented example (11 entries, `pgrdesbds09` first, `10.54.49.100` last)
  - **Result**: Verified via `docker exec formato_db_sader curl ... /api/v1/admin/hostnames` → HTTP 200, response body byte-for-byte matches the enriched user story's "Expected output" example (11 entries in seed order, envelope `{data, message, code, success}`).
- [X] T024 Run the full suite `./vendor/bin/phpunit` and confirm no regressions beyond any pre-existing unrelated integration test failures already present on this branch
  - **Result**: Default suite (99 tests, `Unit`+`Feature`): 99/99 pass (9 notices, 0 failures). Explicit `tests/Integration` run (51 tests): 2 pre-existing failures in `TipoPermisoPostgresSQLRepositoryIntegrationTest`/`TipoPersonalPostgresSQLRepositoryIntegrationTest` (both legacy repositories, unrelated to Admin's hexagonal `tb_cat_*` catalogs) — confirmed pre-existing via `git diff` (only pint whitespace changes touched those files, no logic changes) and already documented as pre-existing in `specs/004-catalogo-bases-datos/tasks.md` T024. All 4 new Hostname test files (22 tests, 107 assertions) pass with 0 failures.
- [X] T025 [P] Verify `GET /api/v1/admin/hostnames` response time < 200ms under a load of 50 req/s (e.g. `ab -n 1000 -c 50 http://localhost:8000/api/v1/admin/hostnames` or `artillery`) per SC-001, mirroring the equivalent load-test task already done for the precedent feature (`specs/004-catalogo-bases-datos/tasks.md` T025); document any environment limitation preventing true concurrent-load measurement (e.g. `php artisan serve`'s single-threaded dev server) the same way the precedent task did, rather than skipping the check
  - **Result**: Single-request latency 36–62ms across 5 sequential requests, well under the 200ms target — consistent with the already-accepted precedent measurement for `bases-datos` (38–65ms) on the same 4→11 row, single indexed-lookup query shape. True 50 req/s concurrent-load testing was not performed in this environment for the same documented reason as the precedent (`php artisan serve`'s single-threaded dev server serializes concurrent requests, making burst-concurrency measurements an artifact of the server, not the endpoint).

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
- T015 (OutDtos) depends on T010
- T016 (InAdapter) depends on T014 + T015
- T017 (ServiceProvider binding) depends on T012 + T013
- T018 (Route) depends on T016
- T019 (test run) depends on everything above

### Parallel Opportunities

- T005, T006, T007, T008 (all four test files) can be written in parallel — different files
- T009 and T010 can be implemented in parallel — different files, no shared dependency
- T020, T021, T025 (Polish) can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Unit test for ObtenerHostnamesUseCase in tests/Unit/Core/Admin/Application/UseCases/ObtenerHostnamesUseCaseTest.php"
Task: "Integration test for HostnameRepository in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepositoryIntegrationTest.php"
Task: "Integration test for HostnameOutAdapter in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapterIntegrationTest.php"
Task: "Feature/contract test for the endpoint in tests/Feature/Core/Admin/Api/ObtenerHostnamesApiTest.php"

# Launch the two independent foundational classes together:
Task: "Create HostnameModel in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameModel.php"
Task: "Create HostnameVO in app/Core/Admin/Domain/ValueObjects/HostnameVO.php"
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
- Commit after each task or logical group
- Stop at the Phase 3 checkpoint to validate the story independently before Polish
