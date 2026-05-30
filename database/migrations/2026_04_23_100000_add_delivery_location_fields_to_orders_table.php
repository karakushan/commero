<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_city_ref')->nullable()->after('shipping_method_name');
            $table->string('delivery_city_name')->nullable()->after('delivery_city_ref');
            $table->string('delivery_warehouse_ref')->nullable()->after('delivery_city_name');
            $table->string('delivery_warehouse_name')->nullable()->after('delivery_warehouse_ref');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_city_ref',
                'delivery_city_name',
                'delivery_warehouse_ref',
                'delivery_warehouse_name',
            ]);
        });
    }
};
