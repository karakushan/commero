<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->boolean('is_priority')->default(false)->after('sort');
            $table->index(['product_id', 'variant_id', 'is_priority'], 'product_attribute_values_product_variant_priority_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->dropIndex('product_attribute_values_product_variant_priority_index');
            $table->dropColumn('is_priority');
        });
    }
};
