<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 50);
            $table->string('status', 50)->default('new');
            $table->string('subject')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 5)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->json('form_data')->nullable();
            $table->json('client_meta')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['product_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leads');
    }
};
