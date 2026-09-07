<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * Output DTO for a single Esquema item
 *
 * Nested DTO shared by ObtenerEsquemasOutDto and ObtenerEsquemasPorHostnameOutDto
 * for collection responses. Carries only primitive data — no domain objects
 * (Value Objects/Entities) ever leak into the DTO layer.
 *
 * Purpose: Explicit, framework-agnostic contract between Use Case result and API response
 */
final readonly class ObtenerEsquemaOutDto
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {}

    /**
     * @return array{id: int, nombre: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
