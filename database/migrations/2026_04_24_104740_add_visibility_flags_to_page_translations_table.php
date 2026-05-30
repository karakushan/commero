<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_translations', function (Blueprint $table): void {
            $table->boolean('show_breadcrumbs')->default(true)->after('background_mobile_image');
            $table->boolean('show_title')->default(true)->after('show_breadcrumbs');
        });
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table): void {
            $table->dropColumn([
                'show_breadcrumbs',
                'show_title',
            ]);
        });
    }
};
