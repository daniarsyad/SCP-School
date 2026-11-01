<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveRequestCancellationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_at_migration_adds_and_rolls_back_the_nullable_timestamp(): void
    {
        Schema::table('leave_requests', function ($table) {
            if (Schema::hasIndex('leave_requests', 'leave_requests_sync_index')) {
                $table->dropIndex('leave_requests_sync_index');
            }

            if (Schema::hasColumn('leave_requests', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });

        DB::table('migrations')
            ->whereIn('migration', [
                '2026_10_29_000001_extend_journals_and_leave_requests',
                '2026_10_30_000001_add_cancelled_at_to_leave_requests',
            ])
            ->delete();

        $migration = require database_path('migrations/2026_10_30_000001_add_cancelled_at_to_leave_requests.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('leave_requests', 'cancelled_at'));
        $column = collect(Schema::getColumns('leave_requests'))
            ->firstWhere('name', 'cancelled_at');
        $this->assertContains(strtolower($column['type_name']), ['datetime', 'timestamp']);
        $this->assertTrue($column['nullable']);

        $migration->down();

        $this->assertFalse(Schema::hasColumn('leave_requests', 'cancelled_at'));
        $this->assertFalse(Schema::hasIndex('leave_requests', 'leave_requests_sync_index'));

        $migration->up();
    }
}
