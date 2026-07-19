<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

/**
 * Output DTO for Obtener Ambientes Use Case
 *
 * Encapsulates the output of ObtenerAmbientesUseCase for In Adapters.
 * Created by the In Adapter (REST controller) after receiving raw data from UseCase.
 *
 * Purpose: Explicit contract between Use Case result and API response
 */
final readonly class ObtenerAmbientesOutDto
{
    /**
     * @param  list<AmbienteVO>  $ambientes  Array of ambiente value objects
     */
    public function __construct(
        public array $ambientes,
    ) {}
}
