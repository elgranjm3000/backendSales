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
        Schema::table('products', function (Blueprint $table) {
            // Eliminar el unique constraint actual del code
            $table->dropUnique('products_code_unique');
            
            // Agregar unique constraint compuesto
            $table->unique(['company_id', 'code'], 'products_company_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revertir cambios
            $table->dropUnique('products_company_code_unique');
            $table->unique('code', 'products_code_unique');
        });
    }
};
