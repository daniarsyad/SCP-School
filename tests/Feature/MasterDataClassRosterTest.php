<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataClassRosterTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;

    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $this->studentRole = Role::create(['role_name' => 'Siswa']);
        $this->waka = $this->makeUser('Waka', 'waka', $wakaRole);
    }

    public function test_class_count_matches_active_student_roster(): void
    {
        $class = $this->makeClass('X RPL 1', 'X', 'RPL', '1');
        $activeStudent = $this->makeUser('Siswa Aktif', 'siswa_aktif', $this->studentRole, $class);
        $alumni = $this->makeUser('Alumni', 'alumni', $this->studentRole, $class);
        $alumni->forceFill(['is_alumni' => true, 'class_id' => null])->save();
        $this->makeUser('Wali Kelas', 'wali', Role::create(['role_name' => 'Wali Kelas']), $class);

        $this->actingAs($this->waka)
            ->get('/waka/master/classes')
            ->assertOk()
            ->assertSee('1 Siswa');

        $this->actingAs($this->waka)
            ->get("/waka/master/classes/{$class->id}/students")
            ->assertOk()
            ->assertSee($activeStudent->fullname)
            ->assertDontSee($alumni->fullname)
            ->assertDontSee('Wali Kelas');
    }

    public function test_promotion_moves_active_student_to_the_next_class_roster(): void
    {
        $source = $this->makeClass('X RPL 1', 'X', 'RPL', '1');
        $target = $this->makeClass('XI RPL 1', 'XI', 'RPL', '1');
        $student = $this->makeUser('Siswa Naik', 'siswa_naik', $this->studentRole, $source);

        $this->actingAs($this->waka)
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $source->id,
                'student_ids' => [$student->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'class_id' => $target->id,
            'is_alumni' => false,
        ]);

        $this->actingAs($this->waka)
            ->get("/waka/master/classes/{$target->id}/students")
            ->assertOk()
            ->assertSee($student->fullname);

        $this->actingAs($this->waka)
            ->get("/waka/master/classes/{$source->id}/students")
            ->assertOk()
            ->assertDontSee($student->fullname);
    }

    public function test_graduated_student_is_marked_as_alumni_and_removed_from_class_roster(): void
    {
        $class = $this->makeClass('XII RPL 1', 'XII', 'RPL', '1');
        $student = $this->makeUser('Siswa Lulus', 'siswa_lulus', $this->studentRole, $class);

        $this->actingAs($this->waka)
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $class->id,
                'student_ids' => [$student->id],
                'graduate' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'class_id' => null,
            'is_alumni' => true,
        ]);

        $this->actingAs($this->waka)
            ->get("/waka/master/classes/{$class->id}/students")
            ->assertOk()
            ->assertDontSee($student->fullname)
            ->assertSee('Belum ada siswa di kelas ini');
    }

    public function test_promotion_rejects_students_outside_the_selected_source_class(): void
    {
        $source = $this->makeClass('X RPL 1', 'X', 'RPL', '1');
        $this->makeClass('XI RPL 1', 'XI', 'RPL', '1');
        $otherClass = $this->makeClass('X TKJ 1', 'X', 'TKJ', '1');
        $student = $this->makeUser('Siswa Kelas Lain', 'siswa_lain', $this->studentRole, $otherClass);

        $this->actingAs($this->waka)
            ->from('/waka/kenaikan-kelas')
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $source->id,
                'student_ids' => [$student->id],
            ])
            ->assertRedirect('/waka/kenaikan-kelas')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'class_id' => $otherClass->id,
            'is_alumni' => false,
        ]);
    }

    private function makeClass(string $name, string $grade, string $major, string $section): Classes
    {
        return Classes::create([
            'class_name' => $name,
            'grade' => $grade,
            'major' => $major,
            'section' => $section,
        ]);
    }

    private function makeUser(string $name, string $username, Role $role, ?Classes $class = null): User
    {
        return User::create([
            'fullname' => $name,
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'class_id' => $class?->id,
        ]);
    }
}
