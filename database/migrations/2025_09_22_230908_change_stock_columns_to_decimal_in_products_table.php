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
            // Cambiar stock de integer a decimal(10, 2)
            $table->decimal('stock', 10, 2)->default(0)->change();
            
            // Cambiar min_stock de integer a decimal(10, 2)
            $table->decimal('min_stock', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('products', function (Blueprint $table) {
            // Revertir los cambios en caso de rollback
            $table->integer('stock')->default(0)->change();
            $table->integer('min_stock')->default(0)->change();
        });
    }
};
