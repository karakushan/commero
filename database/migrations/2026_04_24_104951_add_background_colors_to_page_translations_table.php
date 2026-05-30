<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_translations', function (Blueprint $table): void {
            $table->string('background_desktop_color')->nullable()->after('background_mobile_image');
            $table->string('background_mobile_color')->nullable()->after('background_desktop_color');
        });
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table): void {
            $table->dropColumn([
                'background_desktop_color',
                'background_mobile_color',
            ]);
        });
    }
};
