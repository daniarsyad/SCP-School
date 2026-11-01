<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('journals')
            ->select('schedule_id', 'date')
            ->groupBy('schedule_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException(
                'Cannot add journal uniqueness: duplicate schedule/date journals exist and must be reconciled first.'
            );
        }

        if (DB::table('attendances')
            ->select('journal_id', 'student_id')
            ->groupBy('journal_id', 'student_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException(
                'Cannot add attendance uniqueness: duplicate journal/student attendances exist and must be reconciled first.'
            );
        }

        Schema::table('journals', function (Blueprint $table) {
            $table->text('activity_notes')->nullable();
            $table->string('idempotency_key', 255)->nullable()->unique();
            $table->string('idempotency_hash', 64)->nullable();
            $table->unique(['schedule_id', 'date']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_type', 20)->default('Izin');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('attendance_leave_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->unique()->constrained('attendances')->cascadeOnDelete();
            $table->enum('base_status', ['Hadir', 'Izin', 'Sakit', 'Alpha']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_leave_syncs');

        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasIndex('leave_requests', 'leave_requests_sync_index')) {
                $table->dropIndex('leave_requests_sync_index');
            }
            $table->dropConstrainedForeignId('cancelled_by');
            $columns = ['leave_type', 'rejection_reason'];
            if (Schema::hasColumn('leave_requests', 'cancelled_at')) {
                $columns[] = 'cancelled_at';
            }
            $table->dropColumn($columns);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropUnique(['schedule_id', 'date']);
            $table->dropColumn(['activity_notes', 'idempotency_key', 'idempotency_hash']);
        });
    }
};
