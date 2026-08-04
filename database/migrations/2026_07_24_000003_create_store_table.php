<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store', function (Blueprint $table): void {
            $table->string('code');
            $table->unsignedBigInteger('company_id');
            $table->string('description')->nullable();
            $table->primary(['code', 'company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store');
    }
};
