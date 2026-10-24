<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleCycleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Classes $class;
    private Classes $otherClass;
    private Room $room;
    private SystemConfiguration $config;
    private array $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = [];
        foreach (['Waka Kurikulum', 'Guru', 'Guru BK', 'Wali Kelas', 'Siswa', 'Orang Tua'] as $name) {
            $this->roles[$name] = Role::create(['role_name' => $name]);
        }

        $waka = $this->makeUser('Waka', 'waka', 'Waka Kurikulum');
        $this->config = SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ]);

        $this->teacher = $this->makeUser('Kurnia', 'kurnia', 'Guru');
        $this->class = Classes::create(['class_name' => 'X RPL 1']);
        $this->otherClass = Classes::create(['class_name' => 'X RPL 2']);
        $this->room = Room::create(['room_name' => 'Ruang 1']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_teacher_my_schedules_filters_the_requested_cycle_and_serializes_it(): void
    {
        $cycleTwo = $this->makeSchedule($this->teacher, $this->class, '2');
        $this->makeSchedule($this->teacher, $this->class, '1', 'Selasa');
        $otherTeacher = $this->makeUser('Guru Lain', 'guru.lain', 'Guru');
        $this->makeSchedule($otherTeacher, $this->class, '2', 'Rabu');

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/schedules/my?cycle=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cycle', 1)
            ->assertJsonPath('data.0.cycle_type', '1')
            ->assertJsonPath('data.0.day_of_week', 'Selasa');

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/schedules/my?cycle=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cycleTwo->id)
            ->assertJsonPath('data.0.cycle', 2)
            ->assertJsonPath('data.0.teacher.fullname', 'Kurnia');

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/schedules?cycle=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cycleTwo->id);
    }

    public function test_student_only_gets_their_class_and_requested_cycle(): void
    {
        $student = $this->makeUser('Siswa', 'siswa', 'Siswa', $this->class->id);
        $cycleTwo = $this->makeSchedule($this->teacher, $this->class, '2');
        $this->makeSchedule($this->teacher, $this->otherClass, '2');

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->class->id . '&cycle=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->class->id . '&cycle=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cycleTwo->id)
            ->assertJsonPath('data.0.cycle', 2);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->otherClass->id . '&cycle=2')
            ->assertForbidden();
    }

    public function test_homeroom_teacher_is_limited_to_the_assigned_class(): void
    {
        $wali = $this->makeUser('Wali', 'wali', 'Wali Kelas', $this->class->id);
        $assignedClassSchedule = $this->makeSchedule($this->teacher, $this->class, '2');
        $this->makeSchedule($this->teacher, $this->otherClass, '2');
        $waliOwnSchedule = $this->makeSchedule($wali, $this->class, '2', 'Selasa');
        $this->makeSchedule($wali, $this->otherClass, '2', 'Rabu');

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->class->id . '&cycle=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $assignedClassSchedule->id, 'cycle' => 2]);

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->otherClass->id . '&cycle=2')
            ->assertForbidden();

        $this->actingAs($wali, 'sanctum')
            ->getJson('/api/schedules/my?cycle=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $waliOwnSchedule->id);
    }

    public function test_parent_only_gets_schedules_of_linked_children(): void
    {
        $parent = $this->makeUser('Orang Tua', 'ortu', 'Orang Tua');
        $child = $this->makeUser('Anak', 'anak', 'Siswa', $this->class->id, $parent->id);
        $this->assertSame($parent->id, $child->parent_id);

        $childSchedule = $this->makeSchedule($this->teacher, $this->class, '2');
        $this->makeSchedule($this->teacher, $this->otherClass, '2');

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/schedules?cycle=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $childSchedule->id)
            ->assertJsonPath('data.0.cycle', 2);

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/schedules?class_id=' . $this->otherClass->id . '&cycle=2')
            ->assertForbidden();
    }

    public function test_today_uses_the_configured_active_cycle_and_serializes_each_schedule_cycle(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-26 10:00:00'));
        $this->config->update(['active_cycle' => '2']);

        $student = $this->makeUser('Siswa', 'siswa.hari', 'Siswa', $this->class->id);
        $this->makeSchedule($this->teacher, $this->class, '1', 'Senin');
        $cycleTwo = $this->makeSchedule($this->teacher, $this->class, '2', 'Senin');

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/schedules/today?cycle=1')
            ->assertOk()
            ->assertJsonPath('cycle', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cycleTwo->id)
            ->assertJsonPath('data.0.cycle', 2);
    }

    private function makeUser(
        string $fullname,
        string $username,
        string $role,
        ?int $classId = null,
        ?int $parentId = null
    ): User {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roles[$role]->id,
            'class_id' => $classId,
            'parent_id' => $parentId,
        ]);
    }

    private function makeSchedule(User $teacher, Classes $class, string $cycle, string $day = 'Senin'): Schedule
    {
        return Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $this->room->id,
            'subject' => 'Bahasa Indonesia',
            'day_of_week' => $day,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => $cycle,
        ]);
    }
}
