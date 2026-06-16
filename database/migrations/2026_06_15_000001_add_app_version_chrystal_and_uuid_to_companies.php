<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('app_version_chrystal', 20)->nullable()->after('offline_token_hours');
            $table->string('uuid_hard_drive', 100)->nullable()->after('app_version_chrystal');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['app_version_chrystal', 'uuid_hard_drive']);
        });
    }
};
