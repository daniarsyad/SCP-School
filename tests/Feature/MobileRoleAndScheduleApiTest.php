<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileRoleAndScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    private Role $wakaRole;

    private Role $guruRole;

    private Role $bkRole;

    private User $waka;

    private Classes $class;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $this->guruRole = Role::create(['role_name' => 'Guru']);
        $this->bkRole = Role::create(['role_name' => 'Guru BK']);
        $this->waka = $this->makeUser('Waka', 'waka', $this->wakaRole, '19770001');
        $this->class = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);
        $this->room = Room::create(['room_name' => 'Ruang 1']);

        SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $this->waka->id,
        ]);
    }

    public function test_teacher_with_additional_waka_role_can_log_in_as_teacher_but_gets_no_waka_api_access(): void
    {
        $teacher = $this->makeUser('Guru', 'guru', $this->guruRole, '19870001', 'Matematika');
        $teacher->additionalRoles()->attach($this->wakaRole);

        $login = $this->postJson('/api/login', [
            'identifier' => $teacher->nip,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'Guru')
            ->assertJsonPath('data.user.additional_roles.0.role_name', 'Waka Kurikulum')
            ->assertJsonPath('data.user.bk_classes', []);

        $this->withToken($login->json('data.token'))
            ->postJson('/api/schedules', $this->schedulePayload($teacher, 'Matematika'))
            ->assertForbidden();

        $this->withToken($login->json('data.token'))
            ->getJson('/api/journals/monitor')
            ->assertForbidden();

        $this->withToken($login->json('data.token'))
            ->getJson('/api/attendances/report/filters')
            ->assertForbidden();
    }

    public function test_primary_waka_role_is_still_blocked_from_mobile_login(): void
    {
        $this->postJson('/api/login', [
            'identifier' => $this->waka->nip,
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Akun Waka hanya bisa login melalui website.');
    }

    public function test_login_and_me_return_consistent_additional_roles_and_all_bk_classes(): void
    {
        $teacher = $this->makeUser('Guru BK', 'guru.bk', $this->bkRole, '19870002');
        $teacher->additionalRoles()->attach($this->wakaRole);
        $otherClass = Classes::create([
            'class_name' => 'X RPL 2',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '2',
        ]);
        $unrelatedClass = Classes::create([
            'class_name' => 'XI RPL 1',
            'grade' => 'XI',
            'major' => 'RPL',
            'section' => '1',
        ]);
        $teacher->bkClasses()->sync([$this->class->id, $otherClass->id]);

        $login = $this->postJson('/api/login', [
            'identifier' => $teacher->nip,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'Guru BK')
            ->assertJsonPath('data.user.additional_roles', [
                ['id' => $this->wakaRole->id, 'role_name' => 'Waka Kurikulum'],
            ])
            ->assertJsonCount(2, 'data.user.bk_classes')
            ->assertJsonPath('data.user.bk_classes.0.class_name', 'X RPL 1')
            ->assertJsonPath('data.user.bk_classes.1.class_name', 'X RPL 2');

        $this->withToken($login->json('data.token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'Guru BK')
            ->assertJsonPath('data.additional_roles', [
                ['id' => $this->wakaRole->id, 'role_name' => 'Waka Kurikulum'],
            ])
            ->assertJsonCount(2, 'data.bk_classes')
            ->assertJsonMissing([
                'id' => $unrelatedClass->id,
                'class_name' => $unrelatedClass->class_name,
            ]);
    }

    public function test_bk_teacher_without_profile_subject_can_store_both_supported_bk_subject_names(): void
    {
        $teacher = $this->makeUser('Guru BK', 'guru.bk', $this->bkRole, '19870003');

        $this->actingAs($this->waka, 'sanctum')
            ->postJson('/api/schedules', $this->schedulePayload($teacher, 'BK', '08:00', '08:35'))
            ->assertCreated()
            ->assertJsonPath('data.subject', 'BK');

        $this->actingAs($this->waka, 'sanctum')
            ->postJson('/api/schedules', $this->schedulePayload($teacher, 'Bimbingan dan Konseling', '08:35', '09:10'))
            ->assertCreated()
            ->assertJsonPath('data.subject', 'Bimbingan dan Konseling');

        $this->assertSame(2, Schedule::count());
    }

    public function test_schedule_api_returns_subject_validation_and_conflict_details_without_false_success(): void
    {
        $teacher = $this->makeUser('Guru', 'guru', $this->guruRole, '19870004', 'Matematika');

        $this->actingAs($this->waka, 'sanctum')
            ->postJson('/api/schedules', $this->schedulePayload($teacher, 'Fisika'))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.subject.0', 'Kolom Pengajar / Mata Pelajaran: Guru mengajar "Matematika", bukan "Fisika".');

        $this->assertSame(0, Schedule::count());

        Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $this->class->id,
            'room_id' => $this->room->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'cycle_type' => '1',
        ]);

        $this->actingAs($this->waka, 'sanctum')
            ->postJson('/api/schedules', $this->schedulePayload($teacher, 'Matematika'))
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('conflict_with.subject', 'Matematika')
            ->assertJsonPath('conflict_with.teacher', 'Guru')
            ->assertJsonPath('conflict_with.time', '08:00 - 09:00');

        $this->assertSame(1, Schedule::count());
    }

    private function schedulePayload(User $teacher, string $subject, string $start = '08:00', string $end = '08:35'): array
    {
        return [
            'teacher_id' => $teacher->id,
            'class_id' => $this->class->id,
            'room_id' => $this->room->id,
            'subject' => $subject,
            'day_of_week' => 'Senin',
            'start_time' => $start,
            'end_time' => $end,
        ];
    }

    private function makeUser(
        string $fullname,
        string $username,
        Role $role,
        string $nip,
        ?string $subject = null
    ): User {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $username.'@example.com',
            'nip' => $nip,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'subject' => $subject,
        ]);
    }
}
