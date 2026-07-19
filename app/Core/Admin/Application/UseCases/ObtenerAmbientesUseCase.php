<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

/**
 * Use Case: Obtener Catálogo de Ambientes de Desarrollo
 *
 * Business logic: Retrieve all active ambientes from storage.
 *
 * Responsibility:
 * - Orchestrate the retrieval of active ambientes via OutPort
 * - Return raw array<AmbienteVO> for maximum reusability
 *
 * The InAdapter (REST controller) is responsible for:
 * - Creating ObtenerAmbientesOutDto from the result
 * - Transforming to JSON response format
 */
final readonly class ObtenerAmbientesUseCase
{
    public function __construct(
        private AmbienteDesarrolloOutPort $outPort,
    ) {}

    /**
     * Execute the use case
     *
     * @return list<AmbienteVO> Array of active ambientes
     */
    public function execute(): array
    {
        return $this->outPort->obtenerAmbientesDesarrollo();
    }
}
