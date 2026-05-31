<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->dropIfExists('tb_cat_tipo_requerimiento');
        
        Schema::connection('pgsql')->create('tb_cat_tipo_requerimiento', function (Blueprint $table) {
            $table->integer('id_nu_requerimiento')->primary();
            $table->string('sn_requerimiento', 255);
            $table->timestamps();
        });

        // Insert initial data
        DB::connection('pgsql')->table('tb_cat_tipo_requerimiento')->insert([
            [
                'id_nu_requerimiento' => 1,
                'sn_requerimiento' => 'ALTA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_nu_requerimiento' => 2,
                'sn_requerimiento' => 'BAJA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_nu_requerimiento' => 3,
                'sn_requerimiento' => 'VALIDACION DE PERMANENCIA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_nu_requerimiento' => 4,
                'sn_requerimiento' => 'AGREGAR PERMISOS',
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
        Schema::connection('pgsql')->dropIfExists('tb_cat_tipo_requerimiento');
    }
};
