<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\BaseDatosModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\BaseDatosRepository;

/**
 * Out Adapter for Base de Datos (PostgreSQL implementation)
 *
 * Implements the OutPort interface defined in Application layer.
 * Delegates actual data access to the Repository and maps raw data to Domain objects.
 *
 * Adapter Pattern:
 * - Application defines OutPort interface (what it needs)
 * - This adapter implements the interface (how to fulfill the need)
 * - Repository handles the low-level Eloquent interactions
 *
 * Dependency Inversion:
 * - This concrete implementation will be bound to the OutPort interface
 *   in the service provider for dependency injection
 */
final readonly class BaseDatosOutAdapter implements BaseDatosOutPort
{
    public function __construct(
        private BaseDatosRepository $baseDatosRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function obtenerBasesDatos(): array
    {
        $rawData = $this->baseDatosRepository->obtenerBasesDatos();

        return array_map(
            fn (BaseDatosModel $model): BaseDatosVO => new BaseDatosVO(
                id: $model->id_nu_base_datos,
                nombre: $model->sn_nombre,
            ),
            $rawData
        );
    }
}
