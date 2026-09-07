# Open Questions & Responses — Selección de Esquemas por Hostname (006-catalogo-esquemas-hostname)

This log records every question/answer pair encountered while enriching
`userStory/general/seleccionar-esquemas-hostname.md` into
`userStory/enriched/2026-08-30-seleccionar-esquemas-por-hostname-user-story.md`. The enrichment was
run fully autonomously (no human available for clarification); every open question below was
resolved by picking the most reasonable answer grounded in the existing codebase — chiefly the
`specs/005-catalogo-hostnames` precedent (the most recently merged catalog, and the one this story
directly extends) and `specs/004-catalogo-bases-datos`.

Only the enrichment stage was run (no `/speckit.*` commands were executed in this pass), so this
log has a single stage.

## Stage 0 — Enrichment

| # | Question | Chosen answer | Rationale |
|---|----------|----------------|-----------|
| 1 | Is "Esquema" a new standalone catalog, or is it implicitly tied to a single hostname (e.g. a column on `tb_cat_hostname`)? | New standalone catalog table `tb_cat_esquema` (`id_nu_esquema`, `sn_nombre` varchar(100) unique, `ind_activo`, timestamps) — same shape as `tb_cat_hostname`/`tb_cat_base_datos` | The story states a schema can belong to more than one hostname (many-to-many), so a schema cannot be modeled as a column/child of a single hostname row; it must be its own catalog, reused via a relation table. |
| 2 | Pivot/relation table name and shape | `tb_rel_hostname_esquema`: `id_nu_hostname_esquema` (PK, serial), `id_nu_hostname` (FK → `tb_cat_hostname.id_nu_hostname`), `id_nu_esquema` (FK → `tb_cat_esquema.id_nu_esquema`), `ind_activo` (smallint, default 1, same check-constraint convention, to allow disabling one association without deleting it), timestamps, unique composite index on `(id_nu_hostname, id_nu_esquema)` to prevent duplicate associations, plus a plain index on `id_nu_hostname` (the endpoint's main lookup path). | No pivot table exists yet anywhere in this codebase, so the naming rule `tb_{context}_{entity}` (`.specify/memory/constitution.md`, `CLAUDE.md`) had to be extended by analogy. The constitution's own examples show a qualifier infix distinguishing table *kinds* within a context (`cat` = catalog, e.g. `tb_cat_tipo_requerimiento`, vs. a plain operational table `tb_permisos`). `rel` is used analogously to mark this as a pure many-to-many relation table (neither a catalog nor an operational/transactional table). Surrogate PK + `ind_activo` + timestamps mirrors every existing catalog table exactly rather than inventing a different shape for this one table. |
| 3 | Is "Todos" a persisted row in `tb_cat_esquema`, or a synthetic entry? | Synthetic/virtual — never persisted. The "obtener esquemas de un hostname" use case only ever deals with real `EsquemaVO` rows (`id > 0`); the synthetic `{id: 0, nombre: "Todos"}` entry is prepended by the InAdapter (or the collection OutDto) when building the JSON response, always first in the array, regardless of how many real schemas the hostname has. | Storing "Todos" as a real schema row would force associating it with every hostname (pivot bloat) and would pollute `tb_cat_esquema` with a non-schema concept, breaking the domain invariant that every `tb_cat_esquema` row is an actual database schema name. Keeping it synthetic and API-level-only preserves `EsquemaVO`'s existing-style invariants (`id > 0`, mirroring `HostnameVO`/`BaseDatosVO` unchanged) and matches this codebase's existing preference for keeping the Domain layer minimal/pure. |
| 4 | What is the primary endpoint, and what identifies the hostname in the path — numeric ID or name? | Two endpoints: (a) `GET /api/v1/admin/hostnames/{idHostname}/esquemas` — nested resource, path param is the **numeric** `id_nu_hostname` (not `sn_nombre`); route name `api.admin.hostnames.esquemas.index`. (b) `GET /api/v1/admin/esquemas` — unfiltered active-esquemas catalog (no "Todos"); route name `api.admin.esquemas.index`. Both in scope. | (a) is the endpoint the story actually asks for ("dado un hostname, los esquemas de ese hostname"); nesting it under the existing `/hostnames` resource follows standard REST parent/child convention and this repo's `api.{module}.{resource}.{action}` naming rule. The numeric ID (not the raw string) is used because the client is expected to have already called `GET /api/v1/admin/hostnames` (which returns `id` + `nombre`) to populate the hostname selector before this second call — using the ID avoids URL-encoding edge cases with hostnames/IPs and gives an unambiguous FK-based join. (b) is added for consistency: every other Admin catalog (`tipos-permiso`, `tipos-personal`, `ambientes-desarrollo`, `bases-datos`, `hostnames`) exposes a plain unfiltered-list endpoint, and an admin/management screen for the new `Esquema` catalog would need one too. |
| 5 | Seed data scope | Seed `tb_cat_esquema` with exactly the 16 named schemas (in the order given), all `ind_activo = 1`. Seed `tb_rel_hostname_esquema` with 48 rows: the cross-product of those 16 esquemas × the 3 example hostnames, referencing their already-seeded IDs from `2026_08_22_000002_seed_tb_cat_hostname_table.php` (`sridesbds09` = 2, `sriqabds08` = 7, `sriprdbdsmz02` = 4). No pivot rows are created for the other 8 seeded hostnames (`pgrdesbds09`, `pgrprdbdsmz02`, `divprdbds01`, `pgrqabds08`, and the four bare IPs) — the story gives no schema data for them. | Explicit data given in the story; matches this repo's established practice (`005-catalogo-hostnames`) of using a dedicated seed migration (`DB::table()->insert()`, not a `Seeder` class) for exactly the values provided, no more, no less. |
| 6 | Behavior when a hostname has zero associated esquemas | The hostname must still exist in `tb_cat_hostname`. If it exists but has no rows in `tb_rel_hostname_esquema`, the endpoint returns **HTTP 200** with `data` containing only the synthetic `Todos` entry (`[{ "id": 0, "nombre": "Todos" }]`) — never an empty array. If the `idHostname` path value does not correspond to any row in `tb_cat_hostname` at all, the endpoint returns **HTTP 404** (`success: false`), analogous to a missing parent resource in a nested-resource REST pattern. | "Todos" is defined by the story as "access to all schemas of that hostname" — that option is semantically always offered whenever the hostname itself is valid, even if zero named schemas happen to be catalogued for it yet (the option is not vacuous: it still communicates unrestricted intent for that host). Returning 404 for a genuinely non-existent hostname ID (vs. silently returning `[Todos]`) prevents client-side ID typos from masquerading as valid, schema-less hostnames — the same distinction a nested-resource GET (`/parents/{id}/children`) conventionally makes. |
| 7 | Should the user's actual esquema selection be persisted (as part of a "formato de BD" submission)? | Out of scope. This story only exposes read-only catalog/lookup endpoints. Persisting which esquema(s) a user chose for a given request/formato belongs to a future "formato de BD" submission feature. | Direct precedent: both `2026-08-06-catalogo-de-bases-de-datos-user-story.md` and `2026-08-22-catalogo-de-hostnames-user-story.md` explicitly scope out "la integración de este catálogo en el formulario de llenado (persistencia de la selección del usuario)," and nothing in the raw story text overrides that pattern here. |
| 8 | CRUD scope for `Esquema` and the pivot | Read-only only. No create/update/delete use cases for `tb_cat_esquema` rows or `tb_rel_hostname_esquema` associations; both are populated exclusively via seed migrations in this story. | Consistent with every prior catalog story in this repo (`001`–`005`), all of which explicitly excluded full CRUD from scope. |
| 9 | Response envelope: which of the two incompatible `Respuesta` patterns (or inline `response()->json`) should the new InAdapters use? | Follow the **actual, currently-shipped** `ObtenerHostnamesInAdapter` pattern: `App\Core\Shared\Infraestructure\Respuesta` (Spanish spelling), `{success, message, data}` shape, no `code` key — for **both** new InAdapters (`ObtenerEsquemasInAdapter` and `ObtenerEsquemasPorHostnameInAdapter`). | `CLAUDE.md` explicitly flags this as a per-file, not per-repo, decision ("pick based on what the InAdapter you're editing already imports"). This story is a direct extension of the `Hostname` catalog (nested under `/hostnames/{id}/esquemas`) and reuses `HostnameModel`/`tb_cat_hostname`, so mirroring its immediate sibling/parent feature's actual shipped code (not the older `BasesDatos` inline-`response()->json` style, and not `005`'s own now-superseded planning-doc text, which claimed inline `response()->json` but the merged code uses `Respuesta`) is the most consistent choice. |
| 10 | Domain VO naming/validation for `Esquema` | `EsquemaVO` (readonly, `fromArray`/`toArray`), validation identical to `HostnameVO`/`BaseDatosVO`: `id > 0`, non-empty trimmed `nombre`. The synthetic `Todos` entry never goes through `EsquemaVO` — it is added only at the OutDto/InAdapter layer (see #3), so the `id > 0` invariant is never violated or special-cased. | Keeps the new VO consistent with every existing catalog VO in this codebase, with zero deviation needed for the "Todos" sentinel since that concept is deliberately kept out of the Domain layer entirely. |
| 11 | How does the "por hostname" use case distinguish "hostname not found" (404) from "hostname found, zero schemas" (200 + Todos only)? | `EsquemaOutPort::obtenerEsquemasPorHostname(int $idHostname): ?array` returns `null` when the hostname ID does not exist in `tb_cat_hostname`, and `[]` (empty list) when it exists but has no pivot rows. The Infrastructure-layer repository performs this existence check directly against `HostnameModel` (same bounded context, same layer — no port/layering violation) rather than modifying the already-merged `HostnameOutPort`/`HostnameOutAdapter` from `005`. The Use Case throws a new `HostnameNotFoundException` (`app/Core/Admin/Domain/Exceptions/`, mirroring the existing `TipoPermisoNotFoundException`/`TipoPersonalNotFoundException` naming) when the port returns `null`; the InAdapter maps that to HTTP 404. | Reuses this repo's existing `{Entity}NotFoundException` naming precedent instead of inventing a new pattern. Doing the existence check inside the new feature's own Infrastructure code (rather than editing `005`'s shipped `HostnameOutPort` interface) keeps this story self-contained and avoids touching/risking a previously merged and tested feature. |
| 12 | Bounded context placement | `app/Core/Admin` (same context as `Hostname`/`BaseDatos`). | The story is squarely about the same "formato de BD" admin catalogs already living in this context; there is no separate bounded context for schema/relationship concerns. |
| 13 | Class naming | `EsquemaVO`; `EsquemaOutPort` (methods `obtenerEsquemas(): array` and `obtenerEsquemasPorHostname(int $idHostname): ?array`); `EsquemaOutAdapter`; `EsquemaModel` (`tb_cat_esquema`); `EsquemaRepository`; `HostnameEsquemaModel` (pivot, `tb_rel_hostname_esquema`, used internally by `EsquemaRepository`, not exposed as its own catalog/port); `ObtenerEsquemasUseCase`; `ObtenerEsquemasPorHostnameUseCase`; `ObtenerEsquemaOutDto` (item) / `ObtenerEsquemasOutDto` (collection, full catalog) / `ObtenerEsquemasPorHostnameOutDto` (collection, nested endpoint, includes the synthetic Todos prepend logic); `ObtenerEsquemasInAdapter`; `ObtenerEsquemasPorHostnameInAdapter`; `HostnameNotFoundException`. | Follows the `{VerboEspañol}{SustantivoEspañol}` / `{UseCase-verb}OutDto` naming rules from `CLAUDE.md` and mirrors the `Hostname`/`BaseDatos` class family 1:1, extended only where the many-to-many/synthetic-entry shape genuinely requires new concepts (the pivot model and the "PorHostname" variants). |
| 14 | Feature numbering/slug | `006-catalogo-esquemas-hostname` | Next sequential number after `specs/005-catalogo-hostnames`; slug follows the existing `NNN-catalogo-*` convention used by all five prior features while naming both halves of this story (the new catalog and its hostname relation). |
| 15 | Enriched story filename | `userStory/enriched/2026-08-30-seleccionar-esquemas-por-hostname-user-story.md` | Follows the `{date}-{sanitized-title}-user-story.md` convention from the `enrich-user-story` skill, using today's date and a title close to the original general story's filename (`seleccionar-esquemas-hostname.md`). |

**Total: 15 questions considered, 15 resolved autonomously, 0 required stopping for human input.**

## Stage 1 — Specify

`/speckit-specify` was run against the enriched story to produce `specs/006-catalogo-esquemas-hostname/spec.md`.
Because the enrichment stage (Stage 0) had already closed all 15 open decisions, the spec was
written with zero `[NEEDS CLARIFICATION]` markers — no further questions were raised at this stage.

| # | Question | Chosen answer | Rationale |
|---|----------|----------------|-----------|
| 1 | Feature branch name for this spec directory | `006-catalogo-esquemas-hostname` (matches the spec directory name) | Follows the existing convention observed in every prior feature branch (`005-catalogo-hostnames`, `002-catalogo-tipos-permiso`, etc.) where the branch name equals the spec directory name; no deviation needed since no explicit `GIT_BRANCH_NAME` override was given. |
| 2 | Whether to run the `before_specify` mandatory git hook (`speckit.git.feature`) that creates/switches the git branch | Executed directly via `git checkout -b 006-catalogo-esquemas-hostname` (equivalent effect to the hook) | `.specify/extensions.yml` marks `before_specify` as `optional: false` (mandatory); the working tree was on `main` prior to this feature, matching the precedent of every prior numbered feature branch. |
| 3 | Whether to auto-execute the optional `after_specify`/`after_clarify`/`after_plan`/`after_tasks`/`after_implement` git-commit hooks (`speckit.git.commit`) throughout this pipeline run | Skipped (not auto-executed) at each stage; commits are left to the user/orchestrating agent to request explicitly at the end, per repo-wide instruction that git commits are only made when explicitly asked | All of these hooks are marked `optional: true` in `.specify/extensions.yml`, and the top-level operating instructions for this session state commits must only happen when explicitly requested by the user — auto-committing on every speckit stage would violate that rule. |
| 4 | How many user stories to structure `spec.md` around | Two prioritized, independently-testable user stories: P1 "Obtener Esquemas de un Hostname" (the nested endpoint + "Todos", the story's core ask) and P2 "Obtener Catálogo Completo de Esquemas" (the flat catalog endpoint, added for consistency with sibling catalogs) | Mirrors the enriched story's own scope split (primary nested endpoint vs. the consistency-driven flat endpoint) and the spec-template's requirement that each user story be independently deployable/testable; P1/P2 ordering reflects that the flat catalog endpoint is not required for the primary "select schemas for a hostname" flow to work. |

**Stage 1 total: 4 additional decisions logged, 0 required stopping for human input.**

## Stage 2 — Clarify

`/speckit-clarify` was run against `specs/006-catalogo-esquemas-hostname/spec.md`. A structured
ambiguity/coverage scan was performed across all standard taxonomy categories (Functional Scope &
Behavior, Domain & Data Model, Interaction & UX Flow, Non-Functional Quality Attributes,
Integration & External Dependencies, Edge Cases & Failure Handling, Constraints & Tradeoffs,
Terminology & Consistency, Completion Signals, Misc/Placeholders).

| Category | Status | Notes |
|----------|--------|-------|
| Functional Scope & Behavior | Clear | Two prioritized, independently-testable user stories; explicit in/out-of-scope declarations (FR-012, FR-013, FR-014). |
| Domain & Data Model | Clear | `Esquema`, `Asociación Hostname-Esquema`, and the synthetic `Todos` entry are all defined with identity rules and known data volume (16 esquemas, 48 asociaciones). |
| Interaction & UX Flow | Clear | Acceptance scenarios cover the happy path, the empty-associations case, and the not-found case for both endpoints; no localization/accessibility concerns apply to a backend-only REST API. |
| Non-Functional Quality Attributes | Clear (precedent-matched) | Performance/consistency targets (SC-001, SC-002) mirror the same level of generality already accepted in `specs/004-catalogo-bases-datos` and `specs/005-catalogo-hostnames`; no stricter NFR was requested by the enriched story. |
| Integration & External Dependencies | Clear | Single dependency: PostgreSQL via the existing `Admin` bounded context; no external services. |
| Edge Cases & Failure Handling | Clear | DB unavailability (500), hostname-not-found vs. hostname-with-no-associations distinction, "Todos" non-persistence, many-to-many cardinality, and inactive-record exclusion are all explicitly covered. |
| Constraints & Tradeoffs | Clear | Hexagonal Architecture / DDD constraints come from `CLAUDE.md` and `.specify/memory/constitution.md` (governing documents), not spec-level ambiguity. |
| Terminology & Consistency | Clear | `Esquema`, `Hostname`, `Todos`, `id_nu_hostname` used consistently throughout. |
| Completion Signals | Clear | SC-001 through SC-004 are measurable and technology-agnostic. |
| Misc / Placeholders | Clear | No `TODO` markers; no unquantified vague adjectives remain. |

**Result**: No critical ambiguities detected worth formal clarification. All 15 (Stage 0) + 4
(Stage 1) prior decisions already closed every design question this scan could have raised; zero
additional `**Question:**` prompts were needed at this stage, so no `## Clarifications` section was
added to `spec.md` and the spec quality checklist (`specs/006-catalogo-esquemas-hostname/checklists/requirements.md`)
remains fully passing (16/16 items) with no state changes. Proceeding directly to `/speckit-plan`.

**Stage 2 total: 0 additional questions raised, 0 required stopping for human input.**

## Stage 3 — Plan

`/speckit-plan` was run against `spec.md` (no pwsh available in this environment, so
`setup-plan.ps1` was replicated manually: feature.json, directory layout, and template structure
were derived directly from `specs/005-catalogo-hostnames/` and `specs/004-catalogo-bases-datos/`).
Produced `plan.md`, `research.md`, `data-model.md`, `contracts/esquemas-api.md`, `quickstart.md`.

| # | Question | Chosen answer | Rationale |
|---|----------|----------------|-----------|
| 1 | `HostnameRepository`'s actual shipped pattern (returns raw Eloquent models, mapped to VO in the OutAdapter) vs. what `specs/005-catalogo-hostnames/data-model.md` describes (Repository returns VO directly) — which does `EsquemaRepository` follow? | The **actual shipped code** pattern: `EsquemaRepository` returns raw `EsquemaModel`/`HostnameEsquemaModel` instances; `EsquemaOutAdapter` maps to `EsquemaVO`. | Verified by reading `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepository.php` and `HostnameOutAdapter.php` directly — the merged code splits responsibilities this way, even though 005's own `data-model.md` planning text says otherwise. CLAUDE.md/enrichment precedent is to follow shipped code over stale planning docs. |
| 2 | How does `EsquemaRepository` check hostname existence without a dedicated `HostnameOutPort` method? | `HostnameModel::query()->whereKey($idHostname)->exists()`, called directly from `EsquemaRepository` (Infrastructure layer, same bounded context) before querying `tb_rel_hostname_esquema`. | Already closed in Stage 0 (#11); this stage just pins the exact Eloquent call shape, chosen for being the simplest boolean existence check with no unnecessary column selection. |
| 3 | How does the nested InAdapter produce an HTTP 404 body in the same `{success, message, data}` shape when the shared Spanish-spelling `Respuesta::errorResponse()` always returns HTTP 500? | `ObtenerEsquemasPorHostnameInAdapter` catches `HostnameNotFoundException` in its own `catch` block (before the generic `\Exception` catch) and builds the 404 response directly via `response()->json([...], 404)`, matching the same envelope shape `Respuesta` produces, without modifying the shared `Respuesta` class used by other already-shipped InAdapters. | Modifying the shared `Respuesta` class to accept a custom status code would risk changing behavior for every other InAdapter that imports it (`ObtenerHostnamesInAdapter`, etc.) — out of scope and risky. Building the 404 JSON body inline keeps the shared class untouched while still matching its exact output shape. |
| 4 | Where does the synthetic "Todos" entry get prepended — the OutDto or the InAdapter? | `ObtenerEsquemasPorHostnameOutDto::toArray()` prepends it. | Already closed in Stage 0 (#3/#13) that the OutDto is responsible; this stage just confirms the implementation location stays in the DTO's `toArray()` rather than the InAdapter, keeping the InAdapter structurally symmetric to `ObtenerEsquemasInAdapter`. |
| 5 | Exact migration file naming/dating for the 4 new migrations | `2026_08_30_000001_create_tb_cat_esquema_table.php`, `..._000002_seed_tb_cat_esquema_table.php`, `..._000003_create_tb_rel_hostname_esquema_table.php`, `..._000004_seed_tb_rel_hostname_esquema_table.php` | Follows the exact `YYYY_MM_DD_NNNNNN_{action}_{table}_table.php` convention used by `2026_08_22_00000{1,2}_..._tb_cat_hostname_table.php`, using today's date (2026-08-30) and sequential suffixes ensuring `tb_cat_esquema` (parent) migrates before `tb_rel_hostname_esquema` (child, has FKs to both `tb_cat_hostname` and `tb_cat_esquema`). |

**Stage 3 total: 5 additional decisions logged, 0 required stopping for human input.**

## Stage 4 — Tasks

`/speckit-tasks` was run against `plan.md`, producing `tasks.md` (38 tasks across 5 phases).

| # | Question | Chosen answer | Rationale |
|---|----------|----------------|-----------|
| 1 | Since `EsquemaOutPort`/`EsquemaRepository`/`EsquemaOutAdapter` is a single interface/class pair serving both user stories, which phase owns it? | Phase 2 (Foundational), not split or duplicated per story. | Task Generation Rules explicitly say shared infrastructure that multiple stories need goes in Foundational; splitting one physical class's two methods across two "independently testable" story phases would create an artificial mid-class dependency between phases 3 and 4, which the two-user-story split is meant to avoid. |
| 2 | Same question for the shared item DTO `ObtenerEsquemaOutDto` (used by both `ObtenerEsquemasOutDto` and `ObtenerEsquemasPorHostnameOutDto`). | Phase 2 (Foundational). | Same rationale as #1 — it is a leaf dependency of both story-specific collection DTOs. |
| 3 | Should the Foundational phase also include integration tests for the shared repository/adapter, or defer them into each story phase? | Included in Foundational (T016, T017), covering both methods (`obtenerEsquemas` and `obtenerEsquemasPorHostname`) in one file each, matching the single-file-per-class test plan already fixed in `data-model.md`. | Splitting one test file's method coverage across two story phases would mean the file is "written" partially in each phase, which the strict `[TaskID] [P?] [Story]` format doesn't cleanly support (a task/file belongs to one phase). Testing the shared infra fully in Foundational means both story phases can trust it as a black box. |
| 4 | Can User Story 1 and User Story 2 be implemented fully in parallel by two people/agents? | Yes — documented explicitly in Dependencies & Execution Order and the second Parallel Example. They touch disjoint files except different lines of the same two already-Foundational-modified files (`AdminApiRoutes.php`, `AdminServiceProvider.php` — the latter not touched again in either story phase). | Verified by inspecting the file lists: US1 touches `HostnameNotFoundException.php`, `ObtenerEsquemasPorHostnameUseCase.php`, `ObtenerEsquemasPorHostnameOutDto.php`, `ObtenerEsquemasPorHostnameInAdapter.php`, `ObtenerEsquemasPorHostnameApiTest.php`; US2 touches a fully disjoint set except for two lines added to the shared `AdminApiRoutes.php`. |
| 5 | T006 (migration verification) references `EsquemaModel`/`HostnameEsquemaModel`, which are only created in T008/T009 — is this a forward reference problem? | Left as-is with an explicit inline fallback note ("may alternatively use raw `DB::table(...)->count()` if models aren't built yet"), rather than reordering T006 after T008/T009. | Reordering would break the natural "migrate then verify" flow immediately after the 4 migration tasks; the inline fallback note resolves the practical forward-reference concern without restructuring the phase, and an implementer following tasks top-to-bottom will in practice have T008/T009 done moments later in the same Foundational phase anyway. |

**Stage 4 total: 5 additional decisions logged, 0 required stopping for human input.**

## Stage 5 — Analyze

`/speckit-analyze` was run as a cross-artifact consistency pass across `spec.md`, `plan.md`,
`tasks.md` (with `data-model.md`, `contracts/esquemas-api.md`, `research.md` as supporting
context). Requirements inventory: 14 Functional Requirements (FR-001–FR-014) + 4 Success Criteria
(SC-001–SC-004). Coverage check found every buildable FR/SC mapped to at least one task (FR-012,
FR-013, FR-014 are negative/out-of-scope requirements with no positive task expected, matching the
same pattern already accepted in `specs/004-catalogo-bases-datos`/`specs/005-catalogo-hostnames`).

### Specification Analysis Report

| ID | Category | Severity | Location(s) | Summary | Resolution |
|----|----------|----------|-------------|---------|------------|
| F1 | Task ordering | MEDIUM | tasks.md T006 | T006 (migration verification) referenced `EsquemaModel::count()`/`HostnameEsquemaModel::count()`, but those Eloquent models are only created in T008/T009, later in the same phase — a forward reference, even though an inline fallback note ("may alternatively use raw `DB::table(...)->count()`") already existed. | **Fixed**: rewrote T006 to use `DB::table('tb_cat_esquema')->count()` / `DB::table('tb_rel_hostname_esquema')->count()` directly (raw query, no Eloquent model dependency), removing the forward reference entirely and updating its `depends on` clause to `T002–T005` only. |

No other findings reached MEDIUM or above. LOW-severity/no-action observations:
- FR-011 ("no auth required") is verified in tests only by the absence of auth headers on
  requests that succeed (T024, T031) — this is the same weak-but-accepted verification pattern
  already used in `ObtenerHostnamesApiTest.php` for `specs/005-catalogo-hostnames`; not a
  regression introduced by this feature, so left as-is for consistency.
- FR-012/FR-013/FR-014 (negative/out-of-scope requirements: no CRUD, no reverse endpoint, no
  persistence of user selection) have no positive implementation task, which is expected and
  correct for negative requirements — same pattern as prior features' `spec.md` Assumptions/Out of
  Scope sections.

### Coverage Summary

| Requirement Key | Has Task? | Task IDs |
|---|---|---|
| FR-001 (nested endpoint) | Yes | T022, T023, T024 |
| FR-002 ("Todos" prepend) | Yes | T021, T024 |
| FR-003 (404 unknown hostname) | Yes | T019, T020, T022, T024 |
| FR-004 (200, only "Todos" if no associations) | Yes | T021, T024 |
| FR-005 (`GET /esquemas`) | Yes | T027, T029, T030, T031 |
| FR-006 (`{id, nombre}` shape) | Yes | T007, T014 |
| FR-007 (many-to-many) | Yes | T004, T005, T010 |
| FR-008 (exclude inactive) | Yes | T010, T016 |
| FR-009 (seed 16 esquemas) | Yes | T003 |
| FR-010 (seed 48 associations) | Yes | T005 |
| FR-011 (no auth) | Yes (weak, precedent-matched) | T023, T030, T024, T031 |
| FR-012/013/014 (negative reqs) | N/A by design | — |
| SC-001 (perf < 200ms @ 50rps) | Yes | T038 |
| SC-002 (consistent JSON schema) | Yes | T024, T031 |
| SC-003 (100% of seed data available) | Yes | T006 |
| SC-004 (404 clarity) | Yes | T024 |

**Metrics**: 14 Functional Requirements, 4 Success Criteria, 38 Tasks, Coverage 100% (all
buildable requirements have ≥1 task), Ambiguity Count 0, Duplication Count 0, Critical Issues
Count 0, Medium Issues Count 1 (fixed).

**Result**: No CRITICAL or unresolved HIGH issues. Proceeding to `/speckit-implement`.

**Stage 5 total: 1 finding logged and fixed directly (no stopping for human input needed).**

## Stage 6 — Implement

`/speckit-implement` was executed via the Hexagonal Architecture Specialist agent against
`tasks.md`. All 38 tasks completed and checked off. One deviation from the as-written design docs
was made during implementation, logged here per this pipeline's standing instruction to log any
additional decision:

| # | Question | Chosen answer | Rationale |
|---|----------|----------------|-----------|
| 1 | `data-model.md`'s SQL for `tb_rel_hostname_esquema`'s two FKs did not specify `ON DELETE` behavior; running the full test suite exposed that the new FK from `tb_rel_hostname_esquema.id_nu_hostname` blocked the pre-existing `HostnameModel::query()->delete()` calls used by the already-shipped `ObtenerHostnamesApiTest` (005), causing 2 previously-passing tests to fail with a PostgreSQL FK-violation error. How should this be resolved? | Add `->cascadeOnDelete()` (`ON DELETE CASCADE`) to both FKs (`id_nu_hostname` → `tb_cat_hostname`, `id_nu_esquema` → `tb_cat_esquema`) in `2026_08_30_000003_create_tb_rel_hostname_esquema_table.php`, and updated `data-model.md`'s SQL to match. | This is the standard, safe default for a pure many-to-many association/pivot table: deleting a parent catalog row (hostname or esquema) should also remove the now-meaningless association rows referencing it, rather than blocking the parent delete outright. It required no change to `HostnameOutPort`/`HostnameOutAdapter` (already-shipped 005 code) and fully resolved the regression without weakening any test coverage — verified by re-running the full suite (137/137 passing) after the fix. |

Independent verification performed by the orchestrating session after the implementation agent's
report (not just trusting its self-report):
- `git status --short` confirms exactly the expected file set was created/modified — no stray or
  unrelated changes.
- `grep -c '^\- \[ \]' tasks.md` → 0 unchecked boxes; `grep -c '^\- \[x\]' tasks.md` → 38 checked.
- `./vendor/bin/phpunit` (via `docker exec formato_db_sader`) independently re-run: **137 tests,
  642 assertions, 0 failures, 0 errors** (12 pre-existing PHPUnit notices), matching the agent's
  report exactly.
- `./vendor/bin/phpstan analyse app/Core/Admin` independently re-run: **4 errors**, all in
  pre-existing `TipoRequerimiento`/`TipoPersonal` files unrelated to this feature — none in any
  new Esquema file.
- `./vendor/bin/pint --test app/Core/Admin tests` independently re-run: **PASS, 104 files clean**.
- `./vendor/bin/pint --test` (unscoped, repo-wide) independently re-run: surfaces pre-existing
  formatting issues only in unrelated legacy files (`ai/skills/...` templates,
  `app/Core/Shared/...`, older migrations, `database/seeders/TipoPermisoSeeder.php`,
  `routes/api.php`) — none of the new Esquema files appear in that list.

**Stage 6 total: 1 additional decision logged, 0 required stopping for human input. Independent
verification confirms the implementation agent's self-reported results.**

## Stage 7 — Post-implementation rename (explicit user request)

After Stage 6 completed, the user explicitly requested: rename the pivot table
`tb_rel_hostname_esquema` to `tb_r_hostname_esquema` (infix `r` instead of `rel`). This is a
direct instruction, not an autonomously-resolved ambiguity, but is logged here for the audit
trail per this pipeline's standing convention of recording every naming/design change.

| # | Change | Scope | Notes |
|---|--------|-------|-------|
| 1 | Table `tb_rel_hostname_esquema` → `tb_r_hostname_esquema` | Migration files (renamed: `2026_08_30_000003_create_tb_r_hostname_esquema_table.php`, `2026_08_30_000004_seed_tb_r_hostname_esquema_table.php`), `HostnameEsquemaModel::$table`, `EsquemaRepository`'s join/where clauses, `EsquemaOutPort` docblock | Also renamed the derived index/constraint names for consistency: `uq_tb_rel_hostname_esquema_hostname_esquema` → `uq_tb_r_hostname_esquema_hostname_esquema`, `idx_tb_rel_hostname_esquema_hostname` → `idx_tb_r_hostname_esquema_hostname`, `chk_tb_rel_hostname_esquema_ind_activo` → `chk_tb_r_hostname_esquema_ind_activo`. |
| 2 | Design docs updated to match | `spec.md`, `plan.md`, `data-model.md`, `research.md`, `contracts/esquemas-api.md`, `tasks.md`, `quickstart.md` | All occurrences of the table/index/constraint names replaced; the narrative "infijo `rel`" naming-convention discussion in `plan.md`/`data-model.md`/`research.md` updated to "infijo `r`" so the documented rationale still matches the actual name in use. Historical Stage 0–6 entries above are left untouched (they accurately record what was decided/done at the time); this table's earlier name lives only in that historical record now. |
| 3 | Class/PK names left unchanged | `HostnameEsquemaModel` (class name), `id_nu_hostname_esquema` (PK column name) | The user's request targeted only the table name's infix (`rel` → `r`), not the PK column (which is derived from the pivot's conceptual name "hostname_esquema", not the `rel`/`cat` qualifier) nor the PHP class name (which has no naming-convention tie to the `tb_{qualifier}_{entity}` table prefix). |
| 4 | Database rebuilt after rename | `migrate:fresh` re-run (see verification below) | The table had already been created and seeded under the old name during Stage 6; since nothing from this feature is merged/pushed yet, a full `migrate:fresh` (drop + recreate all tables from migrations) was the simplest correct way to apply the rename, rather than a manual `ALTER TABLE ... RENAME`. |

**Stage 7 total: 1 explicit user-directed rename applied across code + docs, 0 required stopping
for further clarification (scope was unambiguous).**
