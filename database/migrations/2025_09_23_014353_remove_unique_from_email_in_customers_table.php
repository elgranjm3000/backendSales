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
            // Eliminar el índice único del campo email
            $table->dropUnique(['email']);
            
            // Opcional: si quieres mantener un índice normal para mejor rendimiento en búsquedas
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
           $table->dropIndex(['email']);
            
            // Restaurar la constraint única
            $table->unique('email');
        });
    }
};
