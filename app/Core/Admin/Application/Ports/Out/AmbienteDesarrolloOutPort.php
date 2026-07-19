<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

/**
 * Port Out (Repository Interface) for Ambiente de Desarrollo
 *
 * Defines the contract for retrieving ambientes from storage.
 * Implementation lives in Infrastructure layer (Out Adapter).
 *
 * Dependency Inversion Principle:
 * - Application layer defines the interface
 * - Infrastructure layer implements it
 */
interface AmbienteDesarrolloOutPort
{
    /**
     * Retrieve all active ambientes de desarrollo from storage
     *
     * Returns only ambientes with ind_activo = 1, ordered by ID ascending.
     *
     * @return list<AmbienteVO> Array of AmbienteVO objects
     */
    public function obtenerAmbientesDesarrollo(): array;
}
