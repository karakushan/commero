<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->json('country_codes')->nullable()->after('symbol');
        });

        DB::table('currencies')->whereNotNull('country_code')->orderBy('id')->get()
            ->each(function ($row): void {
                $codes = array_filter([$row->country_code ?? null]);
                DB::table('currencies')->where('id', $row->id)->update([
                    'country_codes' => json_encode($codes),
                ]);
            });

        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->after('symbol');
        });

        DB::table('currencies')->whereNotNull('country_codes')->orderBy('id')->get()
            ->each(function ($row): void {
                $codes = json_decode($row->country_codes, true);
                $first = is_array($codes) ? ($codes[0] ?? null) : null;
                DB::table('currencies')->where('id', $row->id)->update([
                    'country_code' => $first,
                ]);
            });

        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn('country_codes');
        });
    }
};
