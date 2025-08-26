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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            
            // Referencia a la cotización
            $table->unsignedBigInteger('quote_id')->comment('ID de la cotización');
            
            // Información del producto/servicio
            $table->string('item_type')->default('product')->comment('Tipo de item: product, service, etc.');
            $table->unsignedBigInteger('product_id')->nullable()->comment('ID del producto (si aplica)');
            $table->string('name')->comment('Nombre del producto o servicio');
            $table->text('description')->nullable()->comment('Descripción detallada del item');
            $table->string('unit')->default('pcs')->comment('Unidad de medida (pcs, kg, hrs, etc.)');
            
            // Campos de cantidad y precios
            $table->decimal('quantity', 15, 3)->default(1)->comment('Cantidad del item');
            $table->decimal('unit_price', 15, 2)->default(0)->comment('Precio unitario');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Porcentaje de descuento aplicado');
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('Monto de descuento');
            $table->decimal('tax_percentage', 5, 2)->default(0)->comment('Porcentaje de impuesto');
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('Monto de impuesto');
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Subtotal del item (cantidad × precio unitario)');
            $table->decimal('total', 15, 2)->default(0)->comment('Total del item (subtotal - descuento + impuesto)');
            
            // Orden de los items en la cotización
            $table->integer('sort_order')->default(0)->comment('Orden de visualización en la cotización');
            
            // Metadatos adicionales
            $table->json('metadata')->nullable()->comment('Datos adicionales en formato JSON');
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->index(['quote_id', 'sort_order']);
            $table->index('product_id');
            
            // Claves foráneas
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            // Nota: La clave foránea para product_id se puede agregar si tienes tabla products
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};