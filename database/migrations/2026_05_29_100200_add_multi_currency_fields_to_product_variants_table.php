<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('multi_currency_code', 3)->nullable()->after('old_price');
            $table->decimal('multi_currency_price', 12, 2)->nullable()->after('multi_currency_code');
            $table->decimal('multi_currency_old_price', 12, 2)->nullable()->after('multi_currency_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['multi_currency_old_price', 'multi_currency_price', 'multi_currency_code']);
        });
    }
};
