<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->index()->default('new');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method_code')->nullable()->index();
            $table->string('payment_method_name')->nullable();
            $table->string('shipping_method_code')->nullable()->index();
            $table->string('shipping_method_name')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
