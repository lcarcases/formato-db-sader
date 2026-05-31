# Quickstart: Implementar Catálogo Tipos de Personal

## Overview

Implementación de endpoint GET `/api/v1/admin/tipos-personal` siguiendo arquitectura hexagonal y DDD.

**Tiempo estimado**: 3-4 horas (incluyendo tests)

---

## Prerequisites

- PHP 8.4+, Composer 2.x
- Docker + Docker Compose (PostgreSQL 16, Redis 7.4)
- Git branch `001-catalogo-tipos-personal` checked out
- Read `specs/001-catalogo-tipos-personal/spec.md` and `plan.md`

---

## Step 1: Database Setup (30 min)

### 1.1 Create Schema Migration

```bash
php artisan make:migration create_tb_cat_tipo_personal_table
```

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_create_tb_cat_tipo_personal_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_cat_tipo_personal', function (Blueprint $table) {
            $table->id('id_nu_tipo_personal');
            $table->string('sn_nombre', 50)->unique();
            $table->text('sn_descripcion')->nullable();
            $table->boolean('ind_activo')->default(true);
            $table->timestamps();
            
            $table->index('activo', 'idx_tb_cat_tipo_personal_activo');
            $table->index('nombre', 'idx_tb_cat_tipo_personal_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_cat_tipo_personal');
    }
};
```

### 1.2 Create Data Migration

```bash
php artisan make:migration seed_tb_cat_tipo_personal_table
```

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_seed_tb_cat_tipo_personal_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Base', 'sn_descripcion' => 'Personal de base', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Enlace', 'sn_descripcion' => 'Personal de enlace', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Confianza', 'sn_descripcion' => 'Personal de confianza', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Externo', 'sn_descripcion' => 'Personal externo', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('tb_cat_tipo_personal')->truncate();
    }
};
```

### 1.3 Run Migrations

```bash
php artisan migrate
```

**Verify**:
```bash
php artisan tinker
>>> DB::table('tb_cat_tipo_personal')->count();
=> 4
```

---

## Step 2: Domain Layer (45 min)

### 2.1 Create Entity

**File**: `app/Core/Admin/Domain/Entities/TipoPersonal.php`

**IMPORTANT**: Use `@Hexagonal Architecture Specialist` agent for implementation:
```
@Hexagonal Architecture Specialist implement TipoPersonal domain entity with fields: id (int), nombre (string), descripcion (?string), activo (bool). Validate nombre is non-empty. Make entity readonly.
```

**Manual Implementation** (if agent unavailable):
```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Domain\Entities;

final readonly class TipoPersonal
{
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $activo
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (trim($this->nombre) === '') {
            throw new \InvalidArgumentException('TipoPersonal nombre cannot be empty');
        }
    }

    public function isActive(): bool
    {
        return $this->activo;
    }
}
```

### 2.2 Create Domain Exception

**File**: `app/Core/Admin/Domain/Exceptions/TipoPersonalNotFoundException.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Domain\Exceptions;

final class TipoPersonalNotFoundException extends \DomainException
{
    public static function create(): self
    {
        return new self('No active tipos de personal found');
    }
}
```

---

## Step 3: Application Layer (60 min)

> **Implementation Note**: This implementation uses a simplified approach where the use case returns raw arrays instead of DTO wrapper objects. The `Respuesta` class in the InAdapter handles response formatting, eliminating the need for `ObtenerTiposPersonalOutDto` wrapper. The `TipoPersonalItemOutDto` could be used for type safety but is optional for simple catalog reads.

### 3.1 Create DTOs (Optional for this implementation)

**Note**: The quickstart implementation below (Step 3.3) returns raw arrays directly. These DTO classes are shown for reference but not required.

**File**: `app/Core/Admin/Application/DTOs/Out/TipoPersonalItemOutDto.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * Item DTO for individual Tipo Personal (OPTIONAL)
 * Could be used for type safety, but implementation uses raw arrays
 */
final readonly class TipoPersonalItemOutDto
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre
        ];
    }

    public static function fromStdClass(\stdClass $data): self
    {
        return new self(
            id: $data->id,
            nombre: $data->nombre
        );
    }
}
```

