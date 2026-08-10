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
        DB::table('tb_cat_base_datos')->insert([
            [
                'sn_nombre' => 'PPB',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'SURI',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'XAMAN',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'OTROS',
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
        DB::table('tb_cat_base_datos')->truncate();
    }
};
