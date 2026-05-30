<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('full_description');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('robots')->default('index, follow')->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'robots']);
        });
    }
};
