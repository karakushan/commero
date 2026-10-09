<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku')->nullable()->change();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('sku')->nullable()->change();
        });
    }

    public function down(): void
    {
        $this->restoreRequiredSkus('products', 'PRODUCT');
        $this->restoreRequiredSkus('product_variants', 'VARIANT');

        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku')->nullable(false)->change();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('sku')->nullable(false)->change();
        });
    }

    private function restoreRequiredSkus(string $table, string $prefix): void
    {
        DB::table($table)
            ->whereNull('sku')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id) use ($table, $prefix): void {
                DB::table($table)
                    ->where('id', $id)
                    ->update(['sku' => $prefix.'-'.Str::uuid()]);
            });
    }
};
