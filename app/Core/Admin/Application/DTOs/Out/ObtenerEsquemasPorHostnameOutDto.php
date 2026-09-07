<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * Output DTO for Obtener Esquemas Por Hostname Use Case
 *
 * Encapsulates the output of ObtenerEsquemasPorHostnameUseCase for In Adapters.
 * MUST be instantiated only in the InAdapter, after calling the use case —
 * never in the UseCase, Domain, or Infrastructure layers.
 *
 * Carries only the real esquemas associated to the hostname (never the synthetic
 * "Todos" entry); toArray() ALWAYS prepends {id: 0, nombre: 'Todos'} as the first
 * element, since this entry is never persisted nor modeled as an EsquemaVO.
 *
 * Purpose: Explicit contract between Use Case result and API response
 */
final readonly class ObtenerEsquemasPorHostnameOutDto
{
    /**
     * @param  list<ObtenerEsquemaOutDto>  $esquemas  Array of nested single-item DTOs (no "Todos", no domain objects)
     */
    public function __construct(
        public array $esquemas,
    ) {}

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function toArray(): array
    {
        $todos = ['id' => 0, 'nombre' => 'Todos'];

        return [
            $todos,
            ...array_map(
                fn (ObtenerEsquemaOutDto $esquema): array => $esquema->toArray(),
                $this->esquemas
            ),
        ];
    }
}
