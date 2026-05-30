<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_street')->nullable()->after('delivery_warehouse_name');
            $table->string('delivery_house')->nullable()->after('delivery_street');
            $table->string('delivery_apartment')->nullable()->after('delivery_house');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_street',
                'delivery_house',
                'delivery_apartment',
            ]);
        });
    }
};
