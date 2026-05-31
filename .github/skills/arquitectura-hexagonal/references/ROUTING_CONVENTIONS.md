# Routing Conventions for Hexagonal Architecture

## 🚨 CRITICAL: Route Organization Rules (Non-Negotiable)

### 1. Route File Location

**✅ CORRECT**: Create module-specific route files inside Infrastructure

```
app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php
```

**❌ WRONG**: Do NOT define routes in Laravel's default route files

```
❌ routes/web.php      - NEVER place module routes here
❌ routes/api.php      - NEVER place module routes here
❌ routes/console.php  - NEVER place module routes here
```

**Example Structure:**
```
app/Core/Admin/Infrastructure/
├── Adapters/
│   └── In/
│       └── Api/
│           └── ObtenerTiposRequerimientosInAdapter.php
├── Providers/
│   └── AdminServiceProvider.php
└── Routes/
    └── AdminApiRoutes.php    # ✅ Module routes defined here
```

### 2. API Versioning Pattern

**✅ ALWAYS use versioned API prefix:**

```php
Route::prefix('api/v1/{module}')->group(function () {
    // Routes here
});
```

**Pattern Breakdown:**
- `api` - Base API namespace
- `v1` - API version (supports future v2, v3, etc.)
- `{module}` - Module name in lowercase (admin, programa, beneficiario, etc.)

**Examples:**
```php
// ✅ CORRECT - Admin module
Route::prefix('api/v1/admin')->group(function () {
    Route::get('/tipos-requerimientos', ObtenerTiposRequerimientosInAdapter::class)
        ->name('api.admin.tipos-requerimientos.index');
});

// ✅ CORRECT - Programa module
Route::prefix('api/v1/programa')->group(function () {
    Route::post('/solicitudes', CrearSolicitudInAdapter::class)
        ->name('api.programa.solicitudes.store');
});

// ❌ WRONG - Missing version
Route::prefix('api/admin')->group(function () { ... });

// ❌ WRONG - Missing module separation
Route::prefix('api/v1')->group(function () { ... });
```

### 3. Route Naming Convention

**Pattern:** `api.{module}.{resource}.{action}`

```php
// ✅ CORRECT
->name('api.admin.tipos-requerimientos.index')
->name('api.programa.solicitudes.store')
->name('api.beneficiario.documentos.update')
->name('api.tramite.solicitudes.destroy')

// ❌ WRONG - Missing 'api' prefix
->name('admin.tipos-requerimientos.index')

// ❌ WRONG - Not following pattern
->name('tipos-requerimientos')
```

**Action Suffixes:**
- `index` - List all/filter
- `show` - Get single item
- `store` - Create new
- `update` - Update existing
- `destroy` - Delete existing

### 4. Complete Route File Example

```php
// filepath: app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php
<?php

use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposRequerimientosInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTipoRequerimientoPorIdInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\CrearTipoRequerimientoInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ActualizarTipoRequerimientoInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\EliminarTipoRequerimientoInAdapter;
use Illuminate\Support\Facades\Route;

/**
 * Admin Module API Routes (Version 1)
 * 
 * Base URL: /api/v1/admin
 * 
 * These routes handle all administrative functionality including
 * catalog management, configuration, and system administration.
 */

Route::prefix('api/v1/admin')->group(function () {
    
    // Tipos de Requerimientos
    Route::get('/tipos-requerimientos', ObtenerTiposRequerimientosInAdapter::class)
        ->name('api.admin.tipos-requerimientos.index');
    
    Route::get('/tipos-requerimientos/{id}', ObtenerTipoRequerimientoPorIdInAdapter::class)
        ->name('api.admin.tipos-requerimientos.show');
    
    Route::post('/tipos-requerimientos', CrearTipoRequerimientoInAdapter::class)
        ->name('api.admin.tipos-requerimientos.store');
    
    Route::put('/tipos-requerimientos/{id}', ActualizarTipoRequerimientoInAdapter::class)
        ->name('api.admin.tipos-requerimientos.update');
    
    Route::delete('/tipos-requerimientos/{id}', EliminarTipoRequerimientoInAdapter::class)
        ->name('api.admin.tipos-requerimientos.destroy');
    
    // ... other admin routes
});
```

### 5. Route Registration in ServiceProvider

**Register module routes in the module's ServiceProvider:**

```php
// filepath: app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php
<?php

namespace App\Core\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/AdminApiRoutes.php');
        
        // Register migrations, views, etc.
        // ...
    }
}
```

