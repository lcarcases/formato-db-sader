# Quick Start Guide: Obtener Catálogo de Tipos de Permiso

**Feature**: `002-catalogo-tipos-permiso`  
**Branch**: `002-catalogo-tipos-permiso`  
**Date**: 2026-05-31

## Overview

This guide provides step-by-step instructions for implementing the "Obtener Catálogo de Tipos de Permiso" feature following hexagonal architecture principles.

**What you'll build**:
- REST endpoint: `GET /api/v1/admin/tipos-permiso`
- Returns 4 permission types: Consulta, Cambios, Eliminación, Consulta y Cambios
- Hexagonal architecture with Domain/Application/Infrastructure layers
- Database migrations for PostgreSQL with prefixed column names
- Complete test coverage (unit, integration, contract)

---

## Prerequisites

- PHP 8.4+ installed
- Laravel 13.x project set up
- PostgreSQL 16.x running
- Familiarity with hexagonal architecture concepts
- Access to `@Hexagonal Architecture Specialist` agent (MANDATORY per constitution)

---

## Implementation Steps

### Step 1: Use the Hexagonal Architecture Specialist Agent

**⚠️ MANDATORY**: Per project constitution v1.1.0, all use case implementations MUST use the specialized agent.

```bash
@Hexagonal Architecture Specialist implement caso de uso para obtener el catálogo de tipos de permiso de base de datos siguiendo el patrón de TipoPersonal en el bounded context Admin
```

**The agent will**:
- Generate all domain, application, and infrastructure artifacts
- Ensure proper layer separation and dependency direction
- Follow established naming conventions
- Create tests with proper coverage

**Manual implementation is prohibited** to ensure architectural consistency.

---

### Step 2: Review Generated Artifacts

After the agent completes, verify the following files were created:

**Domain Layer** (`app/Core/Admin/Domain/`):
- ✅ `Entities/TipoPermiso.php` - Pure PHP entity with validation
- ✅ `Exceptions/TipoPermisoNotFoundException.php` - Domain exception

**Application Layer** (`app/Core/Admin/Application/`):
- ✅ `DTOs/Out/TipoPermisoOutDto.php` - Single item DTO
- ✅ `DTOs/Out/ObtenerTiposPermisoOutDto.php` - Collection wrapper
- ✅ `Ports/Out/ITipoPermisoOutPort.php` - Repository interface
- ✅ `UseCases/ObtenerTiposPermisoUseCase.php` - Main use case

**Infrastructure Layer** (`app/Core/Admin/Infrastructure/`):
- ✅ `Adapters/In/Api/ObtenerTiposPermisoInAdapter.php` - Controller
- ✅ `Adapters/Out/PostgresSQL/Models/TipoPermisoEloquentModel.php` - Eloquent model
- ✅ `Adapters/Out/PostgresSQL/Repositories/TipoPermisoPostgresSQLRepository.php` - Repository
- ✅ `Adapters/Out/PostgresSQL/TipoPermisoPostgresSQLOutAdapter.php` - OutAdapter

**Database** (`database/migrations/`):
- ✅ `2026_05_31_000001_create_tb_cat_tipo_permiso_table.php` - Schema migration
- ✅ `2026_05_31_000002_seed_tb_cat_tipo_permiso_table.php` - Seed data migration

**Tests** (`tests/`):
- ✅ `Unit/Core/ Admin/Domain/Entities/TipoPermisoTest.php`
- ✅ `Unit/Core/Admin/Application/UseCases/ObtenerTiposPermisoUseCaseTest.php`
- ✅ `Integration/Core/Admin/Infrastructure/.../TipoPermisoPostgresSQLRepositoryIntegrationTest.php`
- ✅ `Feature/Core/Admin/Api/ObtenerTiposPermisoApiTest.php`

---

### Step 3: Update Service Provider

Add the binding for TipoPermiso to `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`:

```php
public function register(): void
{
    // ... existing bindings ...
    
    // TipoPermiso bindings
    $this->app->bind(
        \App\Core\Admin\Application\Ports\Out\ITipoPermisoOutPort::class,
        \App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoPermisoPostgresSQLOutAdapter::class
    );
}
```

