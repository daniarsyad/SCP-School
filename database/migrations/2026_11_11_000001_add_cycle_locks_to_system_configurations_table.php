<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->boolean('is_cycle_1_locked')->default(false)->after('is_schedule_locked');
            $table->boolean('is_cycle_2_locked')->default(false)->after('is_cycle_1_locked');
        });

        if (Schema::hasTable('system_configurations')) {
            $config = DB::table('system_configurations')->first();
            if ($config) {
                $isLocked = (bool) ($config->is_schedule_locked ?? false);
                $activeCycle = (string) ($config->active_cycle ?? '1');

                DB::table('system_configurations')
                    ->where('id', $config->id)
                    ->update([
                        'is_cycle_1_locked' => ($activeCycle === '1' && $isLocked),
                        'is_cycle_2_locked' => ($activeCycle === '2' && $isLocked),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['is_cycle_1_locked', 'is_cycle_2_locked']);
        });
    }
};

