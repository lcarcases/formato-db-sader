<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;

/**
 * Repository for Esquema data access
 *
 * Encapsulates database queries using Eloquent.
 *
 * Responsibility:
 * - Execute database queries
 * - Return RAW data (Eloquent models) as it comes from database
 * - Isolate Eloquent from the rest of the system
 *
 * Also performs the hostname existence check directly against HostnameModel
 * (same bounded context/layer), without going through HostnameOutPort/
 * HostnameOutAdapter (which remain unmodified from 005).
 */
final class EsquemaRepository
{
    /**
     * Retrieve all active esquemas from database
     *
     * Queries PostgreSQL for records with ind_activo = 1,
     * ordered by ID ascending.
     *
     * @return list<EsquemaModel> Raw Eloquent models (mapping is OutAdapter's job)
     */
    public function obtenerEsquemas(): array
    {
        return EsquemaModel::query()
            ->where('ind_activo', 1)
            ->orderBy('id_nu_esquema', 'asc')
            ->get(['id_nu_esquema', 'sn_nombre'])
            ->values() // Ensure list<T> type (re-index to 0-based consecutive keys)
            ->all(); // Convert Collection to plain array
    }

    /**
     * Retrieve the active esquemas associated to a hostname (via tb_r_hostname_esquema),
     * ordered by id_nu_esquema ascending.
     *
     * @return list<EsquemaModel>|null null if the hostname does not exist in tb_cat_hostname;
     *                                 [] if it exists but has no active associations.
     */
    public function obtenerEsquemasPorHostname(int $idHostname): ?array
    {
        $hostnameExists = HostnameModel::query()
            ->whereKey($idHostname)
            ->exists();

        if (! $hostnameExists) {
            return null;
        }

        return EsquemaModel::query()
            ->select(['tb_cat_esquema.id_nu_esquema', 'tb_cat_esquema.sn_nombre'])
            ->join('tb_r_hostname_esquema', 'tb_r_hostname_esquema.id_nu_esquema', '=', 'tb_cat_esquema.id_nu_esquema')
            ->where('tb_r_hostname_esquema.id_nu_hostname', $idHostname)
            ->where('tb_r_hostname_esquema.ind_activo', 1)
            ->where('tb_cat_esquema.ind_activo', 1)
            ->orderBy('tb_cat_esquema.id_nu_esquema', 'asc')
            ->get()
            ->values()
            ->all();
    }
}
