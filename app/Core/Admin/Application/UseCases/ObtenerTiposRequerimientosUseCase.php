<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;

/**
 * ObtenerTiposRequerimientosUseCase
 *
 * Retrieves all active Tipos de Requerimientos from the system.
 *
 * 🚨 CRITICAL USECASE PATTERN RULES:
 * ✅ Uses SPANISH verb: "Obtener" (NOT English "Get")
 * ✅ Returns RAW array (NOT DTO) - for maximum reusability across InAdapters
 * ✅ Throws exceptions - InAdapter catches and converts to response format
 * ✅ Depends on OutPort INTERFACE (NOT concrete Repository or OutAdapter)
 * ✅ Standard constructor with separate property declaration
 *
 * ❌ NO interface for simple CRUD (only use InPort interface for Decorator pattern)
 * ❌ NO catching exceptions (let InAdapter handle error responses)
 * ❌ NO returning DTOs (reduces reusability, return arrays instead)
 * ❌ NO `private readonly` pattern (use separate property declaration)
 * ❌ NO direct Repository dependency (depend on OutPort interface)
 *
 * Flow: InAdapter → UseCase → OutPort → OutAdapter → Repository → Database
 */
final class ObtenerTiposRequerimientosUseCase
{
    private ITipoRequerimientoOutPort $tipoRequerimientoOutPort;

    public function __construct(
        ITipoRequerimientoOutPort $tipoRequerimientoOutPort
    ) {
        $this->tipoRequerimientoOutPort = $tipoRequerimientoOutPort;
    }

    /**
     * Execute the use case
     *
     * Retrieves all active tipos de requerimientos and returns raw data.
     * InAdapter is responsible for converting this to response format.
     *
     * @return array Array of tipos de requerimientos (raw data from OutAdapter)
     *
     * @throws \Exception If data retrieval fails
     */
    public function ejecutar(): array
    {
        // Retrieve data through OutPort (implemented by OutAdapter)
        $tiposRequerimientos = $this->tipoRequerimientoOutPort->obtenerTodos();

        // Return raw data - let InAdapter format the response
        return $tiposRequerimientos;
    }
}
