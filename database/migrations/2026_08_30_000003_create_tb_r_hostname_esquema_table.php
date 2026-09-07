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
        Schema::create('tb_r_hostname_esquema', function (Blueprint $table) {
            $table->id('id_nu_hostname_esquema')->comment('Identificador único de la asociación');
            $table->foreignId('id_nu_hostname')
                ->comment('Hostname asociado')
                ->constrained('tb_cat_hostname', 'id_nu_hostname')
                ->cascadeOnDelete();
            $table->foreignId('id_nu_esquema')
                ->comment('Esquema asociado')
                ->constrained('tb_cat_esquema', 'id_nu_esquema')
                ->cascadeOnDelete();
            $table->smallInteger('ind_activo')->default(1)->comment('Indicador de asociación activa (0=inactivo, 1=activo)');
            $table->timestamps();

            // Unique composite index to prevent duplicate associations
            $table->unique(['id_nu_hostname', 'id_nu_esquema'], 'uq_tb_r_hostname_esquema_hostname_esquema');

            // Index for the main query path of the nested endpoint
            $table->index('id_nu_hostname', 'idx_tb_r_hostname_esquema_hostname');
        });

        // Add check constraint for ind_activo using raw SQL
        DB::statement('ALTER TABLE tb_r_hostname_esquema ADD CONSTRAINT chk_tb_r_hostname_esquema_ind_activo CHECK (ind_activo IN (0, 1))');

        // Add table comment
        DB::statement("COMMENT ON TABLE tb_r_hostname_esquema IS 'Relación muchos-a-muchos entre hostnames y esquemas de base de datos'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_r_hostname_esquema');
    }
};
