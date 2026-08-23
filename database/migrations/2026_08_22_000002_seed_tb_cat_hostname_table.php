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
        DB::table('tb_cat_hostname')->insert([
            [
                'sn_nombre' => 'pgrdesbds09',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'sridesbds09',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'pgrprdbdsmz02',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'sriprdbdsmz02',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'divprdbds01',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'pgrqabds08',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => 'sriqabds08',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => '10.1.35.50',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => '10.1.21.95',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => '10.1.20.25',
                'ind_activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sn_nombre' => '10.54.49.100',
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
        DB::table('tb_cat_hostname')->truncate();
    }
};
