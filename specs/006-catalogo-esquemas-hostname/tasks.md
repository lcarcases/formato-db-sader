---

description: "Task list for Catálogo de Esquemas por Hostname"
---

# Tasks: Catálogo de Esquemas por Hostname

**Input**: Design documents from `/specs/006-catalogo-esquemas-hostname/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/esquemas-api.md, quickstart.md

**Tests**: Included — `research.md`/`data-model.md` explicitly plan Unit/Integration/Feature tests
at all levels, mirroring the shipped precedent (`specs/005-catalogo-hostnames`,
`specs/004-catalogo-bases-datos`).

**Organization**: This feature has two user stories: **US1** (P1, `GET /hostnames/{idHostname}/esquemas`,
the primary ask) and **US2** (P2, `GET /esquemas`, the consistency-driven flat catalog). Both
depend on the same shared `EsquemaOutPort`/`EsquemaRepository`/`EsquemaOutAdapter` infrastructure
(one interface with two methods, one Eloquent-backed repository), so that shared infra — plus the
two new DB tables and the shared item DTO — lives in **Phase 2: Foundational**, not duplicated per
story.

**⚠️ Architecture note (read before implementing T010/T012)**: Following the corrected,
already-shipped precedent verified directly in
`app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepository.php` and
`app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapter.php`:
- `EsquemaRepository` (T010) MUST return raw `EsquemaModel`/`HostnameEsquemaModel`-shaped Eloquent
  query results — no `EsquemaVO` construction inside it.
- `EsquemaOutAdapter` (T012) MUST do the raw-model → `EsquemaVO` mapping for both methods.
- `EsquemaOutAdapter`'s constructor property MUST be named `$esquemaRepository` (not the generic
  `$repository`), per the same convention as `HostnameOutAdapter`'s `$hostnameRepository`.

**⚠️ Precedent note (read before implementing T022/T029)**: The real, currently-shipped
`ObtenerHostnamesInAdapter.php` (verified on disk) uses
`App\Core\Shared\Infraestructure\Respuesta` (Spanish spelling) with `successResponse()` /
`errorResponse()`, and constructor-based DI via `app()->make(...)` (not constructor-promoted DI).
Both new InAdapters MUST follow this exact real precedent, per the enriched user story's explicit
mandate — NOT the outdated `response()->json()` description in `specs/005-catalogo-hostnames/plan.md`.

**⚠️ 404 handling note (read before implementing T022)**: `Respuesta::errorResponse()` (Spanish
spelling) always returns HTTP 500. Since the nested endpoint must return 404 for an unknown
`idHostname`, `ObtenerEsquemasPorHostnameInAdapter` MUST catch `HostnameNotFoundException`
**separately from and before** the generic `\Exception` catch, and build the 404 JSON body
directly via `response()->json(['success' => false, 'message' => '...', 'data' => []], 404)` —
matching the same envelope shape `Respuesta` produces, without modifying the shared `Respuesta`
class (used by other already-shipped InAdapters).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[US1]**: Belongs to User Story 1 — Obtener Esquemas de un Hostname
- **[US2]**: Belongs to User Story 2 — Obtener Catálogo Completo de Esquemas
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel-as-infrastructure). All application code under `app/Core/Admin/`, tests
mirror that structure under `tests/{Unit,Integration,Feature}/`, per `CLAUDE.md`.

---

## Phase 1: Setup

**Purpose**: No new project-level setup needed — this feature adds files to the existing `Admin`
bounded context (already initialized by prior features). This phase only confirms the environment
is ready.

- [x] T001 Confirm local environment is up (`docker-compose up -d`, PostgreSQL 16 reachable via
  `DB_*_PGSQL` env vars) and `php artisan migrate` runs cleanly on the current branch before
  adding new migrations

**Checkpoint**: No blocking setup work remains; proceed to Foundational phase.

---

## Phase 2: Foundational

**Purpose**: Both new database tables, and the shared `EsquemaOutPort`/`EsquemaRepository`/
`EsquemaOutAdapter`/`EsquemaVO`/`ObtenerEsquemaOutDto` infrastructure that **both** US1 and US2
depend on, must exist and be tested before any story-specific use case/InAdapter work begins.

**⚠️ CRITICAL**: T002–T017 MUST complete before any User Story implementation task.

### Database Schema

- [x] T002 Create schema migration
  `database/migrations/2026_08_30_000001_create_tb_cat_esquema_table.php` defining `tb_cat_esquema`
  (`id_nu_esquema` SERIAL PK, `sn_nombre` VARCHAR(100) NOT NULL UNIQUE, `ind_activo` SMALLINT NOT
  NULL DEFAULT 1 with CHECK (0,1), `created_at`/`updated_at` timestamps, index
  `idx_tb_cat_esquema_activo` on `ind_activo`, table comment) per `data-model.md` § Tabla:
  tb_cat_esquema, mirroring `2026_08_22_000001_create_tb_cat_hostname_table.php` exactly
- [x] T003 Create seed migration
  `database/migrations/2026_08_30_000002_seed_tb_cat_esquema_table.php` inserting, in this exact
  order, all `ind_activo = 1`: `ap_activemq_pd`, `ap_apoyos_pd`, `ap_biometricos_pd`,
  `ap_gestion_doc`, `ap_interfaz`, `ap_inventario_pd`, `ap_movil_pd`, `ap_proagro_pd`,
  `ap_reportes_suri`, `ap_supervision_pd`, `ap_suri_pd`, `ap_svc`, `ap_tramites_pd`,
  `ap_viaticos`, `tr_seguridad_pd`, `tr_suri_pd`, per `data-model.md` § Seed Data, mirroring
  `2026_08_22_000002_seed_tb_cat_hostname_table.php` (depends on T002)
- [x] T004 Create schema migration
  `database/migrations/2026_08_30_000003_create_tb_r_hostname_esquema_table.php` defining
  `tb_r_hostname_esquema` (`id_nu_hostname_esquema` SERIAL PK, `id_nu_hostname` INTEGER NOT NULL
  FK → `tb_cat_hostname.id_nu_hostname`, `id_nu_esquema` INTEGER NOT NULL FK →
  `tb_cat_esquema.id_nu_esquema`, `ind_activo` SMALLINT NOT NULL DEFAULT 1 with CHECK (0,1),
  `created_at`/`updated_at` timestamps, UNIQUE composite index
  `uq_tb_r_hostname_esquema_hostname_esquema` on `(id_nu_hostname, id_nu_esquema)`, index
  `idx_tb_r_hostname_esquema_hostname` on `id_nu_hostname`, table comment) per `data-model.md` §
  Tabla: tb_r_hostname_esquema (depends on T002 for the `tb_cat_esquema` FK target; `tb_cat_hostname`
  already exists from 005)
- [x] T005 Create seed migration
  `database/migrations/2026_08_30_000004_seed_tb_r_hostname_esquema_table.php` inserting 48 rows
  = the cross-product of `id_nu_esquema` 1–16 (in seed order from T003) × `id_nu_hostname` values
  2 (`sridesbds09`), 7 (`sriqabds08`), 4 (`sriprdbdsmz02`), all `ind_activo = 1`, per
  `data-model.md` § Seed Data (depends on T003, T004)
- [x] T006 Run `php artisan migrate` and verify seed data landed correctly via
  `php artisan tinker --execute="echo DB::table('tb_cat_esquema')->count();"` (expect 16) and
  `php artisan tinker --execute="echo DB::table('tb_r_hostname_esquema')->count();"` (expect 48)
  — uses raw `DB::table()` queries (not the Eloquent models, which are only created in T008/T009
  below) so this check can run immediately after the migrations, with no forward dependency
  (depends on T002–T005 only)

### Shared Domain / Application / Infrastructure

- [x] T007 [P] Create Value Object `EsquemaVO` in
  `app/Core/Admin/Domain/ValueObjects/EsquemaVO.php` — pure PHP (no Laravel imports), `readonly`,
  constructor validates `id > 0` and non-empty trimmed `nombre` (throw `InvalidArgumentException`
  otherwise), plus `static fromArray(array): self` and `toArray(): array`, mirroring `HostnameVO`
  exactly (no format regex) per `data-model.md` § Value Object: EsquemaVO
- [x] T008 [P] Create Eloquent model `EsquemaModel` in
  `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/EsquemaModel.php` (table
  `tb_cat_esquema`, PK `id_nu_esquema`, fillable `['sn_nombre', 'ind_activo']`, `ind_activo` cast
  to integer, timestamps enabled) per `data-model.md` § Eloquent Model: EsquemaModel, mirroring
  `HostnameModel`
- [x] T009 [P] Create Eloquent model `HostnameEsquemaModel` (pivot) in
  `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameEsquemaModel.php` (table
  `tb_r_hostname_esquema`, PK `id_nu_hostname_esquema`, fillable
  `['id_nu_hostname', 'id_nu_esquema', 'ind_activo']`, all three cast to integer, timestamps
  enabled) per `data-model.md` § Eloquent Model: HostnameEsquemaModel — internal to
  `EsquemaRepository` only, never exposed via its own Port/OutAdapter
- [x] T010 Create `EsquemaRepository` in
  `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/EsquemaRepository.php` with
  two methods: `obtenerEsquemas(): array` (queries `EsquemaModel::query()->where('ind_activo', 1)
  ->orderBy('id_nu_esquema')->get(['id_nu_esquema', 'sn_nombre'])->values()->all()`, raw Eloquent
  models — do NOT map to `EsquemaVO` here); and `obtenerEsquemasPorHostname(int $idHostname): ?array`
  (first checks `HostnameModel::query()->whereKey($idHostname)->exists()` — returns `null`
  immediately if false; otherwise joins `tb_r_hostname_esquema` filtering
  `id_nu_hostname = $idHostname` and `ind_activo = 1` on both the pivot and `tb_cat_esquema`,
  ordered by `id_nu_esquema` ASC, returning `[]` if no matches) per `data-model.md` § Repository:
  EsquemaRepository (depends on T008, T009; reads existing `HostnameModel` from 005 — do NOT
  modify `HostnameOutPort`/`HostnameOutAdapter`)
- [x] T011 Create Port Out interface `EsquemaOutPort` in
  `app/Core/Admin/Application/Ports/Out/EsquemaOutPort.php` declaring
  `obtenerEsquemas(): array` (returns `list<EsquemaVO>`) and
  `obtenerEsquemasPorHostname(int $idHostname): ?array` (returns `list<EsquemaVO>|null`) per
  `data-model.md` § Port Out: EsquemaOutPort (depends on T007)
- [x] T012 Create `EsquemaOutAdapter` in
  `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/EsquemaOutAdapter.php` implementing
  `EsquemaOutPort`, constructor-injecting `EsquemaRepository $esquemaRepository` (named after the
  class, not `$repository`), mapping the repository's raw `EsquemaModel` rows to `EsquemaVO` in
  both methods, and passing through `null` unchanged from `obtenerEsquemasPorHostname()` when the
  repository signals hostname-not-found (depends on T010, T011)
- [x] T013 Register binding in
  `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php::register()` — add
  `$this->app->bind(EsquemaOutPort::class, EsquemaOutAdapter::class);` alongside the existing
  `HostnameOutPort` binding (depends on T011, T012)
- [x] T014 [P] Create item DTO `ObtenerEsquemaOutDto` in
  `app/Core/Admin/Application/DTOs/Out/ObtenerEsquemaOutDto.php` (`id: int`, `nombre: string`,
  `toArray(): array{id: int, nombre: string}`), shared by both US1's and US2's collection DTOs,
  mirroring `ObtenerHostnameOutDto` exactly per `data-model.md` § DTOs

### Tests for shared infrastructure

- [x] T015 [P] Unit test `EsquemaVOTest` in
  `tests/Unit/Core/Admin/Domain/ValueObjects/EsquemaVOTest.php` — mirror of `AmbienteVOTest.php`:
  valid construction, `id <= 0` throws with message `'El ID debe ser mayor a 0'`, empty/whitespace
  `nombre` throws with message `'El nombre no puede estar vacío'`, `fromArray` (including trim and
  missing-key errors), `toArray`, readonly-property immutability check via Reflection (depends on T007)
- [x] T016 [P] Integration test `EsquemaRepositoryIntegrationTest` in
  `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/EsquemaRepositoryIntegrationTest.php`
  against real PostgreSQL — for `obtenerEsquemas()`: only `ind_activo = 1` rows, ordered by
  `id_nu_esquema` ASC, as raw `EsquemaModel` instances, empty table yields `[]`; for
  `obtenerEsquemasPorHostname()`: hostname with active associations returns only those (ordered),
  hostname with zero associations returns `[]`, nonexistent `idHostname` returns `null`, inactive
  associations/esquemas are excluded (depends on T010)
- [x] T017 [P] Integration test `EsquemaOutAdapterIntegrationTest` in
  `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/EsquemaOutAdapterIntegrationTest.php`
  — constructs the adapter with a real `EsquemaRepository`, asserts `obtenerEsquemas()` returns
  `array<EsquemaVO>` mapped correctly, and `obtenerEsquemasPorHostname()` correctly propagates
  `null` (hostname not found), `[]` (no associations), and a mapped `array<EsquemaVO>` (with
  associations) (depends on T012)

**Checkpoint**: `tb_cat_esquema` and `tb_r_hostname_esquema` exist and are seeded; the shared
`EsquemaOutPort`/`EsquemaRepository`/`EsquemaOutAdapter` stack is implemented, bound, and tested —
both User Story phases can now begin (in parallel or in priority order).

---

## Phase 3: User Story 1 - Obtener Esquemas de un Hostname (Priority: P1) 🎯 MVP

**Goal**: Expose `GET /api/v1/admin/hostnames/{idHostname}/esquemas`, returning the synthetic
"Todos" entry (`{id: 0, nombre: "Todos"}`) always first, followed by the active esquemas really
associated to that hostname; 404 if the hostname doesn't exist.

**Independent Test**: Deploy the endpoint and call it for hostname id 2 (expect "Todos" + 16
esquemas), id 1 (expect only "Todos"), and id 999 (expect 404) — no dependency on US2.

### Tests for User Story 1 ⚠️

> Write these tests FIRST; ensure they FAIL before the corresponding implementation task is done.

- [x] T018 [P] [US1] Unit test `ObtenerEsquemasPorHostnameUseCaseTest` in
  `tests/Unit/Core/Admin/Application/UseCases/ObtenerEsquemasPorHostnameUseCaseTest.php` — mocks
  `EsquemaOutPort`: port returns a non-empty `list<EsquemaVO>` → use case returns it unchanged (no
  "Todos" here — that's the OutDto's job); port returns `[]` → use case returns `[]` (no
  exception); port returns `null` → use case throws `HostnameNotFoundException` carrying the same
  `idHostname`

### Implementation for User Story 1

- [x] T019 [US1] Create domain exception `HostnameNotFoundException` in
  `app/Core/Admin/Domain/Exceptions/HostnameNotFoundException.php` — mirrors
  `TipoPersonalNotFoundException`/`TipoPermisoNotFoundException` exactly: `extends \Exception`,
  constructor `__construct(int $idHostname, int $code = 404, ?\Throwable $previous = null)` with
  message `sprintf('Hostname with ID %d not found. Verify the ID exists in tb_cat_hostname table.', $idHostname)`
- [x] T020 [US1] Create `ObtenerEsquemasPorHostnameUseCase` in
  `app/Core/Admin/Application/UseCases/ObtenerEsquemasPorHostnameUseCase.php` — `final readonly
  class` constructor-injecting `EsquemaOutPort $esquemaOutPort`, `execute(int $idHostname): array`
  calls the port, throws `HostnameNotFoundException($idHostname)` if it returns `null`, otherwise
  returns the list unchanged, per `data-model.md` § ObtenerEsquemasPorHostnameUseCase (depends on
  T011, T019)
- [x] T021 [US1] Create collection DTO `ObtenerEsquemasPorHostnameOutDto` in
  `app/Core/Admin/Application/DTOs/Out/ObtenerEsquemasPorHostnameOutDto.php` — `public array
  $esquemas` of `list<ObtenerEsquemaOutDto>` (only real esquemas, never "Todos"); `toArray()`
  ALWAYS prepends `['id' => 0, 'nombre' => 'Todos']` as the first element, followed by
  `$esquemas` mapped via their own `toArray()`, per `data-model.md` § DTOs (depends on T014)
- [x] T022 [US1] Create `ObtenerEsquemasPorHostnameInAdapter` (invokable) in
  `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerEsquemasPorHostnameInAdapter.php` —
  resolves `ObtenerEsquemasPorHostnameUseCase` via `app()->make(...)` in the constructor (not
  constructor-promoted DI), `__invoke(int $idHostname)` calls
  `execute($idHostname)`, maps the resulting `array<EsquemaVO>` into
  `ObtenerEsquemasPorHostnameOutDto`, and returns
  `(new Respuesta)->successResponse()` with message `'Se obtuvieron los esquemas del hostname
  correctamente.'` on success; catches `HostnameNotFoundException` **first** and returns
  `response()->json(['success' => false, 'message' => 'El hostname solicitado no existe.', 'data' => []], 404)`;
  catches generic `\Exception` last and returns `(new Respuesta)->errorResponse($ex)` with message
  `'Error mientras se intentaba obtener los esquemas del hostname.'` — using
  `App\Core\Shared\Infraestructure\Respuesta` (Spanish spelling), mirroring
  `ObtenerHostnamesInAdapter` exactly for the success/500 paths (see precedent notes above)
  (depends on T020, T021)
