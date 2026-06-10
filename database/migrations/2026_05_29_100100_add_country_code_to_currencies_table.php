<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->after('symbol');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn('country_code');
        });
    }
};
