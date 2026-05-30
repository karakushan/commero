<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function jsonColumn(Blueprint $table, string $column): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $table->jsonb($column)->nullable();

            return;
        }

        $table->json($column)->nullable();
    }

    public function up(): void
    {
        Schema::create('city_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('city_categories')->nullOnDelete();
            $table->string('path')->index();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->string('icon_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->timestamps();
        });

        Schema::create('city_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->string('slug');
            $this->jsonColumn($table, 'blocks');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('robots')->nullable();
            $table->timestamps();

            $table->unique(['city_category_id', 'locale']);
            $table->localizedSlugConstraint(['locale', 'slug']);
        });

        Schema::create('category_city_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['city_category_id', 'category_id']);
            $table->index(['city_category_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_city_category');
        Schema::dropIfExists('city_category_translations');
        Schema::dropIfExists('city_categories');
    }
};
