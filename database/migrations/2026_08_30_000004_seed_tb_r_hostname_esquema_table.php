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
        // 16 esquemas sembrados en orden (2026_08_30_000002), en cada uno de los 3
        // hostnames sembrados en 005 (2026_08_22_000002): sridesbds09 (id 2),
        // sriqabds08 (id 7), sriprdbdsmz02 (id 4).
        $esquemaNombres = [
            'ap_activemq_pd',
            'ap_apoyos_pd',
            'ap_biometricos_pd',
            'ap_gestion_doc',
            'ap_interfaz',
            'ap_inventario_pd',
            'ap_movil_pd',
            'ap_proagro_pd',
            'ap_reportes_suri',
            'ap_supervision_pd',
            'ap_suri_pd',
            'ap_svc',
            'ap_tramites_pd',
            'ap_viaticos',
            'tr_seguridad_pd',
            'tr_suri_pd',
        ];

        $idsEsquemas = DB::table('tb_cat_esquema')
            ->whereIn('sn_nombre', $esquemaNombres)
            ->orderByRaw("array_position(ARRAY['".implode("','", $esquemaNombres)."'], sn_nombre)")
            ->pluck('id_nu_esquema')
            ->all();

        $idsHostnames = [2, 7, 4]; // sridesbds09, sriqabds08, sriprdbdsmz02

        $now = now();
        $rows = [];

        foreach ($idsEsquemas as $idEsquema) {
            foreach ($idsHostnames as $idHostname) {
                $rows[] = [
                    'id_nu_hostname' => $idHostname,
                    'id_nu_esquema' => $idEsquema,
                    'ind_activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tb_r_hostname_esquema')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tb_r_hostname_esquema')->truncate();
    }
};
