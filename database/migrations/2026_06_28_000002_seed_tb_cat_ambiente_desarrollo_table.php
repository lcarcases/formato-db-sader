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
        DB::table('tb_cat_ambiente_desarrollo')->insert([
            [
                'sn_nombre' => 'Desarrollo',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'QA',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'Producción',
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
        DB::table('tb_cat_ambiente_desarrollo')->truncate();
    }
};
