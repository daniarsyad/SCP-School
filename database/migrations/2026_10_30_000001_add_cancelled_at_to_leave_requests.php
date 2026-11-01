<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREVIOUS_MIGRATION = '2026_10_29_000001_extend_journals_and_leave_requests';

    public function up(): void
    {
        if (! Schema::hasColumn('leave_requests', 'cancelled_at')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable();
            });
        }

        if (! Schema::hasIndex('leave_requests', 'leave_requests_sync_index')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->index(
                    ['student_id', 'status_approval', 'cancelled_at', 'start_date', 'end_date'],
                    'leave_requests_sync_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (DB::table('migrations')
            ->where('migration', self::PREVIOUS_MIGRATION)
            ->exists()) {
            return;
        }

        if (Schema::hasIndex('leave_requests', 'leave_requests_sync_index')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropIndex('leave_requests_sync_index');
            });
        }

        if (Schema::hasColumn('leave_requests', 'cancelled_at')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('cancelled_at');
            });
        }
    }
};
