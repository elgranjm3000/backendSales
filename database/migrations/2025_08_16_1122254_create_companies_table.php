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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->comment('Usuario propietario de la compañía');
            $table->string('name');
            $table->string('rif')->unique()->comment('RIF de la compañía');
            $table->text('description')->nullable();
            $table->string('address')->nullable();            
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->binary('logo')->nullable()->comment('Logo de la compañía');
            $table->string('logo_type')->nullable()->comment('Tipo MIME del logo');
            $table->string('email')->default('00');
            $table->string('contact')->nullable()->comment('Persona de contacto');
            $table->unsignedBigInteger('key_system_items_id');
            $table->string('serial_no')->nullable()->comment('Número serial');
            $table->binary('restaurant_image')->nullable()->comment('Imagen del restaurante');
            $table->string('restaurant_image_type')->nullable()->comment('Tipo MIME imagen restaurante');
            $table->binary('main_image')->nullable()->comment('Imagen principal');
            $table->string('main_image_type')->nullable()->comment('Tipo MIME imagen principal');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('user_id');
            $table->index(['user_id', 'status']);
            $table->foreign('key_system_items_id')->references('id')->on('key_system_items');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};