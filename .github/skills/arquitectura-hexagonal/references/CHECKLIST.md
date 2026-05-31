# Hexagonal Architecture Implementation Checklist

Use this checklist to validate every use case implementation. Each section contains critical rules that MUST be followed.

## 🏗️ Domain Layer

### Entity Validation
- [ ] 🚨 **NO ANEMIC ENTITIES**: Every Entity has business behavior (not just getters!)
- [ ] 🚨 **Entity vs DTO Check**: Catalog/lookup data uses DTOs, not Entities (see ENTITY_VS_DTO_DECISION_GUIDE.md)
- [ ] Entities have business methods (e.g., `aprobar()`, `rechazar()`, `activar()`)
- [ ] Entities protect invariants in constructor
- [ ] Entities manage state transitions
- [ ] NO `toArray()` or `toJson()` as only methods (these are NOT business logic!)

### Other Domain Components
- [ ] Value Objects with validation in constructor
- [ ] Enums for restricted values (catalog data like estados civiles, tipos)
- [ ] Domain Exceptions for business rule violations
- [ ] Specifications for reusable boolean business rules (if applicable)
- [ ] Domain Services for logic spanning multiple entities (if applicable)
- [ ] Domain Events for state change notifications (if applicable)

## 🎯 Application Layer

### UseCase Validation
- [ ] 🚨 **Spanish Naming**: Uses `Obtener`, `Crear`, `Actualizar`, `Eliminar` (NOT English verbs!)
- [ ] 🚨 **NO interface for simple CRUD**: Only implement InPort when using Decorator pattern
- [ ] 🚨 **Returns RAW array**: NOT DTOs (for maximum reusability)
- [ ] 🚨 **Throws exceptions**: Does NOT catch them (InAdapter responsibility)
- [ ] 🚨 **Depends on OutPort interface**: NOT direct Repository or OutAdapter
- [ ] 🚨 **Standard constructor**: Property declared separately, NOT `private readonly`
- [ ] UseCase class name: `{Verb}{Entity}UseCase.php`
- [ ] Method name: `ejecutar()` (Spanish, not `execute`)
- [ ] No Laravel imports (`Illuminate\*`)

### Port Validation (Interfaces)
- [ ] 🚨 **InPort only for Decorator**: Simple CRUD doesn't need InPort interface
- [ ] 🚨 **OutPort always required**: Application layer depends on OutPort interface
- [ ] OutPort interface name: `I{Entity}OutPort.php`
- [ ] InPort interface name: `I{UseCase}InPort.php` (if needed)
- [ ] Clear method contracts with types

### DTO Validation
- [ ] **InDto**: Data from external world → Application
- [ ] **OutDTO**: Data from Application → Infrastructure
- [ ] 🚨 **OutDTO naming**: ALWAYS format `{VerbSpanish}{ConceptSpanish}OutDto` (e.g., `ObtenerTipoRequerimientoOutDto`)
- [ ] 🚨 **OutDTO NO generic names**: NEVER use `ItemDto`, `DataDto`, `InfoDto` suffixes
- [ ] 🚨 **OutDTO verb prefix**: ALWAYS include use case verb (Obtener, Crear, Listar, etc.)
- [ ] 🚨 **OutDTO singular/plural**: Use singular for single items, plural for collections
- [ ] 🚨 **OutDTO NO metadata**: NO `$success`, `$message`, `$status` properties
- [ ] 🚨 **OutDTO semantic names**: Use `$tiposRequerimientos`, NOT generic `$data`
- [ ] 🚨 **OutDTO nested DTOs**: For collections, use array of specific DTOs
- [ ] All DTO properties are typed (PHP 8+)
- [ ] DTOs are readonly (immutable)
- [ ] InDto name matches UseCase: `{Verb}{Entity}InDto`
- [ ] OutDto name matches UseCase: `{Verb}{Entity}OutDto` (singular) or `{Verb}{Entity}sOutDto` (plural)

## 🔌 Infrastructure Layer