- [x] T023 [US1] Register route in `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — add
  `Route::get('/hostnames/{idHostname}/esquemas', ObtenerEsquemasPorHostnameInAdapter::class)
  ->middleware('throttle:60,1')->name('api.admin.hostnames.esquemas.index');` inside the existing
  `api/v1/admin` group, alongside the `hostnames` route (depends on T022)
- [x] T024 [US1] Feature/contract test `ObtenerEsquemasPorHostnameApiTest` in
  `tests/Feature/Core/Admin/Api/ObtenerEsquemasPorHostnameApiTest.php` — covers: 200 with "Todos" +
  16 esquemas (correct order) for `idHostname` 2, 4, and 7 per `contracts/esquemas-api.md`; 200
  with `data: [{"id":0,"nombre":"Todos"}]` only for a seeded hostname with zero associations (e.g.
  id 1); 404 with `success: false` and the documented message for `idHostname` 999; DB unavailable
  → 500 with the generic error envelope (force via a mocked `EsquemaOutPort` binding override); no
  request sends auth headers, confirming FR-011 (depends on T023)
- [x] T025 [US1] Run
  `./vendor/bin/phpunit tests/Unit/Core/Admin/Application/UseCases/ObtenerEsquemasPorHostnameUseCaseTest.php tests/Feature/Core/Admin/Api/ObtenerEsquemasPorHostnameApiTest.php`
  and fix implementation until all pass (depends on T018–T024)

**Checkpoint**: User Story 1 is fully functional and independently testable —
`GET /api/v1/admin/hostnames/{idHostname}/esquemas` works end-to-end for all three documented
cases. This is the feature's MVP.

---

## Phase 4: User Story 2 - Obtener Catálogo Completo de Esquemas (Priority: P2)

**Goal**: Expose `GET /api/v1/admin/esquemas`, returning the full active-esquema catalog (16
seeded values) as `{id, nombre}` pairs, without the "Todos" entry.

**Independent Test**: Deploy the endpoint and call `GET /api/v1/admin/esquemas`; verify a 200
response with the 16 seeded entries (or `data: []` if the catalog is empty) — no dependency on US1.

### Tests for User Story 2 ⚠️

- [x] T026 [P] [US2] Unit test `ObtenerEsquemasUseCaseTest` in
  `tests/Unit/Core/Admin/Application/UseCases/ObtenerEsquemasUseCaseTest.php` — mirror of
  `ObtenerHostnamesUseCaseTest.php`: mocks `EsquemaOutPort`, asserts the use case invokes
  `obtenerEsquemas()` once and returns its result unchanged (including the empty-list case)

### Implementation for User Story 2

- [x] T027 [US2] Create `ObtenerEsquemasUseCase` in
  `app/Core/Admin/Application/UseCases/ObtenerEsquemasUseCase.php` — `final readonly class`
  constructor-injecting `EsquemaOutPort $esquemaOutPort`, `execute(): array` returns
  `$this->esquemaOutPort->obtenerEsquemas()` unchanged, mirroring `ObtenerHostnamesUseCase`
  (depends on T011)
- [x] T028 [US2] Create collection DTO `ObtenerEsquemasOutDto` in
  `app/Core/Admin/Application/DTOs/Out/ObtenerEsquemasOutDto.php` — `public array $esquemas` of
  `list<ObtenerEsquemaOutDto>`, `toArray(): list<array{id:int,nombre:string}>` with **no** "Todos"
  prepend, mirroring `ObtenerHostnamesOutDto` exactly (depends on T014)
- [x] T029 [US2] Create `ObtenerEsquemasInAdapter` (invokable) in
  `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerEsquemasInAdapter.php` — resolves
  `ObtenerEsquemasUseCase` via `app()->make(...)` in the constructor, `__invoke()` maps the use
  case's `array<EsquemaVO>` into `ObtenerEsquemaOutDto` items wrapped in `ObtenerEsquemasOutDto`,
  and returns `(new Respuesta)->successResponse()` with message
  `'Se obtuvieron los esquemas correctamente.'` on success, catching `\Exception` into
  `(new Respuesta)->errorResponse($ex)` with message
  `'Error mientras se intentaba obtener los esquemas.'` — mirroring `ObtenerHostnamesInAdapter`
  exactly (depends on T027, T028)
- [x] T030 [US2] Register route in `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — add
  `Route::get('/esquemas', ObtenerEsquemasInAdapter::class)->middleware('throttle:60,1')
  ->name('api.admin.esquemas.index');` inside the existing `api/v1/admin` group (depends on T029)
