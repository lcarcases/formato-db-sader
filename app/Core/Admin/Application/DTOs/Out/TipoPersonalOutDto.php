<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * TipoPersonalOutDto
 *
 * Output DTO representing a single Tipo Personal item.
 * Used within ObtenerTiposPersonalOutDto collection.
 *
 * 🔴 OUTDTO PATTERN:
 * ✅ Immutable with readonly properties (no setters)
 * ✅ Contains only data (no business logic)
 * ✅ Provides toArray() for serialization
 * ✅ Provides static factory method fromStdClass() for construction
 * ✅ Simple property names (id, nombre) NOT database column names
 *
 * ❌ NO $success, $message, $code properties (those belong in Respuesta)
 * ❌ NO business logic methods (pure data container)
 * ❌ NO database-specific types (uses primitive types only)
 *
 * Purpose: Transfer single TipoPersonal data from Application to Infrastructure layer
 * Flow: Repository → OutAdapter → UseCase → InAdapter → Respuesta → JSON response
 */
final readonly class TipoPersonalOutDto
{
    /**
     * @param  int  $id  Unique identifier
     * @param  string  $nombre  Personnel type name (Base|Enlace|Confianza|Externo)
     */
    public function __construct(
        public int $id,
        public string $nombre
    ) {}

    /**
     * Convert DTO to associative array for JSON serialization
     *
     * Output format matches API contract specification.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }

    /**
     * Create DTO from raw database data (stdClass object)
     *
     * Maps database column names to DTO property names:
     * - id_nu_tipo_personal → id
     * - sn_nombre → nombre
     *
     * @param  \stdClass  $data  Raw data from repository with database column names
     */
    public static function fromStdClass(\stdClass $data): self
    {
        return new self(
            id: $data->id_nu_tipo_personal,
            nombre: $data->sn_nombre
        );
    }
}
