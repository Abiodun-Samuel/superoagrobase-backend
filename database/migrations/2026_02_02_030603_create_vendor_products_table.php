<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_available')->nullable()->default(true);
            $table->timestamps();

            $table->unique(['vendor_id', 'product_id']);
            $table->index(['product_id', 'is_available', 'stock', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_products');
    }
};
