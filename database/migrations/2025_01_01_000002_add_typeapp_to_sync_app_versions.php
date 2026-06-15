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
        Schema::table('sync_app_versions', function (Blueprint $table) {
            $table->string('typeapp')->default('mobile')->after('version')->comment('Client type: mobile, laravel, web, etc.');
            $table->index(['typeapp', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_app_versions', function (Blueprint $table) {
            $table->dropIndex(['typeapp', 'status']);
            $table->dropColumn('typeapp');
        });
    }
};
