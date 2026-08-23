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
        Schema::create('tb_cat_hostname', function (Blueprint $table) {
            $table->id('id_nu_hostname')->comment('Identificador único del hostname');
            $table->string('sn_nombre', 100)->unique()->comment('Hostname de servidor o dirección IP');
            $table->smallInteger('ind_activo')->default(1)->comment('Indicador de registro activo (0=inactivo, 1=activo)');
            $table->timestamps();

            // Index for frequent queries filtering by active status
            $table->index('ind_activo', 'idx_tb_cat_hostname_activo');
        });

        // Add check constraint for ind_activo using raw SQL
        DB::statement('ALTER TABLE tb_cat_hostname ADD CONSTRAINT chk_tb_cat_hostname_ind_activo CHECK (ind_activo IN (0, 1))');

        // Add table comment
        DB::statement("COMMENT ON TABLE tb_cat_hostname IS 'Catálogo de hostnames/direcciones IP disponibles para solicitud de acceso'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_cat_hostname');
    }
};
