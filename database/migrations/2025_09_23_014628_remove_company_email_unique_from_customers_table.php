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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
            
            // Opcional: mantener índices normales para mejor rendimiento
            $table->index(['company_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
             $table->dropIndex(['company_id', 'email']);
            
            // Restaurar la constraint única compuesta
            $table->unique(['company_id', 'email']);
            //
        });
    }
};
