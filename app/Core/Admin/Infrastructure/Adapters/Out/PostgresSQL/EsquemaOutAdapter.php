<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\EsquemaRepository;

/**
 * Out Adapter for Esquema (PostgreSQL implementation)
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
final readonly class EsquemaOutAdapter implements EsquemaOutPort
{
    public function __construct(
        private EsquemaRepository $esquemaRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function obtenerEsquemas(): array
    {
        return array_map(
            fn (EsquemaModel $model): EsquemaVO => new EsquemaVO(
                id: $model->id_nu_esquema,
                nombre: $model->sn_nombre,
            ),
            $this->esquemaRepository->obtenerEsquemas()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerEsquemasPorHostname(int $idHostname): ?array
    {
        $rawData = $this->esquemaRepository->obtenerEsquemasPorHostname($idHostname);

        if ($rawData === null) {
            return null;
        }

        return array_map(
            fn (EsquemaModel $model): EsquemaVO => new EsquemaVO(
                id: $model->id_nu_esquema,
                nombre: $model->sn_nombre,
            ),
            $rawData
        );
    }
}
