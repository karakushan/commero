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
            $table->dropUnique('product_variant_attribute_unique');
        });

        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'create unique index product_variant_attribute_scalar_unique '
                .'on product_attribute_values (product_id, coalesce(variant_id, 0), attribute_id) '
                .'where value_option_id is null'
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('drop index if exists product_variant_attribute_scalar_unique');
        }

        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->unique(
                ['product_id', 'variant_id', 'attribute_id'],
                'product_variant_attribute_unique',
            );
        });
    }
};
