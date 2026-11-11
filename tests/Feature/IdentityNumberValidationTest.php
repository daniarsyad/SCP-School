<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private Role $guruRole;
    private Role $siswaRole;
    private Classes $classA;

    protected function setUp(): void
    {
        parent::setUp();

        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $this->guruRole = Role::create(['role_name' => 'Guru']);
        $this->siswaRole = Role::create(['role_name' => 'Siswa']);
        $this->classA = Classes::create(['class_name' => 'X RPL 1']);

        $this->waka = User::create([
            'fullname' => 'Waka Kurikulum',
            'username' => 'waka.validator',
            'email' => 'waka.validator@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
            'nip' => '198001012005011001',
        ]);
    }

    public function test_store_user_rejects_non_numeric_nis_and_accepts_any_digit_length(): void
    {
        foreach (['12345AB', 'abcdefg', '123-456', '123 456'] as $invalidNis) {
            $response = $this->actingAs($this->waka)->post('/waka/master/users', [
                'fullname' => 'Siswa Test',
                'username' => 'siswa.'.md5($invalidNis),
                'email' => 'siswa.'.md5($invalidNis).'@school.test',
                'password' => 'password123',
                'role_id' => $this->siswaRole->id,
                'class_id' => $this->classA->id,
                'nis' => $invalidNis,
            ]);

            $response->assertSessionHasErrors('nis');
        }

        // Panjang digit bebas selama hanya angka (misal 5 digit, 7 digit, maupun 10 digit)
        foreach (['12345', '2612345', '2026000123'] as $validNis) {
            $validResponse = $this->actingAs($this->waka)->post('/waka/master/users', [
                'fullname' => 'Siswa Valid '.$validNis,
                'username' => 'siswa.'.$validNis,
                'email' => 'siswa.'.$validNis.'@school.test',
                'password' => 'password123',
                'role_id' => $this->siswaRole->id,
                'class_id' => $this->classA->id,
                'nis' => $validNis,
            ]);

            $validResponse->assertSessionHasNoErrors();
            $this->assertDatabaseHas('users', [
                'username' => 'siswa.'.$validNis,
                'nis' => $validNis,
            ]);
        }
    }

    public function test_store_user_and_teacher_rejects_invalid_nip_and_accepts_up_to_18_digits(): void
    {
        foreach (['1234567890123456789', '19850101ABC', '1985-0101'] as $invalidNip) {
            $userResponse = $this->actingAs($this->waka)->post('/waka/master/users', [
                'fullname' => 'Guru Invalid',
                'username' => 'guru.invalid.'.md5($invalidNip),
                'email' => 'guru.invalid.'.md5($invalidNip).'@school.test',
                'password' => 'password123',
                'role_id' => $this->guruRole->id,
                'subject' => 'Informatika',
                'nip' => $invalidNip,
            ]);
            $userResponse->assertSessionHasErrors('nip');

            $teacherResponse = $this->actingAs($this->waka)->post('/waka/teachers', [
                'fullname' => 'Teacher Invalid',
                'username' => 'teacher.invalid.'.md5($invalidNip),
                'email' => 'teacher.invalid.'.md5($invalidNip).'@school.test',
                'password' => 'password123',
                'role_id' => $this->guruRole->id,
                'nip' => $invalidNip,
            ]);
            $teacherResponse->assertSessionHasErrors('nip');
        }

        $validResponse = $this->actingAs($this->waka)->post('/waka/master/users', [
            'fullname' => 'Guru Valid',
            'username' => 'guru.valid',
            'email' => 'guru.valid@school.test',
            'password' => 'password123',
            'role_id' => $this->guruRole->id,
            'subject' => 'Informatika',
            'nip' => '198501012010012001', // tepat 18 digit angka
        ]);

        $validResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'username' => 'guru.valid',
            'nip' => '198501012010012001',
        ]);
    }

    public function test_api_register_validates_nis_and_nip_digit_rules(): void
    {
        $this->postJson('/api/register', [
            'fullname' => 'Siswa API Invalid',
            'username' => 'siswa.api.bad',
            'email' => 'siswa.api.bad@school.test',
            'password' => 'password123',
            'role_id' => $this->siswaRole->id,
            'class_id' => $this->classA->id,
            'nis' => '12345AB',
        ])->assertStatus(422)->assertJsonValidationErrors('nis');

        $this->postJson('/api/register', [
            'fullname' => 'Guru API Invalid',
            'username' => 'guru.api.bad',
            'email' => 'guru.api.bad@school.test',
            'password' => 'password123',
            'role_id' => $this->guruRole->id,
            'nip' => '1234567890123456789',
        ])->assertStatus(422)->assertJsonValidationErrors('nip');

        $this->postJson('/api/register', [
            'fullname' => 'Siswa API Valid',
            'username' => 'siswa.api.ok',
            'email' => 'siswa.api.ok@school.test',
            'password' => 'password123',
            'role_id' => $this->siswaRole->id,
            'class_id' => $this->classA->id,
            'nis' => '2026000123',
        ])->assertStatus(201);
    }

    public function test_import_users_skips_invalid_nis_and_nip_formats(): void
    {
        $contents = implode("\n", [
            'No,Nama Lengkap,Email,Username,Role,Kelas,Mata Pelajaran,NIS,NIP,Password,NIS Siswa (khusus Orang Tua)',
            '1,Siswa Salah NIS,s1@school.test,siswa.salah,Siswa,"X RPL 1",,1234ABC,,password123,',
            '2,Guru Salah NIP,g1@school.test,guru.salah,Guru,,Informatika,,1985ABC,password123,',
            '3,Siswa Benar,s2@school.test,siswa.benar,Siswa,"X RPL 1",,20260001,,password123,',
            '4,Guru Benar,g2@school.test,guru.benar,Guru,,Informatika,,198501012010012002,password123,',
        ]);

        $this->actingAs($this->waka)
            ->post(route('waka.master.users.import'), [
                'file' => UploadedFile::fake()->createWithContent('users.csv', $contents),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_issues');

        $this->assertDatabaseMissing('users', ['username' => 'siswa.salah']);
        $this->assertDatabaseMissing('users', ['username' => 'guru.salah']);
        $this->assertDatabaseHas('users', ['username' => 'siswa.benar', 'nis' => '20260001']);
        $this->assertDatabaseHas('users', ['username' => 'guru.benar', 'nip' => '198501012010012002']);
    }
}
