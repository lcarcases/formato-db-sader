<?php

use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposPermisoInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposPersonalInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerTiposRequerimientosInAdapter;
use Illuminate\Support\Facades\Route;

/**
 * Admin Module API Routes (Version 1)
 *
 * Base URL: /api/v1/admin
 *
 * These routes handle all administrative functionality including
 * catalog management, configuration, and system administration.
 *
 * ✅ Following Hexagonal Architecture conventions:
 * - Module-specific route file (NOT in routes/web.php)
 * - API versioning with /v1 prefix
 * - Proper route naming: api.{module}.{resource}.{action}
 * - Rate limiting applied with throttle middleware (60 requests per minute per IP)
 */
Route::prefix('api/v1/admin')->group(function () {

    // Tipos de Requerimientos
    Route::get('/tipos-requerimientos', ObtenerTiposRequerimientosInAdapter::class)
        ->name('api.admin.tipos-requerimientos.index');

    // Tipos de Personal
    Route::get('/tipos-personal', ObtenerTiposPersonalInAdapter::class)
        ->middleware('throttle:60,1')
        ->name('api.admin.tipos-personal.index');

    // Tipos de Permiso
    Route::get('/tipos-permiso', ObtenerTiposPermisoInAdapter::class)
        ->middleware('throttle:60,1')
        ->name('api.admin.tipos-permiso.index');

    // Add more admin routes here following the same pattern...

});
