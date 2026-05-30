<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_view_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('session_id');
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();

            $table->unique(['product_id', 'session_id']);
            $table->index(['product_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_view_sessions');
    }
};
