<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tb_cat_esquema')->insert([
            [
                'sn_nombre' => 'ap_activemq_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_apoyos_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_biometricos_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_gestion_doc',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_interfaz',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_inventario_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_movil_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_proagro_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_reportes_suri',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_supervision_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_suri_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_svc',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_tramites_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'ap_viaticos',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'tr_seguridad_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'tr_suri_pd',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tb_cat_esquema')->truncate();
    }
};
