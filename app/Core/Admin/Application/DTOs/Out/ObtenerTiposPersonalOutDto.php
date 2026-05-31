<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * ObtenerTiposPersonalOutDto
 *
 * Output DTO wrapper for collection of Tipos Personal.
 * Transfers data from Application layer to Infrastructure layer (InAdapter).
 *
 * 🔴 CRITICAL OUTDTO NAMING PATTERN:
 * ✅ VERB PREFIX: "Obtener" (Spanish verb matching use case)
 * ✅ PLURAL CONCEPT: "TiposPersonal" (collection of multiple items)
 * ✅ SUFFIX: "OutDto" (output data transfer object)
 *
 * This follows the mandatory pattern: {VerbSpanish}{ConceptPluralSpanish}OutDto
 *
 * ❌ FORBIDDEN NAMES:
 * - TipoPersonalOutDto (missing verb, and singular for collection)
 * - TiposPersonalItemDto (wrong suffix, missing verb)
 * - TiposPersonalDataDto (wrong suffix, missing verb)
 * - GetTiposPersonalOutDto (English verb forbidden)
 *
 * 🔴 OUTDTO PATTERN RULES:
 * ✅ Uses SEMANTIC property name: $tiposPersonal (NOT generic $data or $items)
 * ✅ Uses NESTED DTO pattern: array of TipoPersonalOutDto objects
 * ✅ Immutable with readonly properties (PHP 8.1+ readonly class)
 * ✅ Pure data container - NO methods except constructor and factory
 *
 * ❌ NO $success property (response metadata belongs in InAdapter/Respuesta)
 * ❌ NO $message property (InAdapter sets messages)
 * ❌ NO $status property (InAdapter determines status)
 * ❌ NO generic $data or $items property (use semantic business names)
 * ❌ NO mixing data and metadata (DTOs are pure data)
 *
 * Pattern: UseCase creates OutDTO → InAdapter uses it to build Respuesta
 */
final readonly class ObtenerTiposPersonalOutDto
{
    /**
     * @param  TipoPersonalOutDto[]  $tiposPersonal  List of tipo personal items
     */
    public function __construct(
        public array $tiposPersonal
    ) {}

    /**
     * Create from raw array data
     *
     * Transforms raw repository data into nested DTO structure.
     *
     * @param  array  $rawData  Array of stdClass objects from repository
     */
    public static function fromArray(array $rawData): self
    {
        $items = array_map(
            fn ($item) => TipoPersonalOutDto::fromStdClass($item),
            $rawData
        );

        return new self($items);
    }
}
