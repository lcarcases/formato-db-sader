<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\AmbienteDesarrolloRepository;

/**
 * Out Adapter for Ambiente de Desarrollo (PostgreSQL implementation)
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
final readonly class AmbienteDesarrolloOutAdapter implements AmbienteDesarrolloOutPort
{
    public function __construct(
        private AmbienteDesarrolloRepository $ambienteDesarrolloRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function obtenerAmbientesDesarrollo(): array
    {
        $rawData = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        return array_map(
            fn (AmbienteDesarrolloModel $model): AmbienteVO => new AmbienteVO(
                id: $model->id_nu_ambiente_desarrollo,
                nombre: $model->sn_nombre,
            ),
            $rawData
        );
    }
}
