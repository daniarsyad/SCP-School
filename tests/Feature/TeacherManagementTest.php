<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_bk_can_be_assigned_and_updated_with_multiple_classes(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $bkRole = Role::create(['role_name' => 'Guru BK']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $classes = collect([
            ['class_name' => 'XI AK 1', 'grade' => 'XI', 'major' => 'AK', 'section' => '1'],
            ['class_name' => 'XI AK 2', 'grade' => 'XI', 'major' => 'AK', 'section' => '2'],
            ['class_name' => 'X AK 1', 'grade' => 'X', 'major' => 'AK', 'section' => '1'],
        ])->map(fn (array $data) => Classes::create($data));

        $this->actingAs($waka)
            ->post('/waka/teachers', [
                'fullname' => 'Guru BK',
                'username' => 'guru.bk',
                'email' => 'bk@example.com',
                'password' => 'password123',
                'nip' => '19870001',
                'role_id' => $bkRole->id,
                'bk_class_ids' => [$classes[0]->id, $classes[1]->id],
            ])
            ->assertRedirect('/waka/teachers');

        $teacher = User::where('username', 'guru.bk')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$classes[0]->id, $classes[1]->id],
            $teacher->bkClasses()->pluck('classes.id')->all()
        );

        $this->actingAs($waka)
            ->get('/waka/teachers')
            ->assertOk()
            ->assertSee('Data Guru')
            ->assertSee('Guru BK')
            ->assertSee('XI AK 1')
            ->assertSee('XI AK 2')
            ->assertSee('2 kelas binaan');

        $this->actingAs($waka)
            ->put('/waka/teachers/'.$teacher->id, [
                'fullname' => 'Guru BK',
                'username' => 'guru.bk',
                'email' => 'bk@example.com',
                'nip' => '19870001',
                'role_id' => $bkRole->id,
                'bk_class_ids' => [$classes[1]->id, $classes[2]->id],
            ])
            ->assertRedirect('/waka/teachers');

        $this->assertEqualsCanonicalizing(
            [$classes[1]->id, $classes[2]->id],
            $teacher->fresh()->bkClasses()->pluck('classes.id')->all()
        );
    }

    public function test_guru_bk_must_have_at_least_one_class_assignment(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $bkRole = Role::create(['role_name' => 'Guru BK']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);

        $this->actingAs($waka)
            ->from('/waka/teachers')
            ->post('/waka/teachers', [
                'fullname' => 'Guru BK',
                'username' => 'guru.bk',
                'email' => 'bk@example.com',
                'password' => 'password123',
                'nip' => '19870001',
                'role_id' => $bkRole->id,
            ])
            ->assertRedirect('/waka/teachers')
            ->assertSessionHasErrors('bk_class_ids');

        $this->assertDatabaseMissing('users', ['username' => 'guru.bk']);
    }

    public function test_general_user_form_also_saves_guru_bk_class_assignments(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $bkRole = Role::create(['role_name' => 'Guru BK']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $class = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);

        $this->actingAs($waka)
            ->post('/waka/master/users', [
                'fullname' => 'Guru BK Umum',
                'username' => 'guru.bk.umum',
                'email' => 'bk.umum@example.com',
                'password' => 'password123',
                'nip' => '19870002',
                'role_id' => $bkRole->id,
                'bk_class_ids' => [$class->id],
            ])
            ->assertRedirect();

        $teacher = User::where('username', 'guru.bk.umum')->firstOrFail();
        $this->assertSame([$class->id], $teacher->bkClasses()->pluck('classes.id')->all());
    }

    public function test_existing_teacher_can_receive_waka_access_without_duplicate_nip_and_log_in_to_web(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $guruRole = Role::create(['role_name' => 'Guru']);
        $waka = $this->makeUser('Admin', 'admin', $wakaRole);
        $teacher = $this->makeUser('SUNARSIH, S.Pd.', 'sunarsih', $guruRole);
        $teacher->update(['nip' => '19870003']);

        $this->actingAs($waka)
            ->get('/waka/master/users')
            ->assertOk()
            ->assertSee('Jadikan Guru + Waka');

        $this->actingAs($teacher)
            ->put('/waka/master/users/'.$teacher->id.'/waka-access', ['enabled' => true])
            ->assertForbidden();

        $this->actingAs($waka)
            ->put('/waka/master/users/'.$teacher->id.'/waka-access', ['enabled' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('user_additional_roles', [
            'user_id' => $teacher->id,
            'role_id' => $wakaRole->id,
        ]);
        $this->assertSame('19870003', $teacher->fresh()->nip);
        $this->assertTrue($teacher->fresh()->isGuru());
        $this->assertTrue($teacher->fresh()->isWaka());

        $this->post('/login', ['username' => '19870003', 'password' => 'password123'])
            ->assertRedirect('/waka/dashboard');
    }

    public function test_bulk_delete_keeps_the_current_waka_and_configuration_but_removes_users_and_related_academic_data(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $guruRole = Role::create(['role_name' => 'Guru']);
        $studentRole = Role::create(['role_name' => 'Siswa']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $teacher = $this->makeUser('Guru', 'guru', $guruRole);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $student = User::create([
            'fullname' => 'Siswa',
            'username' => 'siswa',
            'password' => Hash::make('password123'),
            'role_id' => $studentRole->id,
            'class_id' => $class->id,
        ]);
        $room = Room::create(['room_name' => 'Ruang 1']);
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
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => '2026-11-05',
            'topic_description' => 'Materi',
            'photo_path' => 'journal.jpg',
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'date' => '2026-11-05',
        ]);
        $configuration = SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $teacher->id,
        ]);

        $this->actingAs($waka)
            ->delete('/waka/master/users', ['confirmation' => 'HAPUS SEMUA USER'])
            ->assertRedirect('/waka/master/users')
            ->assertSessionHas('success', '2 akun berhasil dihapus. Akun Waka yang sedang digunakan dan konfigurasi sistem tetap dipertahankan; data terkait user yang dihapus ikut terhapus.');

        $this->assertDatabaseHas('users', ['id' => $waka->id]);
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseHas('system_configurations', [
            'id' => $configuration->id,
            'updated_by' => $waka->id,
        ]);
        $this->assertDatabaseCount('schedules', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_bulk_delete_requires_exact_confirmation_and_waka_role(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $guruRole = Role::create(['role_name' => 'Guru']);
        $waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $guru = $this->makeUser('Guru', 'guru', $guruRole);

        $this->actingAs($waka)
            ->from('/waka/master/users')
            ->delete('/waka/master/users', ['confirmation' => 'hapus semua'])
            ->assertRedirect('/waka/master/users')
            ->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('users', ['id' => $guru->id]);

        $this->actingAs($guru)
            ->delete('/waka/master/users', ['confirmation' => 'HAPUS SEMUA USER'])
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $guru->id]);
    }

    private function makeUser(string $fullname, string $username, Role $role): User
    {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
    }
}
