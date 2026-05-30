<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', 'notifications')
            ->where('column_name', 'data')
            ->value('udt_name');

        if ($columnType === 'jsonb') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE notifications
            ALTER COLUMN data TYPE jsonb
            USING CASE
                WHEN data IS NULL OR btrim(data::text) = '' THEN '{}'::jsonb
                ELSE data::jsonb
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }
};
