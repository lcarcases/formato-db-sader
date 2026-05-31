<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

/**
 * OutPort Interface for TipoRequerimiento
 *
 * Defines the contract for retrieving TipoRequerimiento data from external systems.
 * This interface is implemented by OutAdapters in the Infrastructure layer.
 *
 * Pattern: Application layer defines interface → Infrastructure layer implements it
 * Flow: UseCase depends on this OutPort → OutAdapter implements OutPort → Repository provides data access
 */
interface ITipoRequerimientoOutPort
{
    /**
     * Retrieve all active TipoRequerimiento records
     *
     * @return array Array of raw data (assoc arrays or stdClass objects)
     */
    public function obtenerTodos(): array;
}
