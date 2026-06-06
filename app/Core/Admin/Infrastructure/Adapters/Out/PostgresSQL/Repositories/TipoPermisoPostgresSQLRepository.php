<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * TipoPermisoPostgresSQLRepository
 *
 * Repositorio concreto para acceso a datos de tipos de permiso
 * en PostgreSQL. NO implementa interfaces, solo realiza queries.
 */
final class TipoPermisoPostgresSQLRepository
{
    private const TABLE = 'tb_cat_tipo_permiso';

    /**
     * Busca todos los tipos de permiso activos
     *
     * @return array Array of stdClass objects with raw database data
     */
    public function buscarTodos(): array
    {
        return DB::table(self::TABLE)
            ->select(
                'id_nu_tipo_permiso',
                'ln_nombre',
                'ind_activo',
                'sn_descripcion',
                'created_at',
                'updated_at'
            )
            ->where('ind_activo', true)
            ->orderBy('id_nu_tipo_permiso', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Busca un tipo de permiso por su ID
     *
     * @param  int  $id  ID del tipo de permiso
     * @return \stdClass|null stdClass if found, null otherwise
     */
    public function buscarPorId(int $id): ?\stdClass
    {
        return DB::table(self::TABLE)
            ->select(
                'id_nu_tipo_permiso',
                'ln_nombre',
                'ind_activo',
                'sn_descripcion',
                'created_at',
                'updated_at'
            )
            ->where('id_nu_tipo_permiso', $id)
            ->first();
    }

    /**
     * Busca todos los tipos de permiso (incluidos inactivos)
     *
     * @return array Array of stdClass objects with all permission types
     */
    public function buscarTodosIncluyendoInactivos(): array
    {
        return DB::table(self::TABLE)
            ->select(
                'id_nu_tipo_permiso',
                'ln_nombre',
                'ind_activo',
                'sn_descripcion',
                'created_at',
                'updated_at'
            )
            ->orderBy('id_nu_tipo_permiso', 'asc')
            ->get()
            ->toArray();
    }
}
