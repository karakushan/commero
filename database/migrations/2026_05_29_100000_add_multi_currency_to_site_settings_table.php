<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('multi_currency_enabled')->default(false)->after('social_links_translations');
            $table->string('country_source')->nullable()->after('multi_currency_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['country_source', 'multi_currency_enabled']);
        });
    }
};
