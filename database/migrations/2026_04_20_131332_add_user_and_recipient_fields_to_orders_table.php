<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->boolean('has_other_recipient')->default(false)->after('customer_email');
            $table->string('recipient_first_name')->nullable()->after('has_other_recipient');
            $table->string('recipient_last_name')->nullable()->after('recipient_first_name');
            $table->string('recipient_phone')->nullable()->after('recipient_last_name');
            $table->string('recipient_email')->nullable()->after('recipient_phone');
        });

        DB::table('orders')
            ->select('id', 'customer_email')
            ->whereNull('user_id')
            ->whereNotNull('customer_email')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                $emails = collect($orders)
                    ->pluck('customer_email')
                    ->filter()
                    ->unique()
                    ->values();

                if ($emails->isEmpty()) {
                    return;
                }

                $usersByEmail = DB::table('users')
                    ->whereIn('email', $emails)
                    ->pluck('id', 'email');

                foreach ($orders as $order) {
                    $userId = $usersByEmail[$order->customer_email] ?? null;

                    if ($userId === null) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'has_other_recipient',
                'recipient_first_name',
                'recipient_last_name',
                'recipient_phone',
                'recipient_email',
            ]);
        });
    }
};
