<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Domain\ValueObjects\HostnameVO;

/**
 * Use Case: Obtener Catálogo de Hostnames
 *
 * Business logic: Retrieve all active hostnames from storage.
 *
 * Responsibility:
 * - Orchestrate the retrieval of active hostnames via OutPort
 * - Return raw array<HostnameVO> for maximum reusability
 *
 * The InAdapter (REST controller) is responsible for:
 * - Creating ObtenerHostnamesOutDto from the result
 * - Transforming to JSON response format
 */
final readonly class ObtenerHostnamesUseCase
{
    public function __construct(
        private HostnameOutPort $hostnameOutPort,
    ) {}

    /**
     * Execute the use case
     *
     * @return list<HostnameVO> Array of active hostnames
     */
    public function execute(): array
    {
        return $this->hostnameOutPort->obtenerHostnames();
    }
}
