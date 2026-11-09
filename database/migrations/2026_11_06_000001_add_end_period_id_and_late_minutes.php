<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('schedules', 'end_period_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->foreignId('end_period_id')
                    ->nullable()
                    ->after('period_id')
                    ->constrained('periods')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('journals', 'late_minutes')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->integer('late_minutes')
                    ->default(0)
                    ->after('photo_path');
            });
        }

        // Sinkronisasi data end_period_id untuk semua jadwal yang ada
        $periods = DB::table('periods')->where('is_break', false)->get();
        $schedules = DB::table('schedules')->whereNull('end_period_id')->get();

        foreach ($schedules as $schedule) {
            $endTime = substr((string)$schedule->end_time, 0, 5);
            $startPeriod = $periods->firstWhere('id', $schedule->period_id);
            $dayGroup = $startPeriod?->day_group;

            $matchedPeriod = null;
            if ($dayGroup) {
                $matchedPeriod = $periods->first(function ($p) use ($endTime, $dayGroup) {
                    return substr((string)$p->end_time, 0, 5) === $endTime && $p->day_group === $dayGroup;
                });
            }

            if (!$matchedPeriod) {
                $matchedPeriod = $periods->first(function ($p) use ($endTime) {
                    return substr((string)$p->end_time, 0, 5) === $endTime;
                });
            }

            $endPeriodId = $matchedPeriod ? $matchedPeriod->id : $schedule->period_id;
            DB::table('schedules')->where('id', $schedule->id)->update([
                'end_period_id' => $endPeriodId,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schedules', 'end_period_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropForeign(['end_period_id']);
                $table->dropColumn('end_period_id');
            });
        }

        if (Schema::hasColumn('journals', 'late_minutes')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('late_minutes');
            });
        }
    }
};