**Register ServiceProvider in bootstrap/providers.php:**

```php
// filepath: bootstrap/providers.php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Core\Admin\Infrastructure\Providers\AdminServiceProvider::class,  // ✅ Add here
    // ... other providers
];
```

### 6. Middleware Configuration (Optional)

**Apply middleware to route groups:**

```php
Route::prefix('api/v1/admin')
    ->middleware(['api', 'auth:sanctum'])  // Add middleware as needed
    ->group(function () {
        // Protected routes
    });
```

### 7. Route Organization Best Practices

#### Group Related Routes
```php
// ✅ CORRECT - Group by resource
Route::prefix('api/v1/admin')->group(function () {
    
    // Tipos Requerimientos resource
    Route::controller(ObtenerTiposRequerimientosInAdapter::class)->group(function () {
        Route::get('/tipos-requerimientos', 'index');
        Route::get('/tipos-requerimientos/{id}', 'show');
    });
    
    // Documentos resource
    Route::controller(ObtenerDocumentosInAdapter::class)->group(function () {
        Route::get('/documentos', 'index');
        Route::get('/documentos/{id}', 'show');
    });
});
```

#### Use Route Parameters Consistently
```php
// ✅ CORRECT - Use descriptive parameter names
Route::get('/solicitudes/{solicitudId}', ...)
Route::get('/beneficiarios/{beneficiarioId}/documentos/{documentoId}', ...)

// ❌ WRONG - Generic parameter names
Route::get('/solicitudes/{id}', ...)
Route::get('/beneficiarios/{id}/documentos/{id}', ...)  // Conflict!
```

### 8. Testing Routes

**Verify route registration:**
```bash
php artisan route:list --path=api/v1/admin
```

**Expected output:**
```
GET|HEAD   api/v1/admin/tipos-requerimientos ......... api.admin.tipos-requerimientos.index
GET|HEAD   api/v1/admin/tipos-requerimientos/{id} ... api.admin.tipos-requerimientos.show
POST       api/v1/admin/tipos-requerimientos ......... api.admin.tipos-requerimientos.store
PUT|PATCH  api/v1/admin/tipos-requerimientos/{id} ... api.admin.tipos-requerimientos.update
DELETE     api/v1/admin/tipos-requerimientos/{id} ... api.admin.tipos-requerimientos.destroy
```

### 9. Route Checklist

Before finalizing route implementation, verify:

- [ ] ✅ Route file created in `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`
- [ ] ✅ Routes use `api/v1/{module}` prefix pattern
- [ ] ✅ Route names follow `api.{module}.{resource}.{action}` convention
- [ ] ✅ InAdapters are properly imported
- [ ] ✅ Routes are registered in module's ServiceProvider
- [ ] ✅ ServiceProvider is registered in `bootstrap/providers.php`
- [ ] ✅ Routes are NOT in `routes/web.php` or `routes/api.php`
- [ ] ✅ Middleware configured appropriately
- [ ] ✅ Route parameters use descriptive names
- [ ] ✅ Routes tested with `php artisan route:list`

### 10. Common Mistakes to Avoid

#### ❌ MISTAKE 1: Routes in Laravel's default files
```php
// filepath: routes/web.php
Route::get('/api/admin/tipos-requerimientos', ...);  // ❌ WRONG LOCATION
```

#### ❌ MISTAKE 2: Missing API version
```php
Route::prefix('api/admin')->group(function () {  // ❌ Missing /v1
    Route::get('/tipos-requerimientos', ...);
});
```

#### ❌ MISTAKE 3: Wrong file naming
```php
// app/Core/Admin/Infrastructure/Routes/api.php  // ❌ WRONG NAME
// ✅ CORRECT: AdminApiRoutes.php
```

#### ❌ MISTAKE 4: Not registering routes in ServiceProvider
```php
// ServiceProvider without loadRoutesFrom()  // ❌ Routes won't be loaded
```

## Summary

**Always follow this pattern:**

1. **Create**: `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`
2. **Use prefix**: `api/v1/{module}`
3. **Name routes**: `api.{module}.{resource}.{action}`
4. **Register**: In module's ServiceProvider with `loadRoutesFrom()`
5. **NEVER**: Place routes in `routes/web.php` or `routes/api.php`

This ensures proper separation of concerns, API versioning, and module organization aligned with Hexagonal Architecture principles.
