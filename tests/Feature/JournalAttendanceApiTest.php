<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class JournalAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];

    private User $teacher;

    private User $wali;

    private User $bk;

    private Classes $class;

    private Room $room;

    private Schedule $schedule;

    private User $student;

    private User $secondStudent;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-26 10:00:00');
        Storage::fake('public');

        foreach (['Guru', 'Wali Kelas', 'Guru BK', 'Siswa', 'Orang Tua', 'Waka Kurikulum'] as $name) {
            $this->roles[$name] = Role::create(['role_name' => $name]);
        }

        $this->class = Classes::create(['class_name' => 'X RPL 1']);
        $this->room = Room::create(['room_name' => 'Ruang 1']);
        $this->teacher = $this->makeUser('Guru', 'guru', 'Guru');
        $this->wali = $this->makeUser('Wali', 'wali', 'Wali Kelas', $this->class->id);
        $this->bk = $this->makeUser('BK', 'bk', 'Guru BK');
        $this->student = $this->makeUser('Siswa Satu', 'siswa1', 'Siswa', $this->class->id);
        $this->secondStudent = $this->makeUser('Siswa Dua', 'siswa2', 'Siswa', $this->class->id);
        SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $this->teacher->id,
        ]);
        $this->schedule = $this->makeSchedule('Senin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_bk_can_view_only_journals_for_their_own_schedules(): void
    {
        $bkSchedule = $this->makeSchedule('Senin', $this->bk);
        $ownJournal = $this->createLegacyJournal($bkSchedule, '2026-10-26');

        $otherTeacher = $this->makeUser('Guru Lain', 'guru_lain_history', 'Guru');
        $otherSchedule = $this->makeSchedule('Senin', $otherTeacher);
        $this->createLegacyJournal($otherSchedule, '2026-10-26');

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/journals/my')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownJournal->id);
    }

    public function test_bk_without_journals_receives_an_empty_history(): void
    {
        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/journals/my')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_combined_journal_saves_all_attendances_and_retries_idempotently(): void
    {
        $photo = UploadedFile::fake()->image('journal.jpg');
        $payload = $this->journalPayload($photo);

        $first = $this->actingAs($this->teacher, 'sanctum')
            ->withHeader('Idempotency-Key', 'final-journal-001')
            ->post('/api/journals', $payload);

        $first->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('replayed', false)
            ->assertJsonCount(2, 'data.attendances')
            ->assertJsonPath('data.attendances.0.status', 'Hadir')
            ->assertJsonPath('data.attendances.1.status', 'Sakit');

        $journalId = $first->json('data.id');
        $this->actingAs($this->teacher, 'sanctum')
            ->withHeader('Idempotency-Key', 'final-journal-001')
            ->post('/api/journals', $payload)
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('data.id', $journalId);

        $this->assertSame(1, Journal::count());
        $this->assertSame(2, Attendance::count());
        $this->assertDatabaseHas('attendances', ['student_id' => $this->secondStudent->id, 'status' => 'Sakit']);
    }

    public function test_legacy_journal_submission_and_attendance_routes_remain_compatible(): void
    {
        $this->makeLeave($this->student, 'Sakit')->update(['status_approval' => 'Approved']);

        $this->actingAs($this->teacher, 'sanctum')
            ->post('/api/journals', [
                'schedule_id' => $this->schedule->id,
                'topic_description' => 'Topik kompatibel',
                'photo' => UploadedFile::fake()->image('journal.jpg'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.attendances.0.status', 'Sakit')
            ->assertJsonPath('data.attendances.1.status', 'Hadir');

        $journalId = Journal::firstOrFail()->id;
        $attendance = Attendance::where('journal_id', $journalId)
            ->where('student_id', $this->secondStudent->id)
            ->firstOrFail();

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/attendances/journal/{$journalId}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/attendances/{$attendance->id}/status", ['status' => 'Alpha'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Alpha');
    }

    public function test_journal_submission_rejects_another_teachers_schedule_and_incomplete_roster(): void
    {
        $otherTeacher = $this->makeUser('Guru Lain', 'guru_lain', 'Guru');
        $otherSchedule = $this->makeSchedule('Senin', $otherTeacher);

        $this->actingAs($this->teacher, 'sanctum')
            ->post('/api/journals', $this->journalPayload(UploadedFile::fake()->image('journal.jpg'), $otherSchedule->id))
            ->assertForbidden();

        $payload = $this->journalPayload(UploadedFile::fake()->image('journal.jpg'));
        $payload['attendances'] = [
            ['student_id' => $this->student->id, 'status' => 'Hadir'],
        ];

        $this->actingAs($this->teacher, 'sanctum')
            ->post('/api/journals', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendances');

        $this->assertSame(0, Journal::count());
        $this->assertSame(0, Attendance::count());
    }

    public function test_database_failure_rolls_back_journal_and_removes_uploaded_photo(): void
    {
        $created = 0;
        Event::listen('eloquent.creating: '.Attendance::class, function () use (&$created) {
            $created++;
            if ($created === 2) {
                throw new RuntimeException('Simulated attendance write failure');
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->teacher, 'sanctum')
                ->post('/api/journals', $this->journalPayload(UploadedFile::fake()->image('journal.jpg')));
            $this->fail('The simulated database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated attendance write failure', $exception->getMessage());
        }

        $this->assertSame(0, Journal::count());
        $this->assertSame(0, Attendance::count());
        $this->assertSame([], Storage::disk('public')->allFiles('journals'));
    }

    public function test_only_homeroom_teacher_can_verify_and_rejection_requires_reason(): void
    {
        $leave = $this->makeLeave($this->student, 'Izin');
        $otherClass = Classes::create(['class_name' => 'X RPL 2']);
        $otherStudent = $this->makeUser('Siswa Kelas Lain', 'siswa_lain', 'Siswa', $otherClass->id);
        $otherLeave = $this->makeLeave($otherStudent, 'Izin');

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/leave-requests/pending')
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->actingAs($this->wali, 'sanctum')
            ->getJson('/api/leave-requests/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->actingAs($this->bk, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/verify", ['status_approval' => 'Approved'])
            ->assertForbidden();

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/verify", ['status_approval' => 'Rejected'])
            ->assertForbidden();
        $this->actingAs($this->wali, 'sanctum')
            ->postJson("/api/leave-requests/{$otherLeave->id}/verify", ['status_approval' => 'Approved'])
            ->assertForbidden();

        $this->actingAs($this->wali, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/verify", ['status_approval' => 'Rejected'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rejection_reason');

        $this->actingAs($this->wali, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/verify", [
                'status_approval' => 'Rejected',
                'rejection_reason' => 'Dokumen tidak valid',
            ])
            ->assertOk()
            ->assertJsonPath('data.rejection_reason', 'Dokumen tidak valid');

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/cancel")
            ->assertConflict();
    }

    public function test_pending_excludes_cancelled_requests_and_keeps_leave_response_fields(): void
    {
        $active = $this->makeLeave($this->student, 'Sakit');
        $cancelled = $this->makeLeave($this->secondStudent, 'Izin');
        $cancelled->update(['cancelled_at' => now(), 'cancelled_by' => $this->secondStudent->id]);

        $this->actingAs($this->wali, 'sanctum')
            ->getJson('/api/leave-requests/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.leave_type', 'Sakit')
            ->assertJsonPath('data.0.cancelled_at', null)
            ->assertJsonMissing(['id' => $cancelled->id]);

        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/leave-requests/my')
            ->assertOk()
            ->assertJsonPath('data.0.leave_type', 'Sakit')
            ->assertJsonPath('data.0.rejection_reason', null)
            ->assertJsonPath('data.0.cancelled_at', null);

        $this->actingAs($this->secondStudent, 'sanctum')
            ->getJson('/api/leave-requests/my')
            ->assertOk()
            ->assertJsonPath('data.0.id', $cancelled->id)
            ->assertJsonPath('data.0.cancelled_at', $cancelled->fresh()->cancelled_at->toJSON());
    }

    public function test_only_applicant_or_subject_student_can_cancel_an_active_request(): void
    {
        $leave = $this->makeLeave($this->student, 'Izin');
        $otherStudent = $this->makeUser('Siswa Lain', 'siswa_lain_cancel', 'Siswa', $this->class->id);

        $this->actingAs($otherStudent, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/cancel")
            ->assertForbidden();

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/cancel")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_approval', 'Pending')
            ->assertJsonPath('data.leave_type', 'Izin')
            ->assertJsonPath('data.rejection_reason', null)
            ->assertJsonPath('data.cancelled_by', $this->student->id);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/cancel")
            ->assertConflict();
    }

    public function test_approved_multiday_leave_updates_existing_attendances_and_cancellation_restores_base_status(): void
    {
        $mondayJournal = $this->createLegacyJournal($this->schedule, '2026-10-26');
        $tuesdaySchedule = $this->makeSchedule('Selasa');
        $tuesdayJournal = $this->createLegacyJournal($tuesdaySchedule, '2026-10-27');

        $izinLeave = $this->makeLeave($this->student, 'Izin', '2026-10-26', '2026-10-27');
        $sakitLeave = $this->makeLeave($this->student, 'Sakit', '2026-10-26', '2026-10-27');
        $this->assertSame('2026-10-26', $sakitLeave->start_date->toDateString());
        $this->assertSame(
            2,
            Attendance::where('student_id', $this->student->id)
                ->whereDate('date', '>=', '2026-10-26')
                ->whereDate('date', '<=', '2026-10-27')
                ->count()
        );
        $this->actingAs($this->wali, 'sanctum')
            ->postJson("/api/leave-requests/{$izinLeave->id}/verify", ['status_approval' => 'Approved'])
            ->assertOk();
        $this->actingAs($this->wali, 'sanctum')
            ->postJson("/api/leave-requests/{$sakitLeave->id}/verify", ['status_approval' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status_approval', 'Approved');
        $this->assertDatabaseCount('attendance_leave_syncs', 2);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $mondayJournal->id,
            'student_id' => $this->student->id,
            'status' => 'Sakit',
        ]);
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $tuesdayJournal->id,
            'student_id' => $this->student->id,
            'status' => 'Sakit',
        ]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/leave-requests/{$sakitLeave->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.cancelled_by', $this->student->id);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $mondayJournal->id,
            'student_id' => $this->student->id,
            'status' => 'Izin',
        ]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/leave-requests/{$izinLeave->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $mondayJournal->id,
            'student_id' => $this->student->id,
            'status' => 'Hadir',
        ]);
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $tuesdayJournal->id,
            'student_id' => $this->student->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_report_preserves_date_contract_and_adds_monthly_summary(): void
    {
        $this->createLegacyJournal($this->schedule, '2026-10-26');

        $this->actingAs($this->wali, 'sanctum')
            ->getJson('/api/attendances/report?date=2026-10-26')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-26')
            ->assertJsonPath('data.0.journal.schedule.class.homeroom_teacher.id', $this->wali->id)
            ->assertJsonPath('data.0.journal.schedule.class.homeroom_teacher.fullname', 'Wali')
            ->assertJsonPath('summary.total_students', 2)
            ->assertJsonPath('summary.hadir', 2);

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/attendances/report?month=2026-10&class_id='.$this->class->id)
            ->assertOk()
            ->assertJsonPath('from_date', '2026-10-01')
            ->assertJsonPath('to_date', '2026-10-31')
            ->assertJsonPath('summary.total_students', 2)
            ->assertJsonPath('summary.present_percentage', 100);

        $this->actingAs($this->teacher, 'sanctum')
            ->get('/api/attendances/report/export?month=2026-10')
            ->assertForbidden();

        $export = $this->actingAs($this->wali, 'sanctum')
            ->get('/api/attendances/report/export?month=2026-10');
        $export->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString(
            'rekap-presensi-2026-10-01-2026-10-31.xlsx',
            $export->baseResponse->headers->get('Content-Disposition')
        );

        ob_start();
        $export->baseResponse->sendContent();
        $xlsx = ob_get_clean();
        $this->assertStringStartsWith('PK', $xlsx);

        $otherClass = Classes::create(['class_name' => 'X RPL 2']);
        $this->actingAs($this->wali, 'sanctum')
            ->getJson('/api/attendances/report?class_id='.$otherClass->id)
            ->assertForbidden();
    }

    public function test_bk_report_succeeds_for_a_class_without_a_homeroom_teacher(): void
    {
        $classWithoutWali = Classes::create(['class_name' => 'X RPL 2']);
        $student = $this->makeUser('Siswa Kelas Dua', 'siswa_kelas_dua', 'Siswa', $classWithoutWali->id);
        $schedule = $this->makeSchedule('Senin', null, $classWithoutWali);
        $journal = $this->createLegacyJournalForStudent($schedule, '2026-10-26', $student);

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/attendances/report?date=2026-10-26&class_id='.$classWithoutWali->id)
            ->assertOk()
            ->assertJsonPath('data.0.journal.schedule.class.class_name', 'X RPL 2')
            ->assertJsonPath('data.0.journal.schedule.class.homeroom_teacher', null)
            ->assertJsonPath('data.0.journal.id', $journal->id);
    }

    public function test_monitoring_is_restricted_and_reports_only_submitted_journals(): void
    {
        $journal = $this->createLegacyJournal($this->schedule, '2026-10-26');
        $waka = $this->makeUser('Waka', 'waka', 'Waka Kurikulum');

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/journals/monitor')
            ->assertForbidden();

        $this->actingAs($waka, 'sanctum')
            ->getJson('/api/journals/monitor?from_date=2026-10-26&to_date=2026-10-26')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.journal_id', $journal->id)
            ->assertJsonPath('data.0.attendance_complete', true);
    }

    public function test_bk_report_flags_alpha_streaks_longer_than_three_calendar_days(): void
    {
        foreach (['2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29'] as $date) {
            $journal = $this->createLegacyJournal($this->schedule, $date);
            Attendance::where('journal_id', $journal->id)
                ->where('student_id', $this->student->id)
                ->update(['status' => 'Alpha']);
        }

        $this->actingAs($this->bk, 'sanctum')
            ->getJson('/api/attendances/report?from_date=2026-10-26&to_date=2026-10-29')
            ->assertOk()
            ->assertJsonFragment([
                'alpha_consecutive_days' => 4,
                'max_alpha_consecutive_days' => 4,
                'repeated_alpha' => true,
            ]);
    }

    private function journalPayload(UploadedFile $photo, ?int $scheduleId = null): array
    {
        return [
            'schedule_id' => $scheduleId ?? $this->schedule->id,
            'topic_description' => 'Persamaan Linear',
            'activity_notes' => 'Diskusi dan latihan',
            'photo' => $photo,
            'attendances' => [
                ['student_id' => $this->student->id, 'status' => 'Hadir'],
                ['student_id' => $this->secondStudent->id, 'status' => 'Sakit'],
            ],
        ];
    }

    private function makeUser(
        string $fullname,
        string $username,
        string $role,
        ?int $classId = null
    ): User {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roles[$role]->id,
            'class_id' => $classId,
        ]);
    }

    private function makeSchedule(string $day, ?User $teacher = null, ?Classes $class = null): Schedule
    {
        return Schedule::create([
            'teacher_id' => ($teacher ?? $this->teacher)->id,
            'class_id' => ($class ?? $this->class)->id,
            'room_id' => $this->room->id,
            'subject' => 'Bahasa Indonesia',
            'day_of_week' => $day,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => '1',
        ]);
    }

    private function makeLeave(
        User $student,
        string $type,
        string $startDate = '2026-10-26',
        string $endDate = '2026-10-26'
    ): LeaveRequest {
        return LeaveRequest::create([
            'student_id' => $student->id,
            'applicant_id' => $student->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Tidak masuk sekolah',
            'leave_type' => $type,
            'document_path' => '',
            'status_approval' => 'Pending',
        ]);
    }

    private function createLegacyJournal(Schedule $schedule, string $date): Journal
    {
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $date,
            'topic_description' => 'Topik',
            'photo_path' => 'journals/test.jpg',
            'created_at' => now(),
        ]);

        foreach ([$this->student, $this->secondStudent] as $student) {
            Attendance::create([
                'journal_id' => $journal->id,
                'student_id' => $student->id,
                'status' => 'Hadir',
                'date' => $date,
            ]);
        }

        return $journal;
    }

    private function createLegacyJournalForStudent(Schedule $schedule, string $date, User $student): Journal
    {
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $date,
            'topic_description' => 'Topik',
            'photo_path' => 'journals/test.jpg',
            'created_at' => now(),
        ]);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'date' => $date,
        ]);

        return $journal;
    }
}
