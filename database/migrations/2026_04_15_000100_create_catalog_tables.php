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
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('path')->index();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['category_id', 'locale']);
            $table->localizedSlugConstraint(['locale', 'slug']);
        });

        Schema::create('attribute_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('attribute_groups')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('value_type')->index();
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variant_axis')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'sort']);
            $table->index(['is_filterable', 'value_type']);
        });

        Schema::create('attribute_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->timestamps();

            $table->unique(['attribute_id', 'locale']);
        });

        Schema::create('attribute_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('attribute_option_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_option_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('label');
            $table->timestamps();

            $table->unique(['attribute_option_id', 'locale']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index()->default('simple');
            $table->string('status')->index()->default('draft');
            $table->string('sku')->unique();
            $this->jsonColumn($table, 'attribute_snapshot');
            $table->text('search_text')->nullable();
            $table->timestamps();

            $table->index(['status', 'brand_id']);
            $table->index(['type', 'status']);
        });

        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->localizedSlugConstraint(['locale', 'slug']);
            $table->index(['locale', 'name']);
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('old_price', 12, 2)->nullable();
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('status')->default('draft');
            $this->jsonColumn($table, 'option_snapshot');
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('product_category', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['product_id', 'category_id']);
            $table->index(['category_id', 'product_id']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value_string')->nullable();
            $table->integer('value_integer')->nullable();
            $table->decimal('value_numeric', 12, 3)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->foreignId('value_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $this->jsonColumn($table, 'value_json');
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'attribute_id'], 'product_variant_attribute_unique');
            $table->index(['attribute_id', 'value_option_id']);
            $table->index(['attribute_id', 'value_string']);
            $table->index(['attribute_id', 'value_integer']);
            $table->index(['attribute_id', 'value_numeric']);
            $table->index('variant_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('create index products_attribute_snapshot_gin on products using gin (attribute_snapshot)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('drop index if exists products_attribute_snapshot_gin');
        }

        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_option_translations');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_translations');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_groups');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
