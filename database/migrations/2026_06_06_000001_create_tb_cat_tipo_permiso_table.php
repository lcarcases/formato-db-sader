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
        Schema::create('tb_cat_tipo_permiso', function (Blueprint $table) {
            $table->id('id_nu_tipo_permiso')->comment('Auto-increment identifier');
            $table->string('ln_nombre', 100)->unique()->comment('Permission type name');
            $table->boolean('ind_activo')->default(true)->comment('Indicates if tipo is available');
            $table->text('sn_descripcion')->nullable()->comment('Optional description');
            $table->timestamps();
            
            $table->index('ind_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_cat_tipo_permiso');
    }
};
