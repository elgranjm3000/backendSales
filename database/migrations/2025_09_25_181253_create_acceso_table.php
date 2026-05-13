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
        Schema::create('acceso', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()->comment('Código único del cliente');
            $table->string('nombre')->comment('Nombre o razón social del cliente');
            $table->string('id_fiscal', 50)->comment('RIF o identificación fiscal');
            $table->text('direccion')->nullable()->comment('Dirección completa del cliente');
            $table->string('telefono', 20)->nullable()->comment('Teléfono de contacto');
            $table->string('zona', 100)->nullable()->comment('Zona geográfica');
            $table->string('ciudad', 100)->nullable()->comment('Ciudad');
            $table->string('grupo', 150)->nullable()->comment('Grupo al que pertenece el cliente');
            $table->string('vendedor', 200)->nullable()->comment('Vendedor asignado');
            $table->string('contacto', 200)->nullable()->comment('Persona de contacto');
            $table->string('estado', 100)->nullable()->comment('Estado o región');
            $table->string('correo_electronico')->nullable()->comment('Email de contacto');
            $table->timestamps();
            
            // Índices para mejorar consultas
            $table->index('codigo');
            $table->index('id_fiscal');
            $table->index('zona');
            $table->index('ciudad');
            $table->index('estado');
            $table->index('grupo');
            $table->index('vendedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acceso');
    }
};
