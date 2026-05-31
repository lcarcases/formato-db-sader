<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * ObtenerTiposRequerimientosOutDto
 *
 * Output DTO for retrieving list of Tipos de Requerimientos.
 * Transfers data from Application layer to Infrastructure layer (InAdapter).
 *
 * 🚨 CRITICAL OUTDTO PATTERN RULES:
 * ✅ Uses SEMANTIC property name: $tiposRequerimientos (NOT generic $data)
 * ✅ Uses NESTED DTO pattern: array of ObtenerTipoRequerimientoOutDto objects
 * ✅ Immutable with readonly properties (PHP 8.1+ readonly class)
 * ✅ Pure data container - NO methods except constructor
 *
 * ❌ NO $success property (response metadata belongs in InAdapter/Respuesta)
 * ❌ NO $message property (InAdapter sets messages)
 * ❌ NO $status property (InAdapter determines status)
 * ❌ NO generic $data property (use semantic business names)
 * ❌ NO mixing data and metadata (DTOs are pure data)
 *
 * Pattern: UseCase creates OutDTO → InAdapter uses it to build response
 */
final readonly class ObtenerTiposRequerimientosOutDto
{
    /**
     * @param  ObtenerTipoRequerimientoOutDto[]  $tiposRequerimientos  List of tipo requerimiento items
     */
    public function __construct(
        public array $tiposRequerimientos
    ) {}

    /**
     * Create from raw array data
     *
     * @param  array  $rawData  Array of raw tipo requerimiento data from repository
     */
    public static function fromArray(array $rawData): self
    {
        $items = array_map(
            fn ($item) => ObtenerTipoRequerimientoOutDto::fromStdClass($item),
            $rawData
        );

        return new self($items);
    }
}
