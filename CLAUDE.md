# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SADER Database Access Permissions API — an internal, backend-only REST API (Laravel used purely as
infrastructure) built with strict Hexagonal Architecture (Ports & Adapters) and DDD. There is no
frontend, SSR, Blade, or Livewire in scope. Domain terminology is Spanish where it reflects SADER
institutional language (e.g., `TipoPermiso`, `TipoRequerimiento`).

Governing document: `.specify/memory/constitution.md` (v1.1.0) — this supersedes any conflicting
convention. `.github/copilot-instructions.md` and `.github/skills/arquitectura-hexagonal/SKILL.md`
provide the same rules in operational form.

## Commands

```bash
# Install & environment
composer install
cp .env.example .env && php artisan key:generate

# Local services (Postgres 16, Redis 7.4)
docker-compose up -d
php artisan migrate
php artisan db:seed          # optional

# Run everything (server + queue + logs + vite) concurrently
composer dev

# Tests (uses the 'testing' env from phpunit.xml, driver forced to pgsql)
./vendor/bin/phpunit
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature
./vendor/bin/phpunit --filter ObtenerTiposPermisoUseCaseTest         # single test class
./vendor/bin/phpunit tests/Unit/Core/Admin/Domain/Entities/TipoPermisoTest.php  # single file
php artisan test                                                     # composer "test" script equivalent

# Static analysis & formatting
./vendor/bin/phpstan analyse     # phpstan.neon currently pins level: 5 (README/constitution target is 9 — check before assuming stricter rules are enforced)
./vendor/bin/pint                # apply PSR-12 formatting
./vendor/bin/pint --test         # check formatting only
```

There is no `Integration` testsuite wired in `phpunit.xml` — tests under `tests/Integration/`
must currently be run by explicit path (`./vendor/bin/phpunit tests/Integration`).

## Architecture

### Layering (enforced dependency direction: `Infrastructure → Application → Domain`)

Every bounded context lives under `app/Core/{BoundedContext}/` (currently only `Admin`, plus a
`Shared` context for cross-cutting utilities):

```
app/Core/{BoundedContext}/
├── Domain/                          # Pure PHP, ZERO Laravel imports
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Exceptions/
├── Application/
│   ├── UseCases/                    # One class per use case, orchestration only
│   ├── DTOs/{In,Out}/               # Immutable data carriers between layers
│   └── Ports/
│       ├── In/                      # Use case interfaces
│       └── Out/                     # Repository / external-service interfaces
└── Infrastructure/
    ├── Adapters/
    │   ├── In/Api/                  # Invokable "InAdapter" classes (HTTP entry points)
    │   └── Out/PostgresSQL/         # Eloquent models + repositories + OutAdapters
    ├── Providers/{Context}ServiceProvider.php   # Binds Out ports -> OutAdapters; loads routes
    └── Routes/{Context}ApiRoutes.php
```

- Domain MUST NOT import anything from `Illuminate\*`. Application MAY depend on Domain only.
  Infrastructure implements Application's ports.
- Use cases MUST NOT depend on Eloquent directly — only on `Application/Ports/Out` interfaces,
  which `{Context}ServiceProvider::register()` binds to concrete `*PostgresSQLOutAdapter` classes.
- New context providers must be registered in `bootstrap/providers.php` and must call
  `$this->loadRoutesFrom(...)` in `boot()` — routes are never added to `routes/web.php`/`api.php`.

### Naming conventions (non-negotiable per the hexagonal-architecture skill)

- **InAdapters**: `{VerbSpanish}{NounSpanish}InAdapter`, invokable (`__invoke()`), constructed via
  `app()->make({UseCase}::class)` in the constructor (not constructor-promoted DI) — see
  `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerTiposPermisoInAdapter.php` for the
  canonical pattern. Use Spanish verbs: `Obtener`, `Crear`, `Actualizar`, `Eliminar`, `Listar`,
  `Generar` — never English verbs or a `Controller` suffix.
- **DTOs**: must be prefixed with the use-case verb, e.g. `ObtenerTiposPermisoOutDto`, never
  generic names like `TipoPermisoDataDto` or `ItemDto`.
- **Routes**: one file per context at `Infrastructure/Routes/{Context}ApiRoutes.php`, always under
  `api/v1/{context}`, named `api.{module}.{resource}.{action}`.
- **Database tables**: `tb_{context}_{entity}` (e.g. `tb_cat_tipo_permiso`); columns snake_case;
  audit fields `created_at`, `updated_at`, `created_by_user_id`, `updated_by_user_id`.

### Response envelope: two `Respuesta` classes exist — know which is which

There are **two different, incompatible** `Respuesta` helper classes in the codebase; pick based
on what the InAdapter you're editing already imports (existing code is inconsistent — both are
in active use):

- `App\Core\Shared\Infraestructure\Respuesta` (note: "Infraestructure" — Spanish spelling, no
  Laravel exception-mapping logic). `successResponse()`/`errorResponse()` return a fixed
  `{success, message, data}` shape with status 200/500 only. Used by
  `ObtenerTiposPermisoInAdapter`.
- `App\Core\Shared\Infrastructure\Respuesta` (English spelling) — richer, maps specific exception
  types (`ValidationException` → 422, `ModelNotFoundException` → 404, `QueryException` 1451 →
  409, etc.) and returns `{message, success, data, code}`. Used by
  `ObtenerTiposPersonalInAdapter` / `ObtenerTiposRequerimientosInAdapter`.

Do not assume one is the "real" one and silently swap it in existing files — check the existing
import in the file you're touching.

### Use case implementation workflow

The constitution mandates that all new use cases be built via the `hexagonal-architecture-specialist`
/ `@hexagonal-usecase` agent workflow (`.github/skills/arquitectura-hexagonal/SKILL.md`), and that
feature work generally flows through the spec-kit commands already present in this repo:
`/speckit.specify` → `/speckit.plan` → `/speckit.tasks` → implement. Specs, plans, and
task breakdowns for existing features live under `specs/{NNN-feature-slug}/`.

### Database connection env vars are non-standard

`config/database.php`'s `pgsql` connection reads `DB_HOST_PGSQL`, `DB_PORT_PGSQL`,
`DB_DATABASE_PGSQL`, `DB_USERNAME_PGSQL`, `DB_PASSWORD_PGSQL` — **not** Laravel's default
`DB_HOST`/`DB_DATABASE`/etc. `.env.example` still ships with sqlite defaults; real local/test
config must set the `_PGSQL`-suffixed vars (see `phpunit.xml` and `docker-compose.yml` for
working values).

### Testing structure mirrors production layout

- `tests/Unit/Core/{Context}/Domain|Application/...` — pure PHP, no Laravel bootstrap for Domain.
- `tests/Integration/Core/{Context}/Infrastructure/Adapters/Out/.../*IntegrationTest.php` —
  repository adapters against real Postgres.
- `tests/Feature/Core/{Context}/Api/*ApiTest.php` — HTTP contract tests for InAdapters.

Naming: `{UseCase}Test.php`, `{Adapter}IntegrationTest.php`, `{Endpoint}ApiTest.php`.
