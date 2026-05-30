<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_method_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['shipping_method_id', 'locale']);
        });

        $defaultLocale = Locales::default();
        $now = now();
        $rows = DB::table('shipping_methods')
            ->select(['id', 'name', 'description'])
            ->get()
            ->map(fn (object $shippingMethod): array => [
                'shipping_method_id' => $shippingMethod->id,
                'locale' => $defaultLocale,
                'name' => $shippingMethod->name,
                'description' => $shippingMethod->description,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('shipping_method_translations')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_translations');
    }
};
