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
        Schema::create('sync_app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique()->comment('Version format: 1.0.0');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('active=allowed, inactive=blocked');
            $table->string('notes')->nullable()->comment('Release notes or reason for blocking');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_app_versions');
    }
};
