<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('liqpay_public_key')->nullable();
            $table->text('liqpay_private_key')->nullable();
            $table->boolean('liqpay_sandbox')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['liqpay_public_key', 'liqpay_private_key', 'liqpay_sandbox']);
        });
    }
};