### Repository Validation
- [ ] 🚨 **NO interface implementation**: Repository is concrete class, does NOT implement OutPort!
- [ ] 🚨 **Returns RAW data**: Arrays, stdClass, primitives (NOT Domain Entities)
- [ ] 🚨 **Pure data access**: NO business logic, NO entity creation
- [ ] Uses Laravel Query Builder (NOT Eloquent models directly)
- [ ] Descriptive method names: `buscarPorId()`, `buscarTodos()`, `guardar()`, `actualizar()`, `eliminar()`
- [ ] Lives in: `Infrastructure/Adapters/Out/{Provider}/Repositories/`
- [ ] File name: `{Entity}{Provider}Repository.php`

### OutAdapter Validation
- [ ] 🚨 **Implements OutPort**: `implements I{Entity}OutPort`
- [ ] 🚨 **Uses Repository**: Injects Repository through constructor
- [ ] 🚨 **NO direct database access**: All DB operations through Repository
- [ ] 🚨 **Standard constructor**: Property declared separately, NOT `private readonly`
- [ ] Maps between raw data and Domain (or returns raw data as-is)
- [ ] Handles database exceptions, throws domain exceptions
- [ ] Lives in: `Infrastructure/Adapters/Out/{Provider}/`
- [ ] File name: `{Entity}{Provider}OutAdapter.php`

### InAdapter Validation
- [ ] 🚨 **Spanish naming**: `Obtener`, `Crear`, `Actualizar`, `Eliminar` (NOT Get, Create, etc.)
- [ ] 🚨 **InAdapter suffix**: NOT `Controller` suffix
- [ ] 🚨 **Uses app()->make()**: For dependency resolution in constructor
- [ ] 🚨 **Constructor try-catch**: Wraps `app()->make()` call
- [ ] 🚨 **Property declared separately**: NOT `private readonly`
- [ ] 🚨 **Uses Respuesta class**: `use App\Core\Shared\Infraestructure\Respuesta;` (note: Infraestructure with 'a')
- [ ] 🚨 **Method try-catch**: Wraps entire method body
- [ ] Creates `$respuesta = new Respuesta();` as first line
- [ ] Sets success/message/data: `$respuesta->setSuccess()`, `setMessage()`, `setData()`
- [ ] Returns standardized: `$respuesta->successResponse()` or `$respuesta->errorResponse($ex)`
- [ ] Lives in: `Infrastructure/Adapters/In/{Type}/` (API, Web, CLI, Queue)
- [ ] File name: `{Verb}{Entity}InAdapter.php`

### 🚨 Route Registration Validation (CRITICAL for API InAdapters)
- [ ] 🚨 **Route file location**: Created in `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`
- [ ] 🚨 **API versioning**: Uses `api/v1/{module}` prefix (NOT `api/{module}`)
- [ ] 🚨 **Route naming**: Follows `api.{module}.{resource}.{action}` pattern
- [ ] 🚨 **NO default route files**: Routes NOT in `routes/web.php`, `routes/api.php`, or `routes/console.php`
- [ ] 🚨 **ServiceProvider registration**: Routes loaded via `loadRoutesFrom()` in module's ServiceProvider
- [ ] 🚨 **ServiceProvider boot**: Routes registered in `boot()` method (NOT `register()`)
- [ ] Route imports InAdapter classes correctly
- [ ] Route uses descriptive parameter names (`{solicitudId}`, not just `{id}`)
- [ ] Routes tested with `php artisan route:list --path=api/v1/{module}`
- [ ] See [ROUTING_CONVENTIONS.md](ROUTING_CONVENTIONS.md) for complete guide

## 🧪 Tests

- [ ] Unit test for UseCase happy path
- [ ] Unit tests for each domain exception scenario
- [ ] Unit tests for edge cases and validations
- [ ] Tests use mocks for OutPorts (not real database)
- [ ] Tests are in: `tests/Unit/Core/{Module}/Application/UseCases/`

## ⚙️ Configuration

