<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;

/**
 * Use Case: Obtener Catálogo de Esquemas
 *
 * Business logic: Retrieve all active esquemas from storage.
 *
 * Responsibility:
 * - Orchestrate the retrieval of active esquemas via OutPort
 * - Return raw array<EsquemaVO> for maximum reusability
 *
 * The InAdapter (REST controller) is responsible for:
 * - Creating ObtenerEsquemasOutDto from the result
 * - Transforming to JSON response format
 */
final readonly class ObtenerEsquemasUseCase
{
    public function __construct(
        private EsquemaOutPort $esquemaOutPort,
    ) {}

    /**
     * Execute the use case
     *
     * @return list<EsquemaVO> Array of active esquemas
     */
    public function execute(): array
    {
        return $this->esquemaOutPort->obtenerEsquemas();
    }
}