- [x] T031 [US2] Feature/contract test `ObtenerEsquemasApiTest` in
  `tests/Feature/Core/Admin/Api/ObtenerEsquemasApiTest.php` — covers: 200 with the 16 seeded
  esquemas in seed order and correct JSON envelope per `contracts/esquemas-api.md`; empty catalog
  → `data: []`, `success: true`; inactive rows excluded; DB unavailable → 500 with the generic
  error envelope; no auth headers sent (FR-011) (depends on T030)
- [x] T032 [US2] Run
  `./vendor/bin/phpunit tests/Unit/Core/Admin/Application/UseCases/ObtenerEsquemasUseCaseTest.php tests/Feature/Core/Admin/Api/ObtenerEsquemasApiTest.php`
  and fix implementation until all pass (depends on T026–T031)

**Checkpoint**: User Story 2 is fully functional and independently testable —
`GET /api/v1/admin/esquemas` returns the full catalog end-to-end.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across the whole feature.

- [x] T033 [P] Run `./vendor/bin/phpstan analyse app/Core/Admin` and fix any new errors introduced
  by this feature's files (pre-existing unrelated errors may remain per `CLAUDE.md`'s noted
  phpstan level)
- [x] T034 [P] Run `./vendor/bin/pint app/Core/Admin tests` to apply project formatting to all new
  files
