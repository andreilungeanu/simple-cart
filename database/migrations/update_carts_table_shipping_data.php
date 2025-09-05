<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('shipping_method');
            $table->json('shipping_data')->nullable()->after('tax_zone');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('shipping_data');
            $table->string('shipping_method')->nullable()->after('tax_zone');
        });
    }
};
