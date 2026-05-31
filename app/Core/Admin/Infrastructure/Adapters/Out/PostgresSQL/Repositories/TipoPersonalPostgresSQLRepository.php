<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * TipoPersonalPostgresSQLRepository
 *
 * PostgreSQL repository for TipoPersonal data access.
 * Uses Laravel Query Builder (NOT Eloquent) for direct database queries.
 *
 * 🔴 REPOSITORY PATTERN:
 * ✅ Located in Infrastructure layer (Laravel-specific)
 * ✅ Uses Query Builder (DB facade) NOT Eloquent
 * ✅ Returns raw data (array of stdClass) NOT domain entities
 * ✅ Handles database-specific concerns (queries, connections)
 * ✅ Maps database column names to expected output format
 *
 * ❌ Does NOT implement OutPort directly (OutAdapter does that)
 * ❌ Does NOT return Domain Entities (returns stdClass raw data)
 * ❌ Does NOT contain business logic (pure data access)
 * ❌ Does NOT catch exceptions (let them bubble up to OutAdapter)
 *
 * Pattern: OutAdapter → Repository → Database
 * Flow: Repository executes query → Returns stdClass[] → OutAdapter handles it
 */
final class TipoPersonalPostgresSQLRepository
{
    /**
     * Retrieve all active TipoPersonal records from database
     *
     * Business Rule: Only return active tipos personal (ind_activo = true)
     * Ordering: By id_nu_tipo_personal ascending (FR-004)
     *
     * Query:
     * ```sql
     * SELECT id_nu_tipo_personal, sn_nombre, sn_descripcion, ind_activo, created_at, updated_at
     * FROM tb_cat_tipo_personal
     * WHERE ind_activo = true
     * ORDER BY id_nu_tipo_personal ASC
     * ```
     *
     * @return array Array of stdClass objects with raw database data
     *
     * @throws \Exception If database query fails
     */
    public function buscarTodos(): array
    {
        return DB::table('tb_cat_tipo_personal')
            ->select(
                'id_nu_tipo_personal',
                'sn_nombre',
                'sn_descripcion',
                'ind_activo',
                'created_at',
                'updated_at'
            )
            ->where('ind_activo', true)
            ->orderBy('id_nu_tipo_personal', 'asc')
            ->get()
            ->toArray(); // Convert Collection to raw array
    }

    /**
     * Find a single TipoPersonal by ID
     *
     * @param  int  $id  Primary key (id_nu_tipo_personal)
     * @return \stdClass|null Returns stdClass if found, null otherwise
     *
     * @throws \Exception If database query fails
     */
    public function buscarPorId(int $id): ?\stdClass
    {
        $result = DB::table('tb_cat_tipo_personal')
            ->select(
                'id_nu_tipo_personal',
                'sn_nombre',
                'sn_descripcion',
                'ind_activo',
                'created_at',
                'updated_at'
            )
            ->where('id_nu_tipo_personal', $id)
            ->first();

        return $result;
    }

    /**
     * Count total active TipoPersonal records
     *
     * Utility method for validation/logging
     *
     * @return int Count of active records
     */
    public function contarActivos(): int
    {
        return DB::table('tb_cat_tipo_personal')
            ->where('ind_activo', true)
            ->count();
    }
}
