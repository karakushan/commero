<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('delivery_shipping_method_id')->nullable()->after('birthday');
            $table->string('delivery_city_ref')->nullable()->after('delivery_shipping_method_id');
            $table->string('delivery_city_name')->nullable()->after('delivery_city_ref');
            $table->string('delivery_warehouse_ref')->nullable()->after('delivery_city_name');
            $table->string('delivery_warehouse_name')->nullable()->after('delivery_warehouse_ref');
            $table->string('delivery_street')->nullable()->after('delivery_warehouse_name');
            $table->string('delivery_house')->nullable()->after('delivery_street');
            $table->string('delivery_apartment')->nullable()->after('delivery_house');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_shipping_method_id',
                'delivery_city_ref',
                'delivery_city_name',
                'delivery_warehouse_ref',
                'delivery_warehouse_name',
                'delivery_street',
                'delivery_house',
                'delivery_apartment',
            ]);
        });
    }
};
