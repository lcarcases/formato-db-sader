<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;

/**
 * Repository for Hostname data access
 *
 * Encapsulates database queries using Eloquent.
 *
 * Responsibility:
 * - Execute database queries
 * - Return RAW data (Eloquent models) as it comes from database
 * - Isolate Eloquent from the rest of the system
 */
final class HostnameRepository
{
    /**
     * Retrieve all active hostnames from database
     *
     * Queries PostgreSQL for records with ind_activo = 1,
     * ordered by ID ascending.
     *
     * @return list<HostnameModel> Raw Eloquent models (mapping is OutAdapter's job)
     */
    public function obtenerHostnames(): array
    {
        return HostnameModel::query()
            ->where('ind_activo', 1)
            ->orderBy('id_nu_hostname', 'asc')
            ->get(['id_nu_hostname', 'sn_nombre'])
            ->values() // Ensure list<T> type (re-index to 0-based consecutive keys)
            ->all(); // Convert Collection to plain array
    }
}
