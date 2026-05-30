<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_translations', function (Blueprint $table): void {
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('blocks')->nullable()->after('slug');

                return;
            }

            $table->json('blocks')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('category_translations', function (Blueprint $table): void {
            $table->dropColumn('blocks');
        });
    }
};