---

### Step 4: Register Route

Add the route to `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php`:

```php
Route::prefix('api/v1/admin')->group(function () {
    
    // Existing routes...
    
    // Tipos de Permiso
    Route::get('/tipos-permiso', \App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposPermisoInAdapter::class)
        ->middleware('throttle:60,1')
        ->name('api.admin.tipos-permiso.index');
        
});
```

---

### Step 5: Run Migrations

```bash
# Run migrations to create table and seed data
php artisan migrate

# Verify table was created
php artisan db:table tb_cat_tipo_permiso

# Verify seed data
psql -d sader_db -c "SELECT * FROM tb_cat_tipo_permiso ORDER BY id_nu_tipo_permiso;"
```

**Expected output**:
```
 id_nu_tipo_permiso |      ln_nombre       | ind_activo
--------------------+---------------------+------------
                  1 | Consulta            | t
                  2 | Cambios             | t
                  3 | Eliminación         | t
                  4 | Consulta y Cambios  | t
```

---

### Step 6: Test the Endpoint

```bash
# Test successful response
curl -X GET http://localhost/api/v1/admin/tipos-permiso | jq

# Expected response:
# {
#   "success": true,
#   "message": "Tipos de permiso obtenidos exitosamente.",
#   "code": 200,
#   "data": {
#     "tiposPermiso": [
#       {"id": 1, "nombre": "Consulta"},
#       {"id": 2, "nombre": "Cambios"},
#       {"id": 3, "nombre": "Eliminación"},
#       {"id": 4, "nombre": "Consulta y Cambios"}
#     ]
#   }
# }

# Test rate limiting (run 61 times rapidly)
for i in {1..61}; do
  curl -s -o /dev/null -w "%{http_code}\n" http://localhost/api/v1/admin/tipos-permiso
done
# First 60 should return 200, 61st should return 429
```

---

### Step 7: Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit --filter=TipoPermiso
php artisan test --testsuite=Integration --filter=TipoPermiso
php artisan test --testsuite=Feature --filter=ObtenerTiposPermisoApi

# Run with coverage
php artisan test --coverage --min=80
```

**Success Criteria**:
- ✅ All tests pass
- ✅ Code coverage ≥ 80% in application layer
- ✅ PHPStan level 9 passes: `vendor/bin/phpstan analyse`
- ✅ PSR-12 compliance: `vendor/bin/pint --test`

---

### Step 8: Verify Architectural Compliance

Run the constitutional checks:

```bash
# Check for Laravel dependencies in domain layer
grep -r "Illuminate" app/Core/Admin/Domain/
# Should return: No matches found

# Check dependency direction (no reverse dependencies)
vendor/bin/deptrac analyze

# Verify all OutPorts are interfaces
find app/Core/Admin/Application/Ports/Out -name "*.php" -exec grep -L "interface" {} \;
# Should return: empty

# Verify InAdapters use app()->make() pattern
grep -r "app()->make" app/Core/Admin/Infrastructure/Adapters/In/
# Should find: ObtenerTiposPermisoInAdapter.php
```

---

##Key Architecture Patterns

### InAdapter Pattern (Controller)

```php
// ✅ CORRECT: 6 Mandatory Rules
final class ObtenerTiposPermisoInAdapter
{
    private ObtenerTiposPermisoUseCase $useCase;

    public function __construct()
    {
        $this->useCase = app()->make(ObtenerTiposPermisoUseCase::class);
    }

    public function __invoke()
    {
        $respuesta = new Respuesta();
        
        try {
            $rawData = $this->useCase->ejecutar();
            $dto = ObtenerTiposPermisoOutDto::fromArray($rawData);
            
            $respuesta->setSuccess(true);
            $respuesta->setMessage('Tipos de permiso obtenidos exitosamente.');
            $respuesta->setData($dto);
            
            return $respuesta->successResponse();
        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error al obtener los tipos de permiso.');
            
            return $respuesta->errorResponse($ex);
        }
    }
}
```

### UseCase Pattern

```php
// ✅ CORRECT: Returns raw array, depends on interface
final class ObtenerTiposPermisoUseCase
{
    private ITipoPermisoOutPort $outPort;

