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
        // Eliminar el unique actual solo en 'version'
        Schema::table('sync_app_versions', function (Blueprint $table) {
            $table->dropUnique(['sync_app_versions_version_unique']);
        });

        // Agregar unique compuesto (version, typeapp)
        Schema::table('sync_app_versions', function (Blueprint $table) {
            $table->unique(['version', 'typeapp'], 'sync_app_versions_version_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_app_versions', function (Blueprint $table) {
            $table->dropUnique(['sync_app_versions_version_type_unique']);
            $table->unique(['version'], 'sync_app_versions_version_unique');
        });
    }
};
