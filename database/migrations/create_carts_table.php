<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->json('discount_data')->nullable();            
            $table->json('shipping_data')->nullable();
            $table->json('tax_data')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('active'); // Using enum
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();            
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