    public function __construct(ITipoPermisoOutPort $outPort)
    {
        $this->outPort = $outPort;
    }

    public function ejecutar(): array
    {
        // No try-catch - let exceptions propagate to InAdapter
        return $this->outPort->obtenerTodos();
    }
}
```

### DTO Mapping Pattern

```php
// ✅ CORRECT: Maps DB columns to domain properties
final readonly class TipoPermisoOutDto
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {}

    public static function fromStdClass(\stdClass $data): self
    {
        return new self(
            id: $data->id_nu_tipo_permiso,    // DB column name
            nombre: $data->ln_nombre           // DB column name
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,       // Clean domain name
            'nombre' => $this->nombre // Clean domain name
        ];
    }
}
```

---

## Troubleshooting

### Issue: "Class ITipoPermisoOutPort not found"
**Solution**: Verify binding in `AdminServiceProvider::register()` and run `composer dump-autoload`

### Issue: Migration fails with "column already exists"
**Solution**: Run `php artisan migrate:rollback` then `php artisan migrate` again

### Issue: Route returns 404
**Solution**: 
1. Check route is registered in `AdminApiRoutes.php`
2. Verify route file is loaded in `routes/api.php` or `bootstrap/app.php`
3. Clear route cache: `php artisan route:clear`

### Issue: Tests fail with "Database connection refused"
**Solution**: Verify PostgreSQL is running and `.env.testing` credentials are correct

### Issue: Rate limit not working
**Solution**: Verify `throttle:60,1` middleware is applied to route

### Issue: PHPStan level 9 fails
**Solution**: 
1. Check all properties have type hints
2. Verify `declare(strict_types=1)` is present
3. Run `vendor/bin/phpstan analyse --level 9` for details

---

## Next Steps

After completing implementation and testing:

1. ✅ Commit changes to feature branch `002-catalogo-tipos-permiso`
2. ✅ Update `.github/copilot-instructions.md` to reference this feature's plan
3. ✅ Create pull request to `main` branch
4. ✅ Request code review focusing on architectural compliance
5. ✅ Verify CI/CD pipeline passes (tests, PHPStan, Pint)
6. ✅ Document any learnings or deviations for future reference

---

## Related Documentation

- [Feature Specification](spec.md) - Complete requirements and acceptance criteria
- [Implementation Plan](plan.md) - Overall planning document
- [Research](research.md) - Technical decisions and pattern analysis
- [Data Model](data-model.md) - Entity definitions and database schema
- [API Contract](contracts/api-tipos-permiso.yaml) - OpenAPI specification
- [Project Constitution](/.specify/memory/constitution.md) - Architectural principles
- [Hexagonal Architecture Skill](/.github/skills/arquitectura-hexagonal/SKILL.md) - Agent usage guide

---

## Success Checklist

Before considering the feature complete, verify:

- [ ] All files generated by Hexagonal Architecture Specialist agent
- [ ] Service provider bindings added
- [ ] Route registered with rate limiting middleware
- [ ] Migrations executed successfully
- [ ] Seed data verified in database
- [ ] Endpoint returns correct response format
- [ ] Rate limiting works (429 on 61st request)
- [ ] All tests pass (unit, integration, contract)
- [ ] PHPStan level 9 passes with zero errors
- [ ] PSR-12 (Pint) compliance verified
- [ ] No Laravel dependencies in domain layer
- [ ] Dependency direction verified (Infrastructure → Application → Domain)
- [ ] Code coverage ≥ 80% in application layer
- [ ] API contract matches OpenAPI specification
- [ ] Structured logging implemented (request_id, action, result, ip, duration_ms)
- [ ] Documentation updated (this guide, plan.md, README if applicable)

---

**Estimated Time**: 30-45 minutes (with agent assistance)  
**Complexity**: Low (replicates existing TipoPersonal pattern)  
**Prerequisites Met**: ✅ Research complete, pattern validated, constitution verified
