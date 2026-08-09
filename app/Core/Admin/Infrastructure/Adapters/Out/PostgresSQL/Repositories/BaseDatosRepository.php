<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\BaseDatosModel;

/**
 * Repository for Base de Datos data access
 *
 * Encapsulates database queries using Eloquent.
 *
 * Responsibility:
 * - Execute database queries
 * - Return RAW data (Eloquent models) as it comes from database
 * - Isolate Eloquent from the rest of the system
 */
final class BaseDatosRepository
{
    /**
     * Retrieve all active bases de datos from database
     *
     * Queries PostgreSQL for records with ind_activo = 1,
     * ordered by ID ascending.
     *
     * @return list<BaseDatosModel> Raw Eloquent models (mapping is OutAdapter's job)
     */
    public function obtenerBasesDatos(): array
    {
        return BaseDatosModel::query()
            ->where('ind_activo', 1)
            ->orderBy('id_nu_base_datos', 'asc')
            ->get(['id_nu_base_datos', 'sn_nombre'])
            ->values() // Ensure list<T> type (re-index to 0-based consecutive keys)
            ->all(); // Convert Collection to plain array
    }
}
