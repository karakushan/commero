<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Number of payments chosen for payment in parts (PrivatBank / Monobank installments).
            $table->unsignedTinyInteger('payment_parts')->nullable()->after('payment_reference');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('privat_payparts_store_id')->nullable();
            $table->text('privat_payparts_password')->nullable();
            $table->boolean('privat_payparts_hold')->default(true);
            $table->unsignedTinyInteger('privat_payparts_max_parts')->nullable();
            $table->text('monobank_parts_store_id')->nullable();
            $table->text('monobank_parts_secret')->nullable();
            $table->boolean('monobank_parts_sandbox')->default(false);
            $table->unsignedTinyInteger('monobank_parts_max_parts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('payment_parts'));

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'privat_payparts_store_id', 'privat_payparts_password', 'privat_payparts_hold', 'privat_payparts_max_parts',
                'monobank_parts_store_id', 'monobank_parts_secret', 'monobank_parts_sandbox', 'monobank_parts_max_parts',
            ]);
        });
    }
};