- [x] T035 Update `.github/copilot-instructions.md` per `plan.md` § Agent Context Update, pointing
  to `specs/006-catalogo-esquemas-hostname/plan.md` (already applied during Phase 1 planning —
  verify it still points here)
- [x] T036 Execute `quickstart.md` § manual verification (`curl` against `/esquemas`,
  `/hostnames/2/esquemas`, `/hostnames/1/esquemas`, `/hostnames/999/esquemas`) and confirm each
  response matches `contracts/esquemas-api.md` exactly
- [x] T037 Run the full suite `./vendor/bin/phpunit` and confirm no regressions beyond any
  pre-existing unrelated failures already present on this branch (e.g. legacy
  `TipoPermisoPostgresSQLRepositoryIntegrationTest`/`TipoPersonalPostgresSQLRepositoryIntegrationTest`,
  documented as pre-existing since `specs/005-catalogo-hostnames/tasks.md` T024)
- [x] T038 [P] Verify both endpoints' response time < 200ms under a load of 50 req/s per SC-001,
  documenting any environment limitation (e.g. `php artisan serve`'s single-threaded dev server)
  the same way `specs/005-catalogo-hostnames/tasks.md` T025 did, rather than skipping the check
  - **Result**: Single-request latency, 5 sequential requests each: `GET /esquemas` 54–96ms;
    `GET /hostnames/2/esquemas` 75–90ms — both well under the 200ms target, consistent with the
    already-accepted precedent measurements for `hostnames` (36–62ms) and `bases-datos` (38–65ms)
    on comparable single-indexed-lookup/small-join query shapes. Neither `ab` nor `artillery` is
    available in this container, so true 50 req/s concurrent-load testing was not performed, for
    the same documented reason as the precedent (the container's dev server serializes concurrent
    requests, making burst-concurrency measurements an artifact of the server, not the endpoint).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS both User Story 1 and User Story 2
- **User Story 1 (Phase 3)**: Depends on Foundational completion
- **User Story 2 (Phase 4)**: Depends on Foundational completion — independent of Phase 3, can run
  in parallel with it
- **Polish (Phase 5)**: Depends on both User Story 1 and User Story 2 completion

### Within Foundational

- T002 (schema `tb_cat_esquema`) before T003 (seed) before T004 (schema `tb_r_hostname_esquema`,
  FK target) before T005 (seed) before T006 (verify)
- T007 (VO), T008 (Model), T009 (pivot Model) → no dependency on each other → parallel
- T010 (Repository) depends on T008 + T009
- T011 (OutPort) depends on T007
- T012 (OutAdapter) depends on T010 + T011
- T013 (binding) depends on T011 + T012
- T014 (item DTO) → independent, parallel with T007–T013
- T015 (VO test) depends on T007; T016 (Repository test) depends on T010; T017 (OutAdapter test)
  depends on T012 — all three parallel with each other

### Within User Story 1

- T018 (test) SHOULD be written first and FAIL before T019–T022 land
- T019 (exception) → independent, can start immediately after Foundational
- T020 (UseCase) depends on T011 (Foundational) + T019
- T021 (OutDto) depends on T014 (Foundational)
- T022 (InAdapter) depends on T020 + T021
- T023 (Route) depends on T022
- T024 (Feature test) depends on T023
- T025 (test run) depends on everything above

### Within User Story 2

- T026 (test) SHOULD be written first and FAIL before T027–T029 land
- T027 (UseCase) depends on T011 (Foundational)
- T028 (OutDto) depends on T014 (Foundational)
- T029 (InAdapter) depends on T027 + T028
- T030 (Route) depends on T029
- T031 (Feature test) depends on T030
- T032 (test run) depends on everything above

### Parallel Opportunities

- T007, T008, T009, T014 (Foundational, different files) can be implemented in parallel
- T015, T016, T017 (Foundational tests) can be written in parallel
- Once Foundational is complete, **all of Phase 3 (US1) and Phase 4 (US2) can proceed in
  parallel** — they touch disjoint files except for the shared `AdminApiRoutes.php` (T023/T030,
  different lines) and `AdminServiceProvider.php` (already finished in Foundational)
- T033, T034, T038 (Polish) can run in parallel

---

## Parallel Example: Foundational

```bash
# Launch the three independent foundational classes together:
Task: "Create EsquemaVO in app/Core/Admin/Domain/ValueObjects/EsquemaVO.php"
Task: "Create EsquemaModel in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/EsquemaModel.php"
Task: "Create HostnameEsquemaModel in app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameEsquemaModel.php"
Task: "Create ObtenerEsquemaOutDto in app/Core/Admin/Application/DTOs/Out/ObtenerEsquemaOutDto.php"

# Launch the three foundational test files together:
Task: "Unit test for EsquemaVO in tests/Unit/Core/Admin/Domain/ValueObjects/EsquemaVOTest.php"
Task: "Integration test for EsquemaRepository in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/EsquemaRepositoryIntegrationTest.php"
Task: "Integration test for EsquemaOutAdapter in tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/EsquemaOutAdapterIntegrationTest.php"
```

## Parallel Example: User Story 1 + User Story 2 together (post-Foundational)

```bash
# US1 vertical slice
Task: "HostnameNotFoundException in app/Core/Admin/Domain/Exceptions/HostnameNotFoundException.php"
Task: "ObtenerEsquemasPorHostnameUseCase in app/Core/Admin/Application/UseCases/ObtenerEsquemasPorHostnameUseCase.php"
Task: "ObtenerEsquemasPorHostnameOutDto in app/Core/Admin/Application/DTOs/Out/ObtenerEsquemasPorHostnameOutDto.php"

# US2 vertical slice (different files, no shared dependency with the above)
Task: "ObtenerEsquemasUseCase in app/Core/Admin/Application/UseCases/ObtenerEsquemasUseCase.php"
Task: "ObtenerEsquemasOutDto in app/Core/Admin/Application/DTOs/Out/ObtenerEsquemasOutDto.php"
```

---

## Implementation Strategy

### MVP First, Then Incremental

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (both tables + shared Port/Repository/OutAdapter/VO/item-DTO,
   fully tested)
3. Complete Phase 3: User Story 1 (full vertical slice) — this alone is the feature's MVP, since it
   is the story the enriched user story is actually about
4. **STOP and VALIDATE**: run T025, confirm both US1 test files pass
5. Complete Phase 4: User Story 2 (full vertical slice) — can be done in parallel with Phase 3 by a
   second implementer once Foundational is done, or sequentially afterward
6. **STOP and VALIDATE**: run T032, confirm both US2 test files pass
7. Complete Phase 5: Polish, then deploy/demo

### Constitutional Requirement

Per `.specify/memory/constitution.md` (v1.1.0) and this feature's `plan.md` Constitution Check,
T007–T022 and T027–T029 (the actual hexagonal implementation) MUST be carried out via the
`hexagonal-architecture-specialist` agent / `arquitectura-hexagonal` skill, not implemented ad hoc.

---

## Notes

- [P] tasks = different files, no dependencies
- [US1]/[US2] labels map every Phase 3/4 task to its user story for traceability
- Verify tests fail before implementing (T018 before T019–T022; T026 before T027–T029)
- Commit after each task or logical group
- Stop at each story's checkpoint to validate it independently before moving to Polish
