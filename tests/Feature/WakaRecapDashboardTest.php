<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\CycleHistory;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WakaRecapDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-26 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_shows_today_percentages_and_refreshes_the_page(): void
    {
        [$waka, $schedule, $student] = $this->makeScenario();
        $journal = $this->createJournal($schedule, '2026-10-26');
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'date' => '2026-10-26',
        ]);

        $this->actingAs($waka)
            ->get('/waka/dashboard')
            ->assertOk()
            ->assertSee('Jurnal Terisi Hari Ini')
            ->assertSee('Kehadiran Hari Ini')
            ->assertSee('100%')
            ->assertSee('window.location.reload()', false);
    }

    public function test_journal_recap_filters_by_selected_date_grade_class_rombel_and_teacher(): void
    {
        [$waka, $schedule] = $this->makeScenario();
        $this->createJournal($schedule, '2026-10-26');

        $this->actingAs($waka)
            ->get('/waka/journal-recap?date=2026-10-26&grade=X&major=RPL&section=1&class_id='.$schedule->class_id.'&teacher_id='.$schedule->teacher_id)
            ->assertOk()
            ->assertSee('Senin, 2026-10-26')
            ->assertSee('Guru Rekap')
            ->assertSee('X RPL 1')
            ->assertSee('Sudah Mengisi Jurnal');
    }

    public function test_cycle_history_filters_attendance_and_journals_by_date_class_grade_rombel_and_teacher(): void
    {
        [$waka, $schedule, $student] = $this->makeScenario();
        $journal = $this->createJournal($schedule, '2026-10-26');
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'date' => '2026-10-26',
        ]);
        $history = CycleHistory::create([
            'cycle_type' => '1',
            'start_date' => '2026-10-26',
            'end_date' => '2026-11-08',
            'closed_by' => $waka->id,
        ]);
        $query = http_build_query([
            'history_id' => $history->id,
            'date' => '2026-10-26',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
            'class_id' => $schedule->class_id,
            'teacher_id' => $schedule->teacher_id,
        ]);

        $this->actingAs($waka)
            ->get('/waka/cycle-history?'.$query)
            ->assertOk()
            ->assertSee('Semua Tanggal')
            ->assertSee('X RPL 1')
            ->assertSee('2026-10-26')
            ->assertSee('Hadir');

        $this->actingAs($waka)
            ->get('/waka/cycle-history?'.$query.'&tab=jurnal')
            ->assertOk()
            ->assertSee('Materi rekap')
            ->assertSee('Guru Rekap');
    }

    private function makeScenario(): array
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $teacherRole = Role::create(['role_name' => 'Guru']);
        $studentRole = Role::create(['role_name' => 'Siswa']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $teacher = $this->makeUser('Guru Rekap', 'guru.rekap', $teacherRole);
        $class = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);
        $room = Room::create(['room_name' => 'Ruang Rekap']);
        $student = $this->makeUser('Siswa Rekap', 'siswa.rekap', $studentRole, $class->id);
        SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ]);
        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '08:35',
            'cycle_type' => '1',
        ]);

        return [$waka, $schedule, $student];
    }

    private function makeUser(string $fullname, string $username, Role $role, ?int $classId = null): User
    {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'class_id' => $classId,
        ]);
    }

    private function createJournal(Schedule $schedule, string $date): Journal
    {
        return Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $date,
            'topic_description' => 'Materi rekap',
            'photo_path' => 'journal.jpg',
        ]);
    }
}
