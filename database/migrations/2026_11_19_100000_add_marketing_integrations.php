<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('google_tag_manager_id', 64)->nullable();
            $table->string('google_analytics_id', 64)->nullable();
            $table->string('facebook_pixel_id', 64)->nullable();
            $table->text('head_code')->nullable();
            $table->text('body_code')->nullable();
            $table->boolean('google_shopping_enabled')->default(true);
            $table->unsignedInteger('google_shopping_category_id')->nullable();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('google_shopping_condition', 16)->nullable();
            $table->boolean('google_shopping_exclude')->default(false);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedInteger('google_product_category_id')->nullable();
            $table->boolean('google_shopping_exclude')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['google_tag_manager_id', 'google_analytics_id', 'facebook_pixel_id', 'head_code', 'body_code', 'google_shopping_enabled', 'google_shopping_category_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['google_shopping_condition', 'google_shopping_exclude']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['google_product_category_id', 'google_shopping_exclude']);
        });
    }
};
