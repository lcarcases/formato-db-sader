<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tb_cat_esquema', function (Blueprint $table) {
            $table->id('id_nu_esquema')->comment('Identificador único del esquema');
            $table->string('sn_nombre', 100)->unique()->comment('Nombre del esquema de base de datos');
            $table->smallInteger('ind_activo')->default(1)->comment('Indicador de registro activo (0=inactivo, 1=activo)');
            $table->timestamps();

            // Index for frequent queries filtering by active status
            $table->index('ind_activo', 'idx_tb_cat_esquema_activo');
        });

        // Add check constraint for ind_activo using raw SQL
        DB::statement('ALTER TABLE tb_cat_esquema ADD CONSTRAINT chk_tb_cat_esquema_ind_activo CHECK (ind_activo IN (0, 1))');

        // Add table comment
        DB::statement("COMMENT ON TABLE tb_cat_esquema IS 'Catálogo de esquemas de base de datos disponibles para solicitud de acceso'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_cat_esquema');
    }
};
