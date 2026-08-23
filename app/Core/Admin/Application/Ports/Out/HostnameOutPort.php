<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

use App\Core\Admin\Domain\ValueObjects\HostnameVO;

/**
 * Port Out (Repository Interface) for Hostname
 *
 * Defines the contract for retrieving hostnames from storage.
 * Implementation lives in Infrastructure layer (Out Adapter).
 *
 * Dependency Inversion Principle:
 * - Application layer defines the interface
 * - Infrastructure layer implements it
 */
interface HostnameOutPort
{
    /**
     * Retrieve all active hostnames from storage
     *
     * Returns only hostnames with ind_activo = 1, ordered by ID ascending.
     *
     * @return list<HostnameVO> Array of HostnameVO objects
     */
    public function obtenerHostnames(): array;
}