**File**: `app/Core/Admin/Application/DTOs/Out/ObtenerTiposPersonalOutDto.php` **(Optional - Not used in simplified implementation)**

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * Output DTO for ObtenerTiposPersonalUseCase (OPTIONAL)
 * This wrapper is NOT used in the simplified implementation
 * Implementation returns raw arrays directly
 * 
 * NAMING: Verb-prefixed (Obtener) + Concept + OutDto
 */
final readonly class ObtenerTiposPersonalOutDto
{
    /**
     * @param array<int, TipoPersonalItemOutDto> $items
     */
    public function __construct(
        public array $items
    ) {}

    public function toArray(): array
    {
        return array_map(fn($item) => $item->toArray(), $this->items);
    }
}
```

### 3.2 Create Ports

**File**: `app/Core/Admin/Application/Ports/Out/ITipoPersonalOutPort.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

interface ITipoPersonalOutPort
{
    /**
     * @return array<int, \stdClass>
     */
    public function obtenerTodos(): array;
}
```

### 3.3 Create Use Case

**File**: `app/Core/Admin/Application/UseCases/ObtenerTiposPersonalUseCase.php`

**IMPORTANT**: Use `@Hexagonal Architecture Specialist` agent:
```
@Hexagonal Architecture Specialist implement ObtenerTiposPersonalUseCase that depends on ITipoPersonalOutPort, calls obtenerTodos(), transforms stdClass objects to raw arrays with id and nombre fields, returns array directly
```

**Design Note**: This implementation returns raw arrays instead of DTO objects for simplicity. The Respuesta class in the InAdapter handles the final response formatting. **NAMING**: PLURAL "TiposPersonal" indicates collection return.

**Manual Implementation**:
```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;

/**
 * Use Case: Obtener catálogo de tipos de personal activos
 * 
 * Orchestrates retrieval of active tipos personal from persistence
 * and transforms to raw array format for API response
 * 
 * NOTE: PLURAL naming (TiposPersonal) indicates collection return
 * 
 * @return array Array of arrays with ['id' => int, 'nombre' => string]
 */
final readonly class ObtenerTiposPersonalUseCase
{
    public function __construct(
        private ITipoPersonalOutPort $tipoPersonalOutPort
    ) {}

    public function obtener(): array
    {
        // 1. Get data from repository via OutPort
        $data = $this->tipoPersonalOutPort->obtenerTodos();
        
        // 2. Transform stdClass to raw arrays
        $items = array_map(
            fn(\stdClass $row) => [
                'id' => $row->id,
                'nombre' => $row->nombre
            ],
            $data
        );

        // 3. Return raw array
        return $items;
    }
}
```

---

## Step 4: Infrastructure Layer (90 min)

### 4.1 Create Eloquent Model

**File**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/TipoPersonalEloquentModel.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models;

use Illuminate\Database\Eloquent\Model;

final class TipoPersonalEloquentModel extends Model
{
    protected $table = 'tb_cat_tipo_personal';
    protected $fillable = ['sn_nombre', 'sn_descripcion', 'ind_activo'];
    protected $casts = ['ind_activo' => 'boolean'];
}
```

### 4.2 Create Repository

**File**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoPersonalPostgresSQLRepository.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\TipoPersonalEloquentModel;

final readonly class TipoPersonalPostgresSQLRepository implements ITipoPersonalOutPort
{
    public function obtenerTodos(): array
    {
        return TipoPersonalEloquentModel::query()
            ->where('ind_activo', true)
            ->orderBy('id_nu_tipo_personal', 'asc')
            ->get(['id_nu_tipo_personal', 'sn_nombre'])
            ->map(fn($model) => (object)[
                'id' => $model->id_nu_tipo_personal,
                'nombre' => $model->sn_nombre
            ])
            ->all();
    }
}
```

### 4.3 Create OutAdapter

**File**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/TipoPersonalPostgresSQLOutAdapter.php`

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPersonalPostgresSQLRepository;

final readonly class TipoPersonalPostgresSQLOutAdapter implements ITipoPersonalOutPort
{
    public function __construct(
        private TipoPersonalPostgresSQLRepository $tipoPersonalPostgresSQLRepository
    ) {}

    public function obtenerTodos(): array
    {
        return $this->tipoPersonalPostgresSQLRepository->obtenerTodos();
    }
}
```

### 4.4 Create API InAdapter

**File**: `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerTiposPersonalInAdapter.php`

**Naming Note**: InAdapter uses PLURAL "TiposPersonal" to match the use case it invokes.

**CRITICAL PATTERNS (MANDATORY):**
- ✅ Spanish verb naming: `ObtenerTiposPersonalInAdapter` (NOT Controller suffix - PLURAL TiposPersonal)
- ✅ Use `app()->make()` in constructor (NOT dependency injection parameters)
- ✅ Use `Respuesta` class for response formatting
- ✅ Import from `Infrastructure` (with 'a')
- ✅ Declare private property before constructor
- ✅ Wrap all logic in try-catch

```php
<?php
declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\UseCases\ObtenerTiposPersonalUseCase;
use App\Core\Shared\Infrastructure\Respuesta; // Note: Infrastructure with 'a'
use Illuminate\Routing\Controller;

/**
 * InAdapter for obtaining tipos personal catalog
 * Entry point: HTTP GET request → Use Case → Response
 * 
 * PATTERN: Spanish verb (Obtener) + PLURAL concept (TiposPersonal) + InAdapter suffix (NOT Controller)
 */
final class ObtenerTiposPersonalInAdapter extends Controller
{
    // 1. Declare property as private (NOT readonly, NOT in constructor params)
    private ObtenerTiposPersonalUseCase $obtenerTiposPersonalUseCase;

    // 2. Use app()->make() pattern (NOT dependency injection)
    public function __construct()
    {
        try {
            $this->obtenerTiposPersonalUseCase = app()->make(ObtenerTiposPersonalUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function __invoke()
    {
        try {
            // 3. Create Respuesta instance FIRST
            $respuesta = new Respuesta();
            
            // 4. Execute use case
            $items = $this->obtenerTiposPersonalUseCase->obtener();
            
            // 5. Set up response
            $respuesta->setSuccess(true);
            $respuesta->setMessage(
                count($items) > 0 
                    ? 'Tipos de personal obtenidos exitosamente' 
                    : 'No hay tipos de personal activos'
            );
            $respuesta->setData($items);
            
            // 6. Return standardized response
            return $respuesta->successResponse();
            
        } catch (\Exception $ex) {
            // 7. Handle errors with Respuesta
            $respuesta = new Respuesta();
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error al obtener tipo de personal');
            return $respuesta->errorResponse($ex);
        }
    }
}
```

### 4.5 Register Route

**File**: `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php`

**CRITICAL: Route MUST follow these patterns:**
- ✅ Module-specific file (NOT routes/api.php)
- ✅ Versioned prefix: `api/v1/admin` (ALWAYS include /v1)
- ✅ Named route: `api.admin.tipos-personal.index`

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposPersonalInAdapter;

// Existing routes...

// Versioned API routes with proper naming
Route::prefix('api/v1/admin')->group(function () {
    Route::get('/tipos-personal', ObtenerTiposPersonalInAdapter::class)
        ->middleware('throttle:60,1') // 60 requests per minute per IP
        ->name('api.admin.tipos-personal.index');
});
```

### 4.6 Update Service Provider

**File**: `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`

**NOTE**: Use Case binding NOT needed when using `app()->make()` pattern in InAdapter

```php
<?php
namespace App\Core\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoPersonalPostgresSQLOutAdapter;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Existing bindings...

        // Bind OutPort to OutAdapter implementation
        $this->app->bind(
            ITipoPersonalOutPort::class,
            TipoPersonalPostgresSQLOutAdapter::class
        );
        
        // NOTE: Use Case binding NOT needed - InAdapter uses app()->make() directly
    }

    public function boot(): void
    {
        // Load module-specific routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/AdminApiRoutes.php');
    }
}
```

---

## Step 5: Testing (60 min)

### 5.1 Unit Test - Use Case

**File**: `tests/Unit/Core/Admin/Application/UseCases/ObtenerTipoPersonalUseCaseTest.php`

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Application\UseCases\ObtenerTipoPersonalUseCase;
use PHPUnit\Framework\TestCase;

final class ObtenerTipoPersonalUseCaseTest extends TestCase
{
    public function test_ejecutar_retorno_tipo_personal_exitosamente(): void
    {
        // Arrange
        $mockData = [
            (object)['id' => 1, 'nombre' => 'Base'],
            (object)['id' => 2, 'nombre' => 'Enlace']
        ];

        $mockOutPort = $this->createMock(ITipoPersonalOutPort::class);
        $mockOutPort->method('obtenerTodos')->willReturn($mockData);

        $useCase = new ObtenerTipoPersonalUseCase($mockOutPort);

        // Act
        $result = $useCase->obtener();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Base', $result[0]['nombre']);
    }

    public function test_ejecutar_retorno_array_vacio_cuando_no_haya_tipos_activos(): void
    {
        // Arrange
        $mockOutPort = $this->createMock(ITipoPersonalOutPort::class);
        $mockOutPort->method('obtenerTodos')->willReturn([]);

        $useCase = new ObtenerTipoPersonalUseCase($mockOutPort);

        // Act
        $result = $useCase->obtener();

        // Assert
        $this->assertCount(0, $result);
    }
}
```

**Run**:
```bash
./vendor/bin/phpunit tests/Unit/Core/Admin/Application/UseCases/ObtenerTipoPersonalUseCaseTest.php
```

### 5.2 Integration Test - Repository

**File**: `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoPersonalPostgresSQLRepositoryIntegrationTest.php`

```php
<?php
declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\TipoPersonalEloquentModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPersonalPostgresSQLRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TipoPersonalPostgresSQLRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_obtener_todos_tipos_activos_ordenados_por_id(): void
    {
        // Arrange
        TipoPersonalEloquentModel::create(['nombre' => 'Base', 'activo' => true]);
        TipoPersonalEloquentModel::create(['nombre' => 'Enlace', 'activo' => true]);
        TipoPersonalEloquentModel::create(['nombre' => 'Inactivo', 'activo' => false]);

        $repository = new TipoPersonalPostgresSQLRepository();

        // Act
        $result = $repository->obtenerTodos();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('Base', $result[0]->nombre);
        $this->assertEquals('Enlace', $result[1]->nombre);
    }
}
```

**Run**:
```bash
./vendor/bin/phpunit tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoPersonalPostgresSQLRepositoryIntegrationTest.php
```

### 5.3 Feature Test - API Contract

**File**: `tests/Feature/Api/Admin/ObtenerTipoPersonalApiTest.php`

**Tests InAdapter + Use Case + Repository integration**

```php
<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\TipoPersonalEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ObtenerTipoPersonalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_obtener_tipo_personal_retorna_200_con_formato_respuesta_estandard(): void
    {
        // Arrange
        TipoPersonalEloquentModel::create(['nombre' => 'Base', 'activo' => true]);
        TipoPersonalEloquentModel::create(['nombre' => 'Enlace', 'activo' => true]);
        TipoPersonalEloquentModel::create(['nombre' => 'Confianza', 'activo' => true]);
        TipoPersonalEloquentModel::create(['nombre' => 'Externo', 'activo' => true]);

        // Act
        $response = $this->getJson('/api/v1/admin/tipos-personal');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'code',
                'data' => [
                    '*' => ['id', 'nombre']
                ]
            ])
            ->assertJson([
                'success' => true,
                'code' => 200
            ])
            ->assertJsonCount(4, 'data');
    }

    public function test_obtener_tipo_personal_retorna_array_vacio_cuando_no_hay_activos(): void
    {
        // Arrange
        TipoPersonalEloquentModel::create(['nombre' => 'Inactivo', 'activo' => false]);

        // Act
        $response = $this->getJson('/api/v1/admin/tipos-personal');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'code' => 200,
                'data' => []
            ]);
    }

    public function test_obtener_tipo_personal_respeta_rate_limiting(): void
    {
        // Act: Make 61 requests rapidly
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/v1/admin/tipos-personal');
        }

        // Assert: 61st request should be rate limited
        $response->assertStatus(429);
    }
}
```

**Run all tests**:
```bash
./vendor/bin/phpunit
```

---

## Step 6: Verification (15 min)

### 6.1 Static Analysis

```bash
./vendor/bin/phpstan analyse app/Core/Admin
```

**Expected**: No errors (PHPStan level 9 compliant)

### 6.2 Code Formatting

```bash
./vendor/bin/pint --test
```

**If issues**:
```bash
./vendor/bin/pint
```

### 6.3 Manual API Test

```bash
curl -X GET http://localhost/api/v1/admin/tipos-personal
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Tipos de personal obtenidos exitosamente",
  "code": 200,
  "data": [
    {"id": 1, "nombre": "Base"},
    {"id": 2, "nombre": "Enlace"},
    {"id": 3, "nombre": "Confianza"},
    {"id": 4, "nombre": "Externo"}
  ]
}
```

---

## Common Issues & Troubleshooting

### Issue 1: "Class ITipoPersonalOutPort not found"

**Cause**: Service provider binding incorrect or not registered

**Fix**:
1. Verify `AdminServiceProvider` is in `bootstrap/providers.php`
2. Run `php artisan optimize:clear`

### Issue 2: Rate limiting not working

**Cause**: Middleware not applied to route

**Fix**: Ensure route has `->middleware('throttle:60,1')` in `AdminApiRoutes.php`

### Issue 3: Empty data array despite records in DB

**Cause**: Repository filtering inactive records

**Fix**: Check `ind_activo` column values in database:
```sql
SELECT * FROM tb_cat_tipo_personal;
```

---

## Definition of Done

**✅ Hexagonal Architecture Compliance:**
- [x] **Spanish naming conventions**: InAdapter named `ObtenerTiposPersonalInAdapter` (NOT Controller)
- [x] **Respuesta class pattern**: InAdapter uses `Respuesta` class for response formatting
- [x] **app()->make() constructor**: InAdapter uses `app()->make()` pattern (NOT dependency injection)
- [x] **Correct import**: Uses `Infrastructure` (with 'a') NOT `Infraestructure`
- [x] **Verb-prefixed DTOs**: `ObtenerTiposPersonalOutDto` and `TipoPersonalOutDto`
- [x] **Versioned routes**: Routes use `api/v1/admin` prefix in module-specific file
- [x] **Named routes**: Route named `api.admin.tipos-personal.index`

**✅ Implementation Checklist:**
- [x] Migrations created and run successfully
- [x] Domain entity implemented (TipoPersonal)
- [x] Use case implemented (ObtenerTiposPersonalUseCase)
- [x] Repository implemented (TipoPersonalPostgresSQLRepository)
- [x] InAdapter implemented (ObtenerTiposPersonalInAdapter - NOT Controller)
- [x] Route registered with rate limiting in AdminApiRoutes.php
- [x] Service provider bindings configured (OutPort → OutAdapter)
- [x] Unit tests passing (use case)
- [x] Integration tests passing (repository)
- [x] Feature tests passing (API contract)
- [x] PHPStan level 9 passing (no errors)
- [x] Laravel Pint formatting applied (PSR-12)
- [x] Manual API test successful
- [x] Response format matches project standard `{success, message, code, data}`
- [x] Rate limiting verified (429 on 61st request)
- [x] CORS headers present (Access-Control-Allow-Origin: *)

---

## Next Steps

After completing implementation:

1. **Update OpenAPI spec** (if not already done in Phase 1):
   ```yaml
   # Add endpoint definition to openapi.yaml
   ```

2. **Create Pull Request**:
   ```bash
   git add .
   git commit -m "feat(admin): implement tipos personal catalog endpoint"
   git push origin 001-catalogo-tipos-personal
   ```

3. **Request Code Review**: Ensure reviewer checks hexagonal architecture compliance, test coverage, domain isolation

4. **Deploy to Staging**: After PR approval, deploy and verify with staging database

---

## References

- **Spec**: `specs/001-catalogo-tipos-personal/spec.md`
- **Plan**: `specs/001-catalogo-tipos-personal/plan.md`
- **Constitution**: `.specify/memory/constitution.md`
- **Existing Pattern**: `app/Core/Admin/` (TipoRequerimiento implementation)
