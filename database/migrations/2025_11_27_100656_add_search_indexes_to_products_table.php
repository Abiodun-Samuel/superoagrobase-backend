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
        Schema::table('products', function (Blueprint $table) {
            $table->index('sub_title');
            $table->index('keywords');
            $table->index('brands');
            $table->fullText(['title', 'sub_title', 'description', 'keywords', 'brands'], 'products_search_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sub_title']);
            $table->dropIndex(['keywords']);
            $table->dropIndex(['brands']);
            $table->dropFullText('products_search_fulltext');
        });
    }
};
