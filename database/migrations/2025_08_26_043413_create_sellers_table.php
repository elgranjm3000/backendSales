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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Usuario vendedor');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->comment('Compañía a la que pertenece');
            $table->string('code')->unique()->comment('Código único del vendedor');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->double('percent_sales', 15, 8)->default(0)->comment('Porcentaje de comisión por ventas');
            $table->double('percent_receivable', 15, 8)->default(0)->comment('Porcentaje por cuentas por cobrar');
            $table->boolean('inkeeper')->default(false)->comment('Es posadero/encargado');
            $table->string('user_code')->nullable()->comment('Código de usuario personalizado');
            $table->double('percent_gerencial_debit_note', 15, 8)->default(0)->comment('Porcentaje nota débito gerencial');
            $table->double('percent_gerencial_credit_note', 15, 8)->default(0)->comment('Porcentaje nota crédito gerencial');
            $table->double('percent_returned_check', 15, 8)->default(0)->comment('Porcentaje cheque devuelto');
            $table->enum('seller_status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('user_id');
            $table->index('company_id');
            $table->index(['company_id', 'seller_status']);
            $table->index('code');
            $table->unique(['user_id', 'company_id'], 'unique_user_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};