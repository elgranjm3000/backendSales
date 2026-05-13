<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acceso', function (Blueprint $table) {
            $table->string('api_key', 128)->unique()->nullable()->after('correo_electronico');
            $table->timestamp('api_key_expires_at')->nullable()->after('api_key');
        });
    }

    public function down(): void
    {
        Schema::table('acceso', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_key_expires_at']);
        });
    }
};
