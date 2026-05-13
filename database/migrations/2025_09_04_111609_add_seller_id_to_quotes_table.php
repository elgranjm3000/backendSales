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
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_seller_id')->nullable()->after('company_id');
            $table->foreign('user_seller_id')->references('id')->on('users')->nullOnDelete();
            $table->index('user_seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('quotes', function (Blueprint $table) {
        $table->dropForeign(['user_seller_id']);
        $table->dropIndex(['user_seller_id']);
        $table->dropColumn('user_seller_id');
    });
    }
};
