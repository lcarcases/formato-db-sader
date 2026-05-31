<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

/**
 * ITipoPersonalOutPort
 *
 * Outbound Port Interface for TipoPersonal
 *
 * Defines the contract for retrieving TipoPersonal data from external systems.
 * This interface is implemented by OutAdapters in the Infrastructure layer.
 *
 * 🔴 OUTPORT PATTERN:
 * ✅ Located in Application layer (defines what app NEEDS from infrastructure)
 * ✅ Framework-agnostic interface (no Laravel types in signature)
 * ✅ Returns raw data (array of stdClass) NOT domain entities
 * ✅ Implemented by Infrastructure layer (PostgresSQLOutAdapter)
 *
 * Dependency Direction: Application → Infrastructure (via interface)
 * Pattern: Application layer defines interface → Infrastructure layer implements it
 * Flow: UseCase depends on OutPort → OutAdapter implements OutPort → Repository provides data access
 *
 * Why return raw data instead of entities?
 * - Repositories in Infrastructure can't depend on Domain entities (circular dependency)
 * - OutAdapter handles entity mapping if needed
 * - Use Case transforms raw data to DTOs for presentation
 */
interface ITipoPersonalOutPort
{
    /**
     * Retrieve all active TipoPersonal records from persistence
     *
     * Business Rule: Only return active tipos personal (ind_activo = true)
     *
     * @return array Array of stdClass objects with properties:
     *               - id_nu_tipo_personal (int)
     *               - sn_nombre (string)
     *               - sn_descripcion (string|null)
     *               - ind_activo (bool)
     *               - created_at (string)
     *               - updated_at (string)
     *
     * @throws \Exception If data retrieval fails (DB connection, query error)
     */
    public function obtenerTodos(): array;
}
