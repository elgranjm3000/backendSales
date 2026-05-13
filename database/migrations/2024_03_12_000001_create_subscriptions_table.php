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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('plan', 50); // trial, monthly, annual, lifetime
            $table->dateTime('starts_at');
            $table->dateTime('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->json('features')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('expires_at');
            $table->index('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
