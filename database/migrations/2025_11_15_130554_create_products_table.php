<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Relationships
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('subcategory_id')
                ->nullable()
                ->constrained('subcategories')
                ->nullOnDelete();

            // Slug + Titles
            $table->string('slug')->unique();
            $table->string('title')->index();
            $table->string('sub_title')->nullable();
            $table->string('keywords')->nullable();

            // Description fields
            $table->longText('description')->nullable();
            $table->longText('ingredients')->nullable();

            // Metadata
            $table->boolean('is_featured')->default(false);

            // Brand + Image
            $table->string('brands')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();

            // Tracking Counts
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('sales_count')->default(0);

            // Status
            $table->enum('status', Status::values())
                ->default(Status::IN_STOCK->value)
                ->index();

            // Packaging & Pricing
            $table->string('pack_size')->nullable();

            // Prices
            $table->decimal('price', 12, 2)->index();
            $table->decimal('discount_price', 12, 2)->nullable();

            // Inventory
            $table->unsignedBigInteger('stock')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
