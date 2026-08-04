<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('conversion_factor', 10, 6)->default(0)->after('unit');
            $table->string('unit_type')->nullable()->after('conversion_factor');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['conversion_factor', 'unit_type']);
        });
    }
};
