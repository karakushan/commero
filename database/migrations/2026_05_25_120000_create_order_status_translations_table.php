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
        Schema::create('order_status_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_status_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->timestamps();

            $table->unique(['order_status_id', 'locale']);
        });

        $defaultLocale = Locales::default();
        $now = now();
        $rows = DB::table('order_statuses')
            ->select(['id', 'name'])
            ->get()
            ->map(fn (object $status): array => [
                'order_status_id' => $status->id,
                'locale' => $defaultLocale,
                'name' => $status->name,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('order_status_translations')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_translations');
    }
};
