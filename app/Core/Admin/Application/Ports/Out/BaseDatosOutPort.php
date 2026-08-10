<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;

/**
 * Port Out (Repository Interface) for Base de Datos
 *
 * Defines the contract for retrieving bases de datos from storage.
 * Implementation lives in Infrastructure layer (Out Adapter).
 *
 * Dependency Inversion Principle:
 * - Application layer defines the interface
 * - Infrastructure layer implements it
 */
interface BaseDatosOutPort
{
    /**
     * Retrieve all active bases de datos from storage
     *
     * Returns only bases de datos with ind_activo = 1, ordered by ID ascending.
     *
     * @return list<BaseDatosVO> Array of BaseDatosVO objects
     */
    public function obtenerBasesDatos(): array;
}
