<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('items');
            $table->json('discounts')->nullable();
            $table->json('notes')->nullable();
            $table->json('extra_costs')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_vat_rate', 8, 4)->nullable();
            $table->boolean('shipping_vat_included')->default(false);
            $table->string('tax_zone')->nullable();
            $table->boolean('vat_exempt')->default(false);
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
