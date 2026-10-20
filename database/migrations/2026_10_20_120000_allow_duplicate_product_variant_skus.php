<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('product_variants', 'product_variants_sku_unique', 'unique')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->dropUnique('product_variants_sku_unique');
            });
        }

        if (! Schema::hasIndex('product_variants', 'product_variants_sku_index')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->index('sku', 'product_variants_sku_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('product_variants', 'product_variants_sku_index')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->dropIndex('product_variants_sku_index');
            });
        }

        if (! Schema::hasIndex('product_variants', 'product_variants_sku_unique', 'unique')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->unique('sku', 'product_variants_sku_unique');
            });
        }
    }
};