### ServiceProvider Validation
- [ ] 🚨 **OutPort → OutAdapter**: Binds OutPort interface to OutAdapter class (NOT Repository!)
- [ ] 🚨 **NEVER OutPort → Repository**: Critical mistake! Must bind to OutAdapter
- [ ] Repository singleton binding (for OutAdapter dependency injection)
- [ ] 🚨 **Route loading**: Uses `loadRoutesFrom(__DIR__ . '/../Routes/{Module}ApiRoutes.php')` in boot()
- [ ] ServiceProvider registered in `bootstrap/providers.php` (Laravel 11+) or `config/app.php`
- [ ] InPort binding only if using Decorator pattern
- [ ] InAdapter registration (routes, commands, jobs)

### Flow Verification
```
✅ CORRECT FLOW:
UseCase → OutPort interface (Application layer)
           ↓ (ServiceProvider binds to)
      OutAdapter (Infrastructure layer, implements OutPort)
           ↓ (uses)
      Repository (Infrastructure layer, concrete class)
           ↓ (accesses)
      Database

❌ WRONG FLOW (DO NOT DO THIS!):
UseCase → OutPort interface
           ↓ (ServiceProvider binds to)
      Repository ❌ WRONG! Should bind to OutAdapter!
```

## 📁 Naming & Structure

- [ ] Module folder: `app/Core/{Module}/`
- [ ] Domain: `Domain/{Entities|ValueObjects|Enums|Exceptions|Services|Specifications}/`
- [ ] Application: `Application/{UseCases|DTOs|Ports}/`
- [ ] Infrastructure: `Infrastructure/Adapters/{In|Out}/`
- [ ] All file names follow: `{Type}{Name}.php` pattern
- [ ] All classes use strict types: `declare(strict_types=1);`
- [ ] All properties/parameters/returns are typed

## 🚀 Final Validation

### Architecture Boundaries
- [ ] Domain layer: ZERO Laravel imports (`Illuminate\*`)
- [ ] Application layer: ZERO Laravel imports (`Illuminate\*`)
- [ ] Infrastructure layer: Laravel imports ONLY here
- [ ] Dependency flow: Infrastructure → Application → Domain (never reverse)

### Code Quality
- [ ] NO TODOs or placeholders
- [ ] NO commented code
- [ ] PHPDoc on all public methods
- [ ] All code is production-ready
- [ ] Follows PSR-12 coding standards

### Documentation References
Before completing, verify against these documents:
- [ ] ARCHITECTURAL_RULES.md - Repository vs OutAdapter separation
- [ ] INFRASTRUCTURE_REPOSITORY_EXAMPLES.md - Repository pattern
- [ ] INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md - OutAdapter pattern
- [ ] APPLICATION_USECASE_EXAMPLES.md - UseCase patterns
- [ ] APPLICATION_OUTDTO_EXAMPLES.md - DTO collection patterns
- [ ] SERVICE_CONTAINER_REGISTRATION.md - Binding rules
- [ ] ENTITY_VS_DTO_DECISION_GUIDE.md - Entity vs DTO decision tree
- [ ] INADAPTER_MANDATORY_CHECKLIST.md - InAdapter complete validation

---

## 🚨 Critical Anti-Patterns to Avoid

### ❌ Repository Anti-Patterns
- Repository implementing OutPort interface
- Repository creating Domain Entities
- Repository containing business logic

### ❌ OutAdapter Anti-Patterns
- OutAdapter accessing database directly (without Repository)
- Missing OutAdapter layer (UseCase → Repository directly)
- Using `private readonly` in constructor

### ❌ UseCase Anti-Patterns
- Using English verbs (Get, Create, Update, Delete)
- Implementing InPort interface for simple CRUD
- Returning DTOs instead of arrays
- Catching exceptions (should throw)
- Depending on Repository directly (must use OutPort)
- Using `private readonly` in constructor

### ❌ OutDTO Anti-Patterns
- Including `$success`, `$message`, `$status` properties
- Using generic `$data` property name
- Not using nested DTOs for collections

### ❌ ServiceProvider Anti-Patterns
- Binding OutPort to Repository (must bind to OutAdapter!)
- Skipping OutAdapter layer completely

---

**Remember**: This checklist prevents the 12 most common architectural mistakes. Review it before delivering ANY implementation!