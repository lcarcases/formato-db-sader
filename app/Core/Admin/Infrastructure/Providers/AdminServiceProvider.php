<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Providers;

use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Application\Ports\Out\ITipoPermisoOutPort;
use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\AmbienteDesarrolloOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPermisoPostgresSQLRepository;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPersonalPostgresSQLRepository;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoRequerimientoPostgresSQLRepository;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoPermisoPostgresSQLOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoPersonalPostgresSQLOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\TipoRequerimientoPostgresSQLOutAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * AdminServiceProvider
 *
 * Registers dependency bindings for the Admin module.
 *
 * Pattern: Bind Application layer interfaces to Infrastructure layer implementations
 *
 * Bindings:
 * - OutPort interfaces → OutAdapter implementations
 * - Repositories registered as singletons for performance
 */
class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * Binds interfaces to their implementations in the service container.
     */
    public function register(): void
    {
        // ✅ CORRECT: Bind OutPort interface → OutAdapter implementation

        // AmbienteDesarrollo bindings
        $this->app->bind(
            AmbienteDesarrolloOutPort::class,
            AmbienteDesarrolloOutAdapter::class
        );

        // TipoRequerimiento bindings
        $this->app->bind(
            ITipoRequerimientoOutPort::class,
            TipoRequerimientoPostgresSQLOutAdapter::class
        );

        // TipoPersonal bindings
        $this->app->bind(
            ITipoPersonalOutPort::class,
            TipoPersonalPostgresSQLOutAdapter::class
        );

        // TipoPermiso bindings
        $this->app->bind(
            ITipoPermisoOutPort::class,
            TipoPermisoPostgresSQLOutAdapter::class
        );

        // Repository bindings (singletons for performance)
        $this->app->singleton(
            TipoPersonalPostgresSQLRepository::class,
            TipoPersonalPostgresSQLRepository::class
        );

        $this->app->singleton(
            TipoPermisoPostgresSQLRepository::class,
            TipoPermisoPostgresSQLRepository::class
        );

        $this->app->singleton(
            TipoRequerimientoPostgresSQLRepository::class,
            TipoRequerimientoPostgresSQLRepository::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // 🚨 CRITICAL: Load module routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/AdminApiRoutes.php');
    }
}
