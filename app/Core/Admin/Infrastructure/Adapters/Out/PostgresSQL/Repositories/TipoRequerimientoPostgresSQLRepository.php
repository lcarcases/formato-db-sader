<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * TipoRequerimiento PostgresSQL Repository
 */
final class TipoRequerimientoPostgresSQLRepository
{
    /**
     * Retrieve all active TipoRequerimiento records from database
     *
     * @return array Array of stdClass objects with raw database data
     */
    public function buscarTodos(): array
    {
        return DB::table('tb_cat_tipo_requerimiento')
            ->select('id_nu_requerimiento', 'sn_requerimiento', 'created_at', 'updated_at')
            ->where('activo', true)
            ->orderBy('sn_requerimiento', 'asc')
            ->get()
            ->toArray(); // Convert Collection to raw array
    }

    /**
     * Find a single TipoRequerimiento by ID
     */
    public function buscarPorId(int $id): ?\stdClass
    {
        $result = DB::table('tb_cat_tipo_requerimiento')
            ->select('id_nu_requerimiento', 'sn_requerimiento', 'created_at', 'updated_at')
            ->where('id_nu_requerimiento', $id)
            ->first();

        return $result;
    }

    /**
     * Insert a new TipoRequerimiento record
     *
     * @return int Inserted ID
     */
    public function insertar(array $data): int
    {
        return DB::table('tb_cat_tipo_requerimiento')->insertGetId($data);
    }

    /**
     * Update an existing TipoRequerimiento record
     */
    public function actualizar(int $id, array $data): bool
    {
        return DB::table('tb_cat_tipo_requerimiento')
            ->where('id_nu_requerimiento', $id)
            ->update($data) > 0;
    }

    /**
     * Delete a TipoRequerimiento record (soft delete - set activo = false)
     */
    public function eliminar(int $id): bool
    {
        return DB::table('tb_cat_tipo_requerimiento')
            ->where('id_nu_requerimiento', $id)
            ->update(['activo' => false]) > 0;
    }
}
