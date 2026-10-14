<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('merchant_return_link')->nullable();

            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('merchant_return_link_translations')->nullable();

                return;
            }

            $table->json('merchant_return_link_translations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'merchant_return_link',
                'merchant_return_link_translations',
            ]);
        });
    }
};
