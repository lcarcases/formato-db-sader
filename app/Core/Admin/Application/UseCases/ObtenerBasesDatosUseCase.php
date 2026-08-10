<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;

/**
 * Use Case: Obtener Catálogo de Bases de Datos
 *
 * Business logic: Retrieve all active bases de datos from storage.
 *
 * Responsibility:
 * - Orchestrate the retrieval of active bases de datos via OutPort
 * - Return raw array<BaseDatosVO> for maximum reusability
 *
 * The InAdapter (REST controller) is responsible for:
 * - Creating ObtenerBasesDatosOutDto from the result
 * - Transforming to JSON response format
 */
final readonly class ObtenerBasesDatosUseCase
{
    public function __construct(
        private BaseDatosOutPort $baseDatosOutPort,
    ) {}

    /**
     * Execute the use case
     *
     * @return list<BaseDatosVO> Array of active bases de datos
     */
    public function execute(): array
    {
        return $this->baseDatosOutPort->obtenerBasesDatos();
    }
}
