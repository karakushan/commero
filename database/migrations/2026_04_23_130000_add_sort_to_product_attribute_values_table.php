<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->unsignedInteger('sort')->default(0)->after('value_json');
            $table->index(['product_id', 'variant_id', 'sort'], 'product_attribute_values_product_variant_sort_index');
        });

        DB::table('product_attribute_values')->update([
            'sort' => DB::raw('(select coalesce(attributes.sort, 0) from attributes where attributes.id = product_attribute_values.attribute_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->dropIndex('product_attribute_values_product_variant_sort_index');
            $table->dropColumn('sort');
        });
    }
};
