<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('tax_zone');
            $table->json('tax_data')->nullable()->after('shipping_data');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('tax_data');
            $table->string('tax_zone')->nullable()->after('shipping_data');
        });
    }
};
