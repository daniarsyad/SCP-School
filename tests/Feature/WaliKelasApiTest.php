<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WaliKelasApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_classes_endpoint_returns_classes_for_mobile_dropdown(): void
    {
        Classes::create(['class_name' => 'X RPL 1']);

        $this->getJson('/api/classes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.class_name', 'X RPL 1');
    }

    public function test_mobile_wali_kelas_registration_requires_and_assigns_a_class(): void
    {
        $role = Role::create(['role_name' => 'Wali Kelas']);
        $class = Classes::create(['class_name' => 'X RPL 1']);

        $this->postJson('/api/register', [
            'fullname' => 'Guru Wali',
            'username' => 'guru.wali',
            'email' => 'wali@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'subject' => 'Produktif',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('class_id');

        $this->postJson('/api/register', [
            'fullname' => 'Guru Wali',
            'username' => 'guru.wali',
            'email' => 'wali@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'class_id' => $class->id,
            'subject' => 'Produktif',
        ])->assertCreated()
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.class', 'X RPL 1');

        $this->assertDatabaseHas('users', [
            'username' => 'guru.wali',
            'class_id' => $class->id,
        ]);

        $user = User::where('username', 'guru.wali')->firstOrFail();
        $this->assertSame('Produktif', $user->subject);

        $this->postJson('/api/login', [
            'username' => 'guru.wali',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.class_id', $class->id)
            ->assertJsonPath('data.user.class', 'X RPL 1');
    }

    public function test_waka_can_assign_a_class_when_adding_a_wali_kelas_user(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $waliKelasRole = Role::create(['role_name' => 'Wali Kelas']);
        $class = Classes::create(['class_name' => 'X RPL 1']);
        $waka = User::create([
            'fullname' => 'Waka',
            'username' => 'waka',
            'email' => 'waka@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $wakaRole->id,
        ]);

        $this->actingAs($waka)
            ->get('/waka/master/users')
            ->assertOk()
            ->assertSee('name="class_id"', false)
            ->assertSee('X RPL 1');

        $this->actingAs($waka)
            ->post('/waka/master/users', [
                'fullname' => 'Guru Wali',
                'username' => 'guru.wali',
                'email' => 'wali@example.com',
                'password' => 'password123',
                'role_id' => $waliKelasRole->id,
                'class_id' => $class->id,
                'subject' => 'Produktif',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'guru.wali',
            'class_id' => $class->id,
        ]);
    }
}
