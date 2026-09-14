<?php

use Commero\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->timestamps();

            $table->unique(['brand_id', 'locale']);
        });

        $defaultLocale = Locales::default();
        $now = now();
        $rows = DB::table('brands')
            ->select(['id', 'name'])
            ->get()
            ->map(fn (object $brand): array => [
                'brand_id' => $brand->id,
                'locale' => $defaultLocale,
                'name' => $brand->name,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('brand_translations')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_translations');
    }
};
