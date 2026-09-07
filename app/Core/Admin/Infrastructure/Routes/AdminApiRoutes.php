<?php

use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerAmbientesInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerBasesDatosInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerEsquemasInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerEsquemasPorHostnameInAdapter;
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerHostnamesInAdapter;
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

    // Ambientes de Desarrollo
    Route::get('/ambientes-desarrollo', ObtenerAmbientesInAdapter::class)
        ->name('api.admin.ambientes-desarrollo.index');

    // Bases de Datos
    Route::get('/bases-datos', ObtenerBasesDatosInAdapter::class)
        ->name('api.admin.bases-datos.index');

    // Hostnames
    Route::get('/hostnames', ObtenerHostnamesInAdapter::class)
        ->name('api.admin.hostnames.index');

    // Esquemas por Hostname
    Route::get('/hostnames/{idHostname}/esquemas', ObtenerEsquemasPorHostnameInAdapter::class)
        ->middleware('throttle:60,1')
        ->name('api.admin.hostnames.esquemas.index');

    // Esquemas
    Route::get('/esquemas', ObtenerEsquemasInAdapter::class)
        ->middleware('throttle:60,1')
        ->name('api.admin.esquemas.index');

    // Add more admin routes here following the same pattern...

});
