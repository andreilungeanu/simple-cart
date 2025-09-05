<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('discount_codes');
            $table->json('discount_data')->nullable()->after('shipping_method');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('discount_data');
            $table->json('discount_codes')->nullable()->after('shipping_method');
        });
    }
};
