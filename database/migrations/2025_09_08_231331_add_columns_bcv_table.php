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
        Schema::table('quotes', function (Blueprint $table) {          
        $table->decimal('bcv_rate', 10, 4)->nullable()->after('total');
        $table->date('bcv_date')->nullable()->after('bcv_rate');});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['bcv_rate', 'bcv_date']);
        });
    }
};
