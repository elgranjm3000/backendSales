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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            
            // Número de cotización único
            $table->string('quote_number')->unique()->comment('Número de cotización generado automáticamente');
            
            // Referencias a otras tablas
            $table->unsignedBigInteger('customer_id')->comment('ID del cliente');
            $table->unsignedBigInteger('user_id')->comment('ID del usuario que creó la cotización');
            
            // Campos monetarios
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Subtotal sin impuestos ni descuentos');
            $table->decimal('tax', 15, 2)->default(0)->comment('Impuestos aplicados');
            $table->decimal('discount', 15, 2)->default(0)->comment('Descuento aplicado');
            $table->decimal('total', 15, 2)->default(0)->comment('Total final de la cotización');
            
            // Estado de la cotización
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'expired'])
                  ->default('draft')
                  ->comment('Estado actual de la cotización');
            
            // Campos de texto
            $table->text('notes')->nullable()->comment('Notas adicionales de la cotización');
            $table->longText('terms_conditions')->nullable()->comment('Términos y condiciones');
            
            // Fechas importantes
            $table->datetime('quote_date')->default(now())->comment('Fecha de creación de la cotización');
            $table->date('valid_until')->comment('Fecha de vencimiento de la cotización');
            $table->datetime('sent_at')->nullable()->comment('Fecha cuando se envió la cotización');
            $table->datetime('approved_at')->nullable()->comment('Fecha de aprobación de la cotización');
            
            // Campo JSON para metadatos adicionales
            $table->json('metadata')->nullable()->comment('Datos adicionales en formato JSON');
            
            // Timestamps estándar de Laravel
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index(['customer_id', 'status']);
            $table->index(['user_id', 'quote_date']);
            $table->index(['status', 'valid_until']);
            $table->index('quote_date');
            $table->index('valid_until');
            
            // Claves foráneas
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};