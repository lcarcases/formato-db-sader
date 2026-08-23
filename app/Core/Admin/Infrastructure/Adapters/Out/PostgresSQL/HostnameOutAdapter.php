<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Domain\ValueObjects\HostnameVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\HostnameRepository;

/**
 * Out Adapter for Hostname (PostgreSQL implementation)
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
final readonly class HostnameOutAdapter implements HostnameOutPort
{
    public function __construct(
        private HostnameRepository $hostnameRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function obtenerHostnames(): array
    {
        $rawData = $this->hostnameRepository->obtenerHostnames();

        return array_map(
            fn (HostnameModel $model): HostnameVO => new HostnameVO(
                id: $model->id_nu_hostname,
                nombre: $model->sn_nombre,
            ),
            $rawData
        );
    }
}
