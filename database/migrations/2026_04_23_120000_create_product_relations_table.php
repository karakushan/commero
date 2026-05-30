<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'type'], 'product_relations_unique');
            $table->index(['product_id', 'type', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
    }
};
