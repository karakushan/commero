<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_category_post')
            || ! Schema::hasTable('posts')
            || ! Schema::hasColumn('posts', 'post_category_id')) {
            return;
        }

        DB::table('posts')
            ->whereNotNull('post_category_id')
            ->select(['id', 'post_category_id'])
            ->orderBy('id')
            ->chunkById(500, function ($posts): void {
                $now = now();
                $rows = $posts
                    ->map(fn (object $post): array => [
                        'post_id' => $post->id,
                        'post_category_id' => $post->post_category_id,
                        'sort' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($rows !== []) {
                    DB::table('post_category_post')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        // Keep category links added by this data migration and later edits.
    }
};
