<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataUserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private Role $wakaRole;
    private Role $guruRole;
    private Role $waliKelasRole;
    private Role $bkRole;
    private Role $siswaRole;
    private Role $ortuRole;
    private Classes $classA;
    private Classes $classB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $this->guruRole = Role::create(['role_name' => 'Guru']);
        $this->waliKelasRole = Role::create(['role_name' => 'Wali Kelas']);
        $this->bkRole = Role::create(['role_name' => 'Guru BK']);
        $this->siswaRole = Role::create(['role_name' => 'Siswa']);
        $this->ortuRole = Role::create(['role_name' => 'Orang Tua']);

        $this->classA = Classes::create(['class_name' => 'X PPLG 1']);
        $this->classB = Classes::create(['class_name' => 'XI PPLG 2']);

        $this->waka = User::create([
            'fullname' => 'Waka Kurikulum',
            'username' => 'waka.kurikulum',
            'email' => 'waka@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->wakaRole->id,
            'nip' => '198001012005011001',
        ]);
    }

    public function test_users_page_displays_edit_button_and_dialog_modal(): void
    {
        $student = User::create([
            'fullname' => 'Budi Santoso',
            'username' => 'budi123',
            'email' => 'budi@student.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->siswaRole->id,
            'nis' => '12345',
            'class_id' => $this->classA->id,
        ]);

        $response = $this->actingAs($this->waka)->get('/waka/master/users');

        $response->assertOk();
        $response->assertSee('data-open-edit', false);
        $response->assertSee('id="editUserModal"', false);
        $response->assertSee('id="editNisField"', false);
        $response->assertSee('id="editNipField"', false);
        $response->assertSee('Edit Data Pengguna');
    }

    public function test_can_update_siswa_with_nis_and_class(): void
    {
        $student = User::create([
            'fullname' => 'Budi Santoso',
            'username' => 'budi123',
            'email' => 'budi@student.test',
            'password' => Hash::make('oldpassword'),
            'role_id' => $this->siswaRole->id,
            'nis' => '12345',
            'class_id' => $this->classA->id,
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$student->id}", [
                'fullname' => 'Budi Santoso Updated',
                'username' => 'budi_baru',
                'email' => 'budi_baru@student.test',
                'role_id' => $this->siswaRole->id,
                'nis' => '12345-REV',
                'class_id' => $this->classB->id,
                'password' => '', // kosongkan tidak mengubah password
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('Budi Santoso Updated', $student->fullname);
        $this->assertEquals('budi_baru', $student->username);
        $this->assertEquals('budi_baru@student.test', $student->email);
        $this->assertEquals('12345-REV', $student->nis);
        $this->assertEquals($this->classB->id, $student->class_id);
        $this->assertTrue(Hash::check('oldpassword', $student->password));
    }

    public function test_can_update_waka_or_guru_with_nip_and_new_password(): void
    {
        $guru = User::create([
            'fullname' => 'Siti Guru',
            'username' => 'siti.guru',
            'email' => 'siti@school.test',
            'password' => Hash::make('oldpassword'),
            'role_id' => $this->guruRole->id,
            'nip' => '198501012010012001',
            'subject' => 'Matematika',
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$guru->id}", [
                'fullname' => 'Dr. Siti Guru, M.Pd.',
                'username' => 'siti.guru',
                'email' => 'siti.guru@school.test',
                'role_id' => $this->guruRole->id,
                'nip' => '198501012010012099',
                'subject' => 'Matematika Peminatan',
                'password' => 'newsecret123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guru->refresh();
        $this->assertEquals('Dr. Siti Guru, M.Pd.', $guru->fullname);
        $this->assertEquals('198501012010012099', $guru->nip);
        $this->assertEquals('Matematika Peminatan', $guru->subject);
        $this->assertNull($guru->nis);
        $this->assertTrue(Hash::check('newsecret123', $guru->password));
    }

    public function test_can_update_guru_bk_with_multiple_classes(): void
    {
        $guruBk = User::create([
            'fullname' => 'Ahmad BK',
            'username' => 'ahmad.bk',
            'email' => 'ahmad@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->bkRole->id,
            'nip' => '198901012015011001',
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$guruBk->id}", [
                'fullname' => 'Ahmad BK, S.Pd.',
                'username' => 'ahmad.bk',
                'email' => 'ahmad@school.test',
                'role_id' => $this->bkRole->id,
                'nip' => '198901012015011001',
                'bk_class_ids' => [$this->classA->id, $this->classB->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guruBk->refresh();
        $this->assertEqualsCanonicalizing(
            [$this->classA->id, $this->classB->id],
            $guruBk->bkClasses->pluck('id')->all()
        );
    }

    public function test_can_update_waka_kurikulum_with_nip_and_no_class_or_nis(): void
    {
        $otherWaka = User::create([
            'fullname' => 'Waka Humas',
            'username' => 'waka.humas',
            'email' => 'humas@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->wakaRole->id,
            'nip' => '198101012006011002',
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$otherWaka->id}", [
                'fullname' => 'Drs. Waka Humas, M.M.',
                'username' => 'waka.humas.updated',
                'email' => 'humas.updated@school.test',
                'role_id' => $this->wakaRole->id,
                'nip' => '198101012006011099',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $otherWaka->refresh();
        $this->assertEquals('Drs. Waka Humas, M.M.', $otherWaka->fullname);
        $this->assertEquals('waka.humas.updated', $otherWaka->username);
        $this->assertEquals('198101012006011099', $otherWaka->nip);
        $this->assertNull($otherWaka->nis);
        $this->assertNull($otherWaka->class_id);
        $this->assertNull($otherWaka->subject);
    }

    public function test_can_update_wali_kelas_with_nip_subject_and_class(): void
    {
        $walas = User::create([
            'fullname' => 'Walas Anton',
            'username' => 'anton.walas',
            'email' => 'anton@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->waliKelasRole->id,
            'nip' => '198701012012011001',
            'subject' => 'Bahasa Indonesia',
            'class_id' => $this->classA->id,
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$walas->id}", [
                'fullname' => 'Anton S.Pd., Gr.',
                'username' => 'anton.walas',
                'email' => 'anton@school.test',
                'role_id' => $this->waliKelasRole->id,
                'nip' => '198701012012011001',
                'subject' => 'Bahasa Indonesia Lanjutan',
                'class_id' => $this->classB->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $walas->refresh();
        $this->assertEquals('Anton S.Pd., Gr.', $walas->fullname);
        $this->assertEquals('Bahasa Indonesia Lanjutan', $walas->subject);
        $this->assertEquals($this->classB->id, $walas->class_id);
    }

    public function test_can_update_orang_tua_linked_student(): void
    {
        $student = User::create([
            'fullname' => 'Citra Siswa',
            'username' => 'citra123',
            'password' => Hash::make('password123'),
            'role_id' => $this->siswaRole->id,
            'nis' => '54321',
            'class_id' => $this->classA->id,
        ]);

        $parent = User::create([
            'fullname' => 'Pak Joko',
            'username' => 'joko.ortu',
            'password' => Hash::make('password123'),
            'role_id' => $this->ortuRole->id,
        ]);

        $response = $this->actingAs($this->waka)
            ->put("/waka/master/users/{$parent->id}", [
                'fullname' => 'Joko Susanto',
                'username' => 'joko.ortu',
                'role_id' => $this->ortuRole->id,
                'student_id' => $student->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $parent->refresh();
        $student->refresh();
        $this->assertEquals('Joko Susanto', $parent->fullname);
        $this->assertEquals($student->class_id, $parent->class_id);
        $this->assertEquals($student->nis, $parent->nis);
        $this->assertEquals($parent->id, $student->parent_id);
    }

    public function test_login_and_forgot_password_have_landing_page_links(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Lupa kata sandi?');

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee(route('landing'));

        // Landing page navbar should not have direct Dashboard Waka link/button
        $this->get('/')
            ->assertOk()
            ->assertDontSee(route('waka.dashboard'));

        $wakaUser = User::where('role_id', 1)->first() ?? User::factory()->create(['role_id' => 1]);
        $this->actingAs($wakaUser)
            ->get('/')
            ->assertOk()
            ->assertDontSee(route('waka.dashboard'));
    }
}

