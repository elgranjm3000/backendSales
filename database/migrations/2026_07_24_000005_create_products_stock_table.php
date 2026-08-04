<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products_stock', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->string('store');
            $table->string('locations');
            $table->unsignedBigInteger('company_id');
            $table->double('stock')->nullable();
            $table->double('ordered_stock')->default(0);
            $table->double('committed_stock')->default(0);

            $table->primary(['product_id', 'store', 'locations', 'company_id']);

            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_stock');
    }
};
