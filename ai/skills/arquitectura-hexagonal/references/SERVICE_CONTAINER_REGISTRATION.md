## Step 10: Register Dependencies (Service Container)

## 🚨 CRITICAL Service Provider Bindings

### ✅ What to Bind:
1. **OutPort → OutAdapter** (NOT OutPort → Repository!)
2. **InPort → UseCase** (only if Decorator pattern is used)

### ❌ What NOT to Bind:
1. **OutPort → Repository** (❌ WRONG! Repository has no interface!)
2. **UseCase → Repository** (❌ WRONG! UseCase depends on OutPort, not Repository!)

---

## ✅ Correct Service Provider Pattern

Register interface bindings in Laravel's Service Container:

```php
// filepath: app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php
<?php

namespace App\Core\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Application Ports
use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;

// Infrastructure OutAdapters
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoRequerimientoPostgresSQLOutAdapter;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ✅ CORRECT: Bind OutPort to OutAdapter (NOT Repository!)
        $this->app->bind(
            ITipoRequerimientoOutPort::class,
            TipoRequerimientoPostgresSQLOutAdapter::class  // ✅ OutAdapter!
        );
    }

    public function boot(): void
    {
        //
    }
}
```

---

## ❌ Common Mistake: Binding Repository Directly

```php
// ❌ WRONG: Binding OutPort to Repository
$this->app->bind(
    ITipoRequerimientoOutPort::class,
    TipoRequerimientoPostgresSQLRepository::class  // ❌ Repository should NOT implement interface!
);
```

**Why this is wrong:**
1. ❌ Repositories don't implement OutPort interfaces
2. ❌ Violates separation of concerns
3. ❌ Skips the OutAdapter layer completely
4. ❌ Reduces flexibility and testability

---

## Complete Service Provider Example

```php
// filepath: app/Core/Programa/Infrastructure/Providers/ProgramaServiceProvider.php
<?php

namespace App\Core\Programa\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// OutPort Interfaces
use App\Core\Programa\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Programa\Application\Ports\Out\IPersonaOutPort;
use App\Core\Programa\Application\Ports\Out\IProgramaOutPort;

// OutAdapter Implementations
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\SolicitudMySQLOutAdapter;
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\PersonaMySQLOutAdapter;
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\ProgramaMySQLOutAdapter;

// InPort Interfaces (if using Decorator pattern)
use App\Core\Programa\Application\Ports\In\IGenerarSolicitudInPort;

// UseCase Implementations
use App\Core\Programa\Application\UseCases\GenerarSolicitudUseCase;

final class ProgramaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ✅ Bind OutPorts to OutAdapters
        $this->app->bind(
            ISolicitudOutPort::class,
            SolicitudMySQLOutAdapter::class  // ✅ OutAdapter (NOT Repository!)
        );
        
        $this->app->bind(
            IPersonaOutPort::class,
            PersonaMySQLOutAdapter::class  // ✅ OutAdapter
        );
        
        $this->app->bind(
            IProgramaOutPort::class,
            ProgramaMySQLOutAdapter::class  // ✅ OutAdapter
        );
        
        // ✅ Bind InPorts to UseCases (only if using Decorator pattern)
        $this->app->bind(
            IGenerarSolicitudInPort::class,
            GenerarSolicitudUseCase::class
        );
    }

    public function boot(): void
    {
        // Load routes, migrations, etc.
    }
}
```

---

## Dependency Injection Flow

```
┌─────────────────────────────────────────────────────────┐
│            ServiceProvider (Binding)                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ✅ $this->app->bind(                                   │
│      ITipoRequerimientoOutPort::class,                  │
│      TipoRequerimientoPostgresSQLOutAdapter::class       │
│  );                                                     │
│                                                         │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼ When UseCase requests ITipoRequerimientoOutPort
┌─────────────────────────────────────────────────────────┐
│              UseCase (Receives injection)                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  public function __construct(                           │
│      ITipoRequerimientoOutPort $tipoRequerimientoOutPort│
│  ) {                                                    │
│      $this->tipoRequerimientoOutPort =                  │
│          $tipoRequerimientoOutPort;                     │
│  }                                                      │
│                                                         │
│  // ✅ Receives TipoRequerimientoPostgresSQLOutAdapter   │
│  //    instance (NOT Repository!)                       │
└─────────────────────────────────────────────────────────┘
```

---

## When to Register InPort Bindings

**ONLY register InPort → UseCase bindings when:**
- Using Decorator pattern
- Multiple implementations of same UseCase
- Need to swap implementations dynamically
- Using cross-cutting concerns (logging, caching, transactions)

### Example WITH Decorator Pattern

```php
// InPort interface
interface ICrearSolicitudInPort
{
    public function ejecutar(CrearSolicitudInDto $dto): array;
}

// Base UseCase
final class CrearSolicitudUseCase implements ICrearSolicitudInPort
{
    public function ejecutar(CrearSolicitudInDto $dto): array
    {
        // Implementation
    }
}

// Decorator with transaction
final class CrearSolicitudConTransaccionDecorator implements ICrearSolicitudInPort
{
    public function __construct(
        private ICrearSolicitudInPort $useCase
    ) {}
    
    public function ejecutar(CrearSolicitudInDto $dto): array
    {
        DB::beginTransaction();
        try {
            $result = $this->useCase->ejecutar($dto);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

// ServiceProvider
$this->app->bind(
    ICrearSolicitudInPort::class,
    function ($app) {
        $baseUseCase = new CrearSolicitudUseCase(
            $app->make(ISolicitudOutPort::class)
        );
        return new CrearSolicitudConTransaccionDecorator($baseUseCase);
    }
);
```

### Example WITHOUT Decorator Pattern (Simple CRUD)

```php
// ✅ NO InPort interface needed
final class ObtenerTiposRequerimientosUseCase
{
    // Just a simple UseCase, no interface
}

// ✅ NO binding in ServiceProvider needed
// InAdapter calls UseCase directly via app()->make()
```

---

## 🚨 CRITICAL: Register Routes (MANDATORY)

**❌ WRONG - Do NOT register routes in Laravel's default route files:**
```php
// filepath: routes/api.php or routes/web.php - ❌ WRONG LOCATION
Route::get('/tipos-requerimientos', ...);  // ❌ NEVER DO THIS
```

**✅ CORRECT - Create module-specific route file:**

### Step 1: Create Route File
```php
// filepath: app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php
<?php

use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposRequerimientosInAdapter;
use Illuminate\Support\Facades\Route;

/**
 * Admin Module API Routes (Version 1)
 * Base URL: /api/v1/admin
 */

Route::prefix('api/v1/admin')->group(function () {
    Route::get('/tipos-requerimientos', ObtenerTiposRequerimientosInAdapter::class)
        ->name('api.admin.tipos-requerimientos.index');
});
```

**Key Points:**
- ✅ File location: `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`
- ✅ Prefix pattern: `api/v1/{module}` (ALWAYS include v1 for versioning)
- ✅ Name pattern: `api.{module}.{resource}.{action}`
- ❌ NEVER use: `routes/web.php`, `routes/api.php`, or `routes/console.php`

### Step 2: Register Routes in ServiceProvider
```php
// filepath: app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php
<?php

namespace App\Core\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings
        $this->app->bind(
            I TipoRequerimientoOutPort::class,
            TipoRequerimientoMySQLOutAdapter::class
        );
    }

    public function boot(): void
    {
        // 🚨 CRITICAL: Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/AdminApiRoutes.php');
        
        // Load migrations, views, etc. (if applicable)
        // $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
```

**Complete routing documentation:** [ROUTING_CONVENTIONS.md](ROUTING_CONVENTIONS.md)

---

## Register ServiceProvider

**Laravel 11+ (bootstrap/providers.php):**
```php
// filepath: bootstrap/providers.php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Core\Admin\Infrastructure\Providers\AdminServiceProvider::class,
    App\Core\Programa\Infrastructure\Providers\ProgramaServiceProvider::class,
];
```

**Laravel 10 and below (config/app.php):**
```php
// filepath: config/app.php
'providers' => [
    // ...
    App\Core\Admin\Infrastructure\Providers\AdminServiceProvider::class,
    App\Core\Programa\Infrastructure\Providers\ProgramaServiceProvider::class,
],
```
