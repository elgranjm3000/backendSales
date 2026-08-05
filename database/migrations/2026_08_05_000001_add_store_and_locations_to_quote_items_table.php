<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->string('store')->default('00')->after('item_type');
            $table->string('locations')->default('00')->after('store');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->dropColumn(['store', 'locations']);
        });
    }
};
