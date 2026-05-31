<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\Entities;

/**
 * TipoPersonal Domain Entity
 *
 * Represents a valid personnel type in SADER (Base, Enlace, Confianza, Externo).
 *
 * 🔴 NOT AN ANEMIC ENTITY:
 * - Contains business logic (validation)
 * - Protects invariants (nombre cannot be empty)
 * - Encapsulates behavior (isActive check)
 *
 * Aggregate Root: Yes (simple aggregate with no child entities)
 *
 * Invariants:
 * - nombre MUST NOT be empty
 * - activo MUST be boolean
 * - Only active TipoPersonal (activo=true) should be exposed via API
 *
 * ✅ Domain Isolation: Zero Laravel dependencies (pure PHP)
 * ✅ DDD Entity: Has identity (id), protects business rules
 * ✅ Immutable: readonly properties prevent state mutation after construction
 *
 * Usage:
 * - Created by Repositories when loading from database
 * - Used by Application layer to enforce business rules
 * - Never exposed directly to Infrastructure layer (use DTOs instead)
 */
final readonly class TipoPersonal
{
    /**
     * @param  int  $id  Unique identifier
     * @param  string  $nombre  Personnel type name (Base|Enlace|Confianza|Externo)
     * @param  string|null  $descripcion  Optional description
     * @param  bool  $activo  Indicates if this tipo personal is available for selection
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $activo
    ) {
        $this->validate();
    }

    /**
     * Validate entity invariants
     *
     * Business Rules:
     * - BR-001: Nombre cannot be empty (required for display to users)
     *
     * @throws \InvalidArgumentException If invariants are violated
     */
    private function validate(): void
    {
        if (trim($this->nombre) === '') {
            throw new \InvalidArgumentException(
                'TipoPersonal nombre cannot be empty. Business rule violation: BR-001'
            );
        }
    }

    /**
     * Check if TipoPersonal is active and available for selection
     *
     * Business Logic: Only active tipos personal should be returned by API
     *
     * @return bool True if active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->activo;
    }

    /**
     * Get display name for TipoPersonal
     *
     * Business Logic: Provides formatted name for UI display
     *
     * @return string Formatted nombre (capitalized)
     */
    public function getDisplayName(): string
    {
        return ucfirst(strtolower(trim($this->nombre)));
    }
}
