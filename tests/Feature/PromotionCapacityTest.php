<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PromotionCapacityTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private Role $wakaRole;
    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $this->studentRole = Role::create(['role_name' => 'Siswa']);

        $this->waka = User::create([
            'fullname' => 'Waka Kurikulum',
            'username' => 'waka.test',
            'email' => 'waka@school.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->wakaRole->id,
            'nip' => '198001012005011001',
        ]);
    }

    public function test_repeating_student_can_stay_and_incoming_students_fill_remaining_quota(): void
    {
        $classX = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);

        $classXI = Classes::create([
            'class_name' => 'XI RPL 1',
            'grade' => 'XI',
            'major' => 'RPL',
            'section' => '1',
        ]);

        // 1 siswa tinggal kelas yang sudah ada di XI RPL 1
        $repeatingStudent = User::create([
            'fullname' => 'Siswa Tinggal Kelas',
            'username' => 'siswa.tinggal',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '11001',
            'class_id' => $classXI->id,
        ]);

        // 2 siswa dari kelas X RPL 1 yang hendak naik ke XI RPL 1
        $studentA = User::create([
            'fullname' => 'Siswa Naik A',
            'username' => 'siswa.naik.a',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10001',
            'class_id' => $classX->id,
        ]);

        $studentB = User::create([
            'fullname' => 'Siswa Naik B',
            'username' => 'siswa.naik.b',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10002',
            'class_id' => $classX->id,
        ]);

        // Siswa C di kelas X yang TIDAK dicentang (tinggal di kelas X)
        $studentStayInX = User::create([
            'fullname' => 'Siswa Tinggal di X',
            'username' => 'siswa.tinggal.x',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10003',
            'class_id' => $classX->id,
        ]);

        $response = $this->actingAs($this->waka)
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $classX->id,
                'student_ids' => [$studentA->id, $studentB->id],
                'target_class_id' => $classXI->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Siswa A dan B berhasil naik ke XI RPL 1
        $this->assertEquals($classXI->id, $studentA->fresh()->class_id);
        $this->assertEquals($classXI->id, $studentB->fresh()->class_id);

        // Siswa tinggal kelas di XI tetap di XI RPL 1
        $this->assertEquals($classXI->id, $repeatingStudent->fresh()->class_id);

        // Siswa C tetap di X RPL 1
        $this->assertEquals($classX->id, $studentStayInX->fresh()->class_id);

        // Total di XI RPL 1 sekarang ada 3 siswa (1 tinggal + 2 naik)
        $totalXI = User::where('class_id', $classXI->id)->count();
        $this->assertEquals(3, $totalXI);
    }

    public function test_promotion_rejects_when_total_exceeds_36_students(): void
    {
        $classX = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);

        $classXI = Classes::create([
            'class_name' => 'XI RPL 1',
            'grade' => 'XI',
            'major' => 'RPL',
            'section' => '1',
        ]);

        // Buat 35 siswa sudah ada di XI RPL 1
        for ($i = 1; $i <= 35; $i++) {
            User::create([
                'fullname' => "Siswa XI {$i}",
                'username' => "siswa.xi.{$i}",
                'password' => Hash::make('password123'),
                'role_id' => $this->studentRole->id,
                'nis' => "110{$i}",
                'class_id' => $classXI->id,
            ]);
        }

        // Buat 2 siswa dari kelas X yang ingin masuk (35 + 2 = 37 > 36)
        $studentA = User::create([
            'fullname' => 'Siswa Baru 1',
            'username' => 'siswa.baru.1',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10051',
            'class_id' => $classX->id,
        ]);

        $studentB = User::create([
            'fullname' => 'Siswa Baru 2',
            'username' => 'siswa.baru.2',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10052',
            'class_id' => $classX->id,
        ]);

        $response = $this->actingAs($this->waka)
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $classX->id,
                'student_ids' => [$studentA->id, $studentB->id],
                'target_class_id' => $classXI->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('36', session('error'));

        // Siswa A dan B tidak berpindah
        $this->assertEquals($classX->id, $studentA->fresh()->class_id);
        $this->assertEquals($classX->id, $studentB->fresh()->class_id);
    }

    public function test_can_route_students_to_parallel_section(): void
    {
        $classX = Classes::create([
            'class_name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
            'section' => '1',
        ]);

        $classXI_1 = Classes::create([
            'class_name' => 'XI RPL 1',
            'grade' => 'XI',
            'major' => 'RPL',
            'section' => '1',
        ]);

        $classXI_2 = Classes::create([
            'class_name' => 'XI RPL 2',
            'grade' => 'XI',
            'major' => 'RPL',
            'section' => '2',
        ]);

        $student = User::create([
            'fullname' => 'Siswa Mutasi Rombel',
            'username' => 'siswa.mutasi',
            'password' => Hash::make('password123'),
            'role_id' => $this->studentRole->id,
            'nis' => '10099',
            'class_id' => $classX->id,
        ]);

        // Waka memilih target_class_id ke XI RPL 2 (bukan XI RPL 1)
        $response = $this->actingAs($this->waka)
            ->post('/waka/kenaikan-kelas/proses', [
                'class_id' => $classX->id,
                'student_ids' => [$student->id],
                'target_class_id' => $classXI_2->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Siswa berhasil diarahkan ke XI RPL 2
        $this->assertEquals($classXI_2->id, $student->fresh()->class_id);
    }
}

