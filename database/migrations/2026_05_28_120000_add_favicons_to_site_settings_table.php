<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('favicon_svg_path')->nullable()->after('logo_path_translations');
            $table->string('favicon_png_path')->nullable()->after('favicon_svg_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['favicon_svg_path', 'favicon_png_path']);
        });
    }
};
