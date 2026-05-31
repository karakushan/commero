<?php

use Commero\Models\Link;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 5)->index();
            $table->string('slug');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->timestamps();

            $table->unique(['locale', 'slug']);
            $table->unique(['locale', 'entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id']);
        });

        $timestamp = now();

        $categoryLinks = DB::table('category_translations')
            ->select('locale', 'slug', 'category_id as entity_id')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->map(fn (object $translation): array => [
                'locale' => $translation->locale,
                'slug' => $translation->slug,
                'entity_type' => Link::ENTITY_CATEGORY,
                'entity_id' => $translation->entity_id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        $pageLinks = DB::table('page_translations')
            ->select('locale', 'slug', 'page_id as entity_id')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->map(fn (object $translation): array => [
                'locale' => $translation->locale,
                'slug' => $translation->slug,
                'entity_type' => Link::ENTITY_PAGE,
                'entity_id' => $translation->entity_id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        DB::table('links')->insert(array_merge($categoryLinks, $pageLinks));
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
