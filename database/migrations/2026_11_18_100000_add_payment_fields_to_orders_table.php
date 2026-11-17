<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_status')->nullable()->index()->after('payment_method_name');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('paid_at');
            $table->jsonb('payment_payload')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'paid_at', 'payment_reference', 'payment_payload']);
        });
    }
};
