<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('stock_status')->nullable()->default(null)->change();
        });

        DB::table('products')->update(['stock_status' => null]);
    }

    public function down(): void
    {
        DB::table('products')->whereNull('stock_status')->update(['stock_status' => 'always_in_stock']);

        Schema::table('products', function (Blueprint $table): void {
            $table->string('stock_status')->nullable(false)->default('always_in_stock')->change();
        });
    }
};
