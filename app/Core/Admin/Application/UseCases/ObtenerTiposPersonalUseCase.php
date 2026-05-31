<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;

/**
 * ObtenerTiposPersonalUseCase
 *
 * Retrieves all active Tipos de Personal from the system.
 *
 * 🚨 CRITICAL USECASE PATTERN RULES:
 * ✅ Uses SPANISH verb: "Obtener" (NOT English "Get")
 * ✅ Uses PLURAL: "TiposPersonal" (returns collection of multiple items)
 * ✅ Returns RAW array (NOT DTO) - for maximum reusability across InAdapters
 * ✅ Throws exceptions - InAdapter catches and converts to response format
 * ✅ Depends on OutPort INTERFACE (NOT concrete Repository or OutAdapter)
 * ✅ Standard constructor with separate property declaration
 * ✅ NO framework dependencies (pure PHP only)
 *
 * ❌ NO interface for simple CRUD (only use InPort interface for Decorator pattern)
 * ❌ NO catching exceptions (let InAdapter handle error responses)
 * ❌ NO returning DTOs (reduces reusability, return arrays instead)
 * ❌ NO `private readonly` pattern (use separate property declaration)
 * ❌ NO direct Repository dependency (depend on OutPort interface)
 * ❌ NO logging (InAdapter responsibility)
 * ❌ NO Laravel dependencies (Hexagonal Architecture compliance)
 *
 * Flow: InAdapter → UseCase → OutPort → OutAdapter → Repository → Database
 *
 * Business Rules Enforced:
 * - BR-001: Only return active tipos personal (ind_activo = true)
 * - BR-002: Results ordered by id ascending
 */
final class ObtenerTiposPersonalUseCase
{
    private ITipoPersonalOutPort $tipoPersonalOutPort;

    /**
     * @param  ITipoPersonalOutPort  $tipoPersonalOutPort  Outbound port for data access
     */
    public function __construct(
        ITipoPersonalOutPort $tipoPersonalOutPort
    ) {
        $this->tipoPersonalOutPort = $tipoPersonalOutPort;
    }

    /**
     * Execute the use case
     *
     * Retrieves all active tipos de personal and returns raw data.
     * InAdapter is responsible for:
     * - Logging execution with structured context
     * - Converting result to response format  
     * - Handling exceptions and error responses
     *
     * @return array<int, \stdClass> Array of tipos de personal (raw data from OutAdapter)
     *
     * @throws \Exception If data retrieval fails
     */
    public function ejecutar(): array
    {
        // Retrieve data through OutPort (implemented by OutAdapter)
        // Exceptions propagate to InAdapter for handling
        return $this->tipoPersonalOutPort->obtenerTodos();
    }
}
