<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tb_cat_tipo_personal table for storing personnel types catalog.
     * 
     * Table: tb_cat_tipo_personal
     * Purpose: Store catalog of personnel types (Base, Enlace, Confianza, Externo)
     * Used by: TipoPersonal domain entity, ObtenerTiposPersonalUseCase
     */
    public function up(): void
    {
        
        Schema::dropIfExists('tb_cat_tipo_personal');

        Schema::create('tb_cat_tipo_personal', function (Blueprint $table) {
            // Primary key
            $table->id('id_nu_tipo_personal')->comment('Auto-increment identifier');
            
            // Business fields
            $table->string('sn_nombre', 50)->unique()->comment('Personnel type name');
            $table->text('sn_descripcion')->nullable()->comment('Optional description');
            $table->boolean('ind_activo')->default(true)->comment('Indicates if tipo is available');
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent()->comment('Record creation time');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Record last update time');
            
            // Indexes
            $table->index('ind_activo', 'idx_tb_cat_tipo_personal_activo');
            $table->index('sn_nombre', 'idx_tb_cat_tipo_personal_nombre');
        });
        
        // Add comment to table
        DB::statement("COMMENT ON TABLE tb_cat_tipo_personal IS 'Catalog of personnel types for SADER system'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_cat_tipo_personal');
    }
};
