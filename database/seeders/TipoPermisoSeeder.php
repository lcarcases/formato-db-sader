<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TipoPermisoSeeder
 * 
 * Seeder para la tabla tb_cat_tipo_permiso con los 4 tipos de permiso estándar.
 */
final class TipoPermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposPermiso = [
            [
                'id_nu_tipo_permiso' => 1,
                'ln_nombre' => 'Consulta',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de solo lectura sobre la base de datos'
            ],
            [
                'id_nu_tipo_permiso' => 2,
                'ln_nombre' => 'Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso para modificar registros en la base de datos'
            ],
            [
                'id_nu_tipo_permiso' => 3,
                'ln_nombre' => 'Eliminación',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso para eliminar registros de la base de datos'
            ],
            [
                'id_nu_tipo_permiso' => 4,
                'ln_nombre' => 'Consulta y Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso combinado de lectura y modificación'
            ]
        ];

        DB::table('tb_cat_tipo_permiso')->insert($tiposPermiso);
    }
}
