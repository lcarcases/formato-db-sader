<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * Output DTO for Obtener Ambientes Use Case
 *
 * Encapsulates the output of ObtenerAmbientesUseCase for In Adapters.
 * MUST be instantiated only in the InAdapter, after calling the use case —
 * never in the UseCase, Domain, or Infrastructure layers.
 *
 * Purpose: Explicit contract between Use Case result and API response
 */
final readonly class ObtenerAmbientesOutDto
{
    /**
     * @param  list<ObtenerAmbienteOutDto>  $ambientes  Array of nested single-item DTOs (no domain objects)
     */
    public function __construct(
        public array $ambientes,
    ) {}

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function toArray(): array
    {
        return array_map(
            fn (ObtenerAmbienteOutDto $ambiente): array => $ambiente->toArray(),
            $this->ambientes
        );
    }
}
