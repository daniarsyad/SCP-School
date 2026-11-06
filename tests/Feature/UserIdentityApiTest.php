<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserIdentityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_parent_can_log_in_with_the_same_nis_and_different_passwords(): void
    {
        $studentRole = Role::create(['role_name' => 'Siswa']);
        $parentRole = Role::create(['role_name' => 'Orang Tua']);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $parent = User::create([
            'fullname' => 'Orang Tua',
            'username' => 'ortu.20260001',
            'nis' => '20260001',
            'password' => Hash::make('password-orangtua'),
            'role_id' => $parentRole->id,
        ]);
        $student = User::create([
            'fullname' => 'Siswa',
            'username' => 'siswa.20260001',
            'nis' => '20260001',
            'password' => Hash::make('password-siswa'),
            'role_id' => $studentRole->id,
            'class_id' => $class->id,
            'parent_id' => $parent->id,
        ]);

        $this->postJson('/api/login', [
            'identifier' => '20260001',
            'password' => 'password-siswa',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'Siswa')
            ->assertJsonPath('data.user.id', $student->id);

        $parentLogin = $this->postJson('/api/login', [
            'identifier' => '20260001',
            'password' => 'password-orangtua',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'Orang Tua')
            ->assertJsonPath('data.user.children.0.id', $student->id);

        $this->assertSame('20260001', $parentLogin->json('data.user.children.0.nis'));

        $student->update(['password' => Hash::make('password-siswa-baru')]);
        $this->postJson('/api/login', [
            'identifier' => '20260001',
            'password' => 'password-orangtua',
        ])->assertOk()->assertJsonPath('data.user.role', 'Orang Tua');
    }

    public function test_guru_can_log_in_with_nip_while_legacy_username_still_works(): void
    {
        $guruRole = Role::create(['role_name' => 'Guru']);
        $guru = User::create([
            'fullname' => 'Guru',
            'username' => 'guru.1',
            'nip' => '19870001',
            'password' => Hash::make('password123'),
            'role_id' => $guruRole->id,
        ]);

        $this->postJson('/api/login', [
            'identifier' => '19870001',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.user.id', $guru->id);

        $this->postJson('/api/login', [
            'username' => 'guru.1',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.user.id', $guru->id);
    }

    public function test_waka_requires_nip_and_can_log_in_to_the_web_with_nip(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $admin = User::create([
            'fullname' => 'Waka Admin',
            'username' => 'waka.admin',
            'nip' => '19770000',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);

        $this->actingAs($admin)
            ->post(route('waka.master.users.store'), [
                'fullname' => 'Waka Baru',
                'username' => 'waka.baru',
                'email' => 'waka.baru@example.com',
                'password' => 'password123',
                'role_id' => $wakaRole->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('nip');

        $this->actingAs($admin)
            ->post(route('waka.master.users.store'), [
                'fullname' => 'Waka Baru',
                'username' => 'waka.baru',
                'email' => 'waka.baru@example.com',
                'password' => 'password123',
                'role_id' => $wakaRole->id,
                'nip' => '19770001',
            ])
            ->assertRedirect();

        $waka = User::where('username', 'waka.baru')->firstOrFail();
        $this->actingAs($admin)->get('/waka/master/users')
            ->assertOk()
            ->assertSee('NIS / NIP')
            ->assertSee('19770001');

        $this->post('/login', [
            'username' => '19770001',
            'password' => 'password123',
        ])->assertRedirect(route('waka.dashboard'));
        $this->assertAuthenticatedAs($waka);
    }

    public function test_import_creates_a_parent_role_account_linked_to_the_selected_student(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        Role::create(['role_name' => 'Siswa']);
        Role::create(['role_name' => 'Orang Tua']);
        $waka = User::create([
            'fullname' => 'Waka',
            'username' => 'waka',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);
        Classes::create(['class_name' => 'X RPL 1']);
        $contents = implode("\n", [
            'No,Nama Lengkap,Email,Username,Role,Kelas,Mata Pelajaran,NIS,NIP,Password,NIS Siswa (khusus Orang Tua)',
            '1,Orang Tua Import,ortu@example.com,,Orang Tua,"X RPL 1",,,,password-orangtua,20260002',
            '2,Siswa Import,siswa@example.com,siswa.import,Siswa,"X RPL 1",,20260002,,password-siswa,',
            '3,Waka Import,waka@example.com,waka.import,Waka Kurikulum,,,,19770003,password123,',
        ]);

        $this->actingAs($waka)
            ->post(route('waka.master.users.import'), [
                'file' => UploadedFile::fake()->createWithContent('users.csv', $contents),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai. Baris dibuat: 3, diperbarui: 0, dilewati: 0. Password akun baru kosong memakai password123; password Orang Tua wajib diisi.');

        $student = User::where('username', 'siswa.import')->firstOrFail();
        $parent = User::where('username', 'ortu.20260002')->firstOrFail();
        $this->assertSame($parent->id, $student->parent_id);
        $this->assertSame($student->nis, $parent->nis);
        $this->assertTrue(Hash::check('password-siswa', $student->password));
        $this->assertTrue(Hash::check('password-orangtua', $parent->password));
        $this->assertDatabaseHas('users', ['username' => 'waka.import', 'nip' => '19770003']);

        $this->actingAs($waka)
            ->post(route('waka.master.users.import'), [
                'file' => UploadedFile::fake()->createWithContent('users.csv', $contents),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai. Baris dibuat: 0, diperbarui: 3, dilewati: 0. Password akun baru kosong memakai password123; password Orang Tua wajib diisi.');
        $this->assertSame($parent->id, $student->fresh()->parent_id);
    }

    public function test_import_reads_parent_nis_from_the_column_next_to_student_nis(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        Role::create(['role_name' => 'Siswa']);
        Role::create(['role_name' => 'Orang Tua']);
        $waka = User::create([
            'fullname' => 'Waka',
            'username' => 'waka',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);
        Classes::create(['class_name' => 'X RPL 1']);
        $contents = implode("\n", [
            'No,Nama Lengkap,Email,Username,Role,Kelas,Mata Pelajaran,NIS,NIS Siswa (untuk Orang Tua),NIP,Password',
            '1,Orang Tua Baru,ortu.baru@example.com,,Orang Tua,"X RPL 1",,,20260003,,password-orangtua',
            '2,Siswa Baru,siswa.baru@example.com,siswa.baru,Siswa,"X RPL 1",,20260003,,,password-siswa',
        ]);

        $this->actingAs($waka)
            ->post(route('waka.master.users.import'), [
                'file' => UploadedFile::fake()->createWithContent('users.csv', $contents),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai. Baris dibuat: 2, diperbarui: 0, dilewati: 0. Password akun baru kosong memakai password123; password Orang Tua wajib diisi.');

        $student = User::where('username', 'siswa.baru')->firstOrFail();
        $parent = User::where('username', 'ortu.20260003')->firstOrFail();
        $this->assertSame($parent->id, $student->parent_id);
        $this->assertSame('20260003', $parent->nis);
    }

    public function test_import_reuses_repeated_student_password_checks_in_a_bulk_parent_import(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $studentRole = Role::create(['role_name' => 'Siswa']);
        Role::create(['role_name' => 'Orang Tua']);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $waka = User::create([
            'fullname' => 'Waka',
            'username' => 'waka',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);
        $studentPasswordHash = Hash::make('password-siswa');

        foreach (['20260011', '20260012'] as $index => $nis) {
            User::create([
                'fullname' => 'Siswa '.$index,
                'username' => 'siswa.'.$nis,
                'nis' => $nis,
                'password' => $studentPasswordHash,
                'role_id' => $studentRole->id,
                'class_id' => $class->id,
            ]);
        }

        Hash::partialMock()
            ->shouldReceive('check')
            ->once()
            ->with('password-orangtua', $studentPasswordHash)
            ->andReturn(false);

        $contents = implode("\n", [
            'No,Nama Lengkap,Email,Username,Role,Kelas,Mata Pelajaran,NIS,NIS Siswa (untuk Orang Tua),NIP,Password',
            '1,Orang Tua 1,ortu1@example.com,,Orang Tua,"X RPL 1",,,20260011,,password-orangtua',
            '2,Orang Tua 2,ortu2@example.com,,Orang Tua,"X RPL 1",,,20260012,,password-orangtua',
        ]);

        $this->actingAs($waka)
            ->post(route('waka.master.users.import'), [
                'file' => UploadedFile::fake()->createWithContent('parents.csv', $contents),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Import selesai. Baris dibuat: 2, diperbarui: 0, dilewati: 0. Password akun baru kosong memakai password123; password Orang Tua wajib diisi.');

        $this->assertSame(2, User::where('role_id', Role::where('role_name', 'Orang Tua')->value('id'))->count());
    }

    public function test_manual_parent_creation_uses_class_and_student_selection(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $studentRole = Role::create(['role_name' => 'Siswa']);
        $parentRole = Role::create(['role_name' => 'Orang Tua']);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $waka = User::create([
            'fullname' => 'Waka',
            'username' => 'waka',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);
        $student = User::create([
            'fullname' => 'Siswa Ada',
            'username' => 'siswa.ada',
            'nis' => '20260004',
            'password' => Hash::make('password-siswa'),
            'role_id' => $studentRole->id,
            'class_id' => $class->id,
        ]);

        $this->actingAs($waka)
            ->get('/waka/master/users')
            ->assertOk()
            ->assertSee('Kelas Siswa')
            ->assertSee('Pilih Siswa')
            ->assertSee('Siswa Ada')
            ->assertSee('X RPL 1');

        $this->actingAs($waka)->followingRedirects()
            ->post(route('waka.master.users.store'), [
                'fullname' => 'Orang Tua Baru',
                'email' => 'ortu.baru@example.com',
                'password' => 'password-siswa',
                'role_id' => $parentRole->id,
                'parent_class_id' => $class->id,
                'student_id' => $student->id,
            ])
            ->assertOk()
            ->assertSee('Data pengguna gagal disimpan:')
            ->assertSee('Password Orang Tua harus berbeda dari password siswa.')
            ->assertSee('value="Orang Tua Baru"', false);

        $this->actingAs($waka)
            ->post(route('waka.master.users.store'), [
                'fullname' => 'Orang Tua Baru',
                'email' => 'ortu.baru@example.com',
                'password' => 'password-orangtua',
                'role_id' => $parentRole->id,
                'parent_class_id' => $class->id,
                'student_id' => $student->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Akun Orang Tua berhasil ditambahkan dan ditautkan ke siswa.');

        $parent = $student->fresh()->parent;
        $this->assertSame('ortu.20260004', $parent->username);
        $this->assertSame($student->nis, $parent->nis);
        $this->assertSame($class->id, $parent->class_id);
        $this->assertSame($parentRole->id, $parent->role_id);
        $this->assertTrue(Hash::check('password-orangtua', $parent->password));
        $this->assertFalse(Hash::check('password-siswa', $parent->password));

        $this->actingAs($waka)
            ->post(route('waka.master.users.store'), [
                'fullname' => 'Orang Tua Diperbarui',
                'email' => 'ortu.baru@example.com',
                'password' => 'password-orangtua-baru',
                'role_id' => $parentRole->id,
                'parent_class_id' => $class->id,
                'student_id' => $student->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Akun Orang Tua berhasil diperbarui dan ditautkan ke siswa.');

        $this->assertSame(1, User::where('role_id', $parentRole->id)->count());
        $this->assertSame('Orang Tua Diperbarui', $parent->fresh()->fullname);
    }

    public function test_parent_and_student_dashboard_summary_is_scoped_to_today_and_linked_child(): void
    {
        $studentRole = Role::create(['role_name' => 'Siswa']);
        $parentRole = Role::create(['role_name' => 'Orang Tua']);
        $teacherRole = Role::create(['role_name' => 'Guru']);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $room = Room::create(['room_name' => 'Ruang 1']);
        $parent = User::create([
            'fullname' => 'Orang Tua',
            'username' => 'ortu.dashboard',
            'password' => Hash::make('password123'),
            'role_id' => $parentRole->id,
        ]);
        $student = User::create([
            'fullname' => 'Siswa Dashboard',
            'username' => 'siswa.dashboard',
            'nis' => '20260003',
            'password' => Hash::make('password123'),
            'role_id' => $studentRole->id,
            'class_id' => $class->id,
            'parent_id' => $parent->id,
        ]);
        $teacher = User::create([
            'fullname' => 'Guru',
            'username' => 'guru.dashboard',
            'password' => Hash::make('password123'),
            'role_id' => $teacherRole->id,
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
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'topic_description' => 'Materi hari ini',
            'photo_path' => 'journal.jpg',
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status' => 'Hadir',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/dashboard/today')
            ->assertOk()
            ->assertJsonPath('data.0.student.id', $student->id)
            ->assertJsonPath('data.0.present_percentage', 100);

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/dashboard/today?student_id='.$student->id)
            ->assertOk()
            ->assertJsonPath('data.0.student.id', $student->id)
            ->assertJsonPath('data.0.present_percentage', 100);
    }
}
