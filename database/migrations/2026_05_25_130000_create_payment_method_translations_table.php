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
        Schema::create('payment_method_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['payment_method_id', 'locale']);
        });

        $defaultLocale = Locales::default();
        $now = now();
        $rows = DB::table('payment_methods')
            ->select(['id', 'name', 'description'])
            ->get()
            ->map(fn (object $paymentMethod): array => [
                'payment_method_id' => $paymentMethod->id,
                'locale' => $defaultLocale,
                'name' => $paymentMethod->name,
                'description' => $paymentMethod->description,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('payment_method_translations')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_translations');
    }
};
