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
        Schema::table('sellers', function (Blueprint $table) {
            // Eliminar el unique constraint actual del code
            $table->dropUnique('sellers_code_unique');
            
            // Agregar unique constraint compuesto
            $table->unique(['company_id', 'code'], 'sellers_company_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // Revertir cambios
            $table->dropUnique('sellers_company_code_unique');
            $table->unique('code', 'sellers_code_unique');
        });
    }
};
