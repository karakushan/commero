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
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('logo_path')->nullable();
            $this->jsonColumn($table, 'contacts');
            $this->jsonColumn($table, 'social_links');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
