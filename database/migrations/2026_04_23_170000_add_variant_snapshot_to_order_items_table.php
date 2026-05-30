<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('product_name')->nullable()->after('variant_id');
            $table->string('product_sku')->nullable()->after('product_name');
            $table->string('variant_name')->nullable()->after('product_sku');
            $table->string('variant_sku')->nullable()->after('variant_name');
            $table->json('variant_attributes')->nullable()->after('variant_sku');
            $table->decimal('unit_price', 12, 2)->default(0)->after('variant_attributes');
            $table->decimal('old_price', 12, 2)->nullable()->after('unit_price');

            $table->index(['order_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['order_id', 'variant_id']);
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn([
                'product_name',
                'product_sku',
                'variant_name',
                'variant_sku',
                'variant_attributes',
                'unit_price',
                'old_price',
            ]);
        });
    }
};
