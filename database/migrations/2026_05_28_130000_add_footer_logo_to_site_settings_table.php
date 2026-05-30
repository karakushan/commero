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
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('footer_logo_path')->nullable()->after('logo_path_translations');
            $this->jsonColumn($table, 'footer_logo_path_translations');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'footer_logo_path',
                'footer_logo_path_translations',
            ]);
        });
    }
};
