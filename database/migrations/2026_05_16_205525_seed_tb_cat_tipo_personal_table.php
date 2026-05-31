<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Seeds tb_cat_tipo_personal table with initial 4 personnel types:
     * - Base (permanent staff)
     * - Enlace (liaison staff)
     * - Confianza (trusted staff)
     * - Externo (external staff)
     * 
     * Data source: Business requirement from spec.md
     * Purpose: Provide catalog options for personnel type selection
     */
    public function up(): void
    {
        // Skip seeding in test environment - tests insert their own data
        if (app()->environment('testing')) {
            return;
        }

        DB::table('tb_cat_tipo_personal')->insert([
            [
                'sn_nombre' => 'Base',
                'sn_descripcion' => 'Personal de base',
                'ind_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'sn_nombre' => 'Enlace',
                'sn_descripcion' => 'Personal de enlace',
                'ind_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'sn_nombre' => 'Confianza',
                'sn_descripcion' => 'Personal de confianza',
                'ind_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'sn_nombre' => 'Externo',
                'sn_descripcion' => 'Personal externo',
                'ind_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     * 
     * Remove seeded data when rolling back migration.
     */
    public function down(): void
    {
        DB::table('tb_cat_tipo_personal')->whereIn('sn_nombre', [
            'Base',
            'Enlace',
            'Confianza',
            'Externo'
        ])->delete();
    }
};
