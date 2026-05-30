<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_hit_sales')->default(false)->after('stock_status');
            $table->boolean('is_on_sale')->default(false)->after('is_hit_sales');
            $table->boolean('is_new')->default(false)->after('is_on_sale');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['is_hit_sales', 'is_on_sale', 'is_new']);
        });
    }
};
