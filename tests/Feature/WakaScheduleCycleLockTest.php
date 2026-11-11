<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WakaScheduleCycleLockTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private User $teacher;
    private Classes $class;
    private Room $room;
    private Period $period1;
    private Period $period2;

    protected function setUp(): void
    {
        parent::setUp();

        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $teacherRole = Role::create(['role_name' => 'Guru']);

        $this->waka = User::create([
            'username' => 'waka',
            'password' => Hash::make('password'),
            'fullname' => 'Waka Kurikulum',
            'role_id'  => $wakaRole->id,
        ]);

        $this->teacher = User::create([
            'username' => 'budi',
            'password' => Hash::make('password'),
            'fullname' => 'Budi Santoso',
            'role_id'  => $teacherRole->id,
            'subject'  => 'Matematika',
        ]);

        SystemConfiguration::create([
            'active_cycle'       => '1',
            'is_schedule_locked' => false,
            'is_cycle_1_locked'  => false,
            'is_cycle_2_locked'  => false,
            'updated_by'         => $this->waka->id,
        ]);

        $this->class = Classes::create(['class_name' => 'X RPL 1']);
        $this->room  = Room::create(['room_name' => 'Ruang 1']);

        $this->period1 = Period::create([
            'period_number' => 1,
            'label'         => 'Jam ke-1',
            'start_time'    => '08:00',
            'end_time'      => '08:35',
            'is_break'      => false,
            'day_group'     => 'senin',
        ]);

        $this->period2 = Period::create([
            'period_number' => 2,
            'label'         => 'Jam ke-2',
            'start_time'    => '08:35',
            'end_time'      => '09:10',
            'is_break'      => false,
            'day_group'     => 'senin',
        ]);
    }

    public function test_can_add_schedule_to_unlocked_cycle_when_other_cycle_is_locked(): void
    {
        // Putaran 1 dikunci, Putaran 2 dibuka
        SystemConfiguration::first()->update([
            'is_cycle_1_locked' => true,
            'is_cycle_2_locked' => false,
        ]);

        // Coba tambah jadwal ke Putaran 1 -> harus gagal
        $response1 = $this->actingAs($this->waka)->post(route('waka.schedules.store'), [
            'teacher_id'      => $this->teacher->id,
            'class_id'        => $this->class->id,
            'room_id'         => $this->room->id,
            'subject'         => 'Matematika',
            'day_of_week'     => 'Senin',
            'period_start_id' => $this->period1->id,
            'period_end_id'   => $this->period1->id,
            'cycle_type'      => '1',
        ]);

        $response1->assertRedirect();
        $response1->assertSessionHas('error');
        $this->assertSame(0, Schedule::where('cycle_type', '1')->count());

        // Coba tambah jadwal ke Putaran 2 -> harus berhasil
        $response2 = $this->actingAs($this->waka)->post(route('waka.schedules.store'), [
            'teacher_id'      => $this->teacher->id,
            'class_id'        => $this->class->id,
            'room_id'         => $this->room->id,
            'subject'         => 'Matematika',
            'day_of_week'     => 'Senin',
            'period_start_id' => $this->period1->id,
            'period_end_id'   => $this->period1->id,
            'cycle_type'      => '2',
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');
        $this->assertSame(1, Schedule::where('cycle_type', '2')->count());
    }

    public function test_can_add_schedule_to_cycle_1_when_cycle_2_is_locked(): void
    {
        // Putaran 2 dikunci, Putaran 1 dibuka
        SystemConfiguration::first()->update([
            'is_cycle_1_locked' => false,
            'is_cycle_2_locked' => true,
        ]);

        // Coba tambah jadwal ke Putaran 2 -> harus gagal
        $response2 = $this->actingAs($this->waka)->post(route('waka.schedules.store'), [
            'teacher_id'      => $this->teacher->id,
            'class_id'        => $this->class->id,
            'room_id'         => $this->room->id,
            'subject'         => 'Matematika',
            'day_of_week'     => 'Senin',
            'period_start_id' => $this->period1->id,
            'period_end_id'   => $this->period1->id,
            'cycle_type'      => '2',
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        $this->assertSame(0, Schedule::where('cycle_type', '2')->count());

        // Coba tambah jadwal ke Putaran 1 -> harus berhasil
        $response1 = $this->actingAs($this->waka)->post(route('waka.schedules.store'), [
            'teacher_id'      => $this->teacher->id,
            'class_id'        => $this->class->id,
            'room_id'         => $this->room->id,
            'subject'         => 'Matematika',
            'day_of_week'     => 'Senin',
            'period_start_id' => $this->period1->id,
            'period_end_id'   => $this->period1->id,
            'cycle_type'      => '1',
        ]);

        $response1->assertRedirect();
        $response1->assertSessionHas('success');
        $this->assertSame(1, Schedule::where('cycle_type', '1')->count());
    }

    public function test_toggle_lock_per_cycle(): void
    {
        $config = SystemConfiguration::first();
        $this->assertFalse($config->is_cycle_1_locked);
        $this->assertFalse($config->is_cycle_2_locked);

        // Kunci Putaran 1
        $this->actingAs($this->waka)->post(route('waka.toggle.lock'), ['cycle' => '1'])
            ->assertRedirect();

        $config->refresh();
        $this->assertTrue($config->is_cycle_1_locked);
        $this->assertFalse($config->is_cycle_2_locked);

        // Kunci Putaran 2
        $this->actingAs($this->waka)->post(route('waka.toggle.lock'), ['cycle' => '2'])
            ->assertRedirect();

        $config->refresh();
        $this->assertTrue($config->is_cycle_1_locked);
        $this->assertTrue($config->is_cycle_2_locked);

        // Buka Putaran 1
        $this->actingAs($this->waka)->post(route('waka.toggle.lock'), ['cycle' => '1'])
            ->assertRedirect();

        $config->refresh();
        $this->assertFalse($config->is_cycle_1_locked);
        $this->assertTrue($config->is_cycle_2_locked);
    }

    public function test_schedule_view_hides_add_and_import_when_current_cycle_is_locked(): void
    {
        // Putaran 1 dibuka, Putaran 2 dikunci
        SystemConfiguration::first()->update([
            'is_cycle_1_locked' => false,
            'is_cycle_2_locked' => true,
        ]);

        // Kunjungi halaman Putaran 2 (terkunci) -> tombol tambah/import dan form tambah tidak boleh ada
        $responseCycle2 = $this->actingAs($this->waka)->get(route('waka.schedules.index', ['cycle' => '2']));
        $responseCycle2->assertOk();
        $responseCycle2->assertSee('Putaran 2 Terkunci');
        $responseCycle2->assertDontSee('+ Tambah Jadwal');
        $responseCycle2->assertDontSee('Tambah Jadwal Baru');
        $responseCycle2->assertDontSee('Import Excel');

        // Kunjungi halaman Putaran 1 (terbuka) -> tombol tambah/import dan form tambah harus ada
        $responseCycle1 = $this->actingAs($this->waka)->get(route('waka.schedules.index', ['cycle' => '1']));
        $responseCycle1->assertOk();
        $responseCycle1->assertDontSee('Putaran 1 Terkunci');
        $responseCycle1->assertSee('+ Tambah Jadwal');
        $responseCycle1->assertSee('Tambah Jadwal Baru');
        $responseCycle1->assertSee('Import Excel');

        // Sebaliknya: Kunci Putaran 1, Buka Putaran 2
        SystemConfiguration::first()->update([
            'is_cycle_1_locked' => true,
            'is_cycle_2_locked' => false,
        ]);

        // Kunjungi Putaran 1 (sekarang terkunci) -> tombol tambah/import dan form tidak boleh ada
        $responseCycle1Locked = $this->actingAs($this->waka)->get(route('waka.schedules.index', ['cycle' => '1']));
        $responseCycle1Locked->assertOk();
        $responseCycle1Locked->assertSee('Putaran 1 Terkunci');
        $responseCycle1Locked->assertDontSee('+ Tambah Jadwal');
        $responseCycle1Locked->assertDontSee('Tambah Jadwal Baru');
        $responseCycle1Locked->assertDontSee('Import Excel');

        // Kunjungi Putaran 2 (sekarang terbuka) -> tombol tambah/import dan form harus ada
        $responseCycle2Open = $this->actingAs($this->waka)->get(route('waka.schedules.index', ['cycle' => '2']));
        $responseCycle2Open->assertOk();
        $responseCycle2Open->assertDontSee('Putaran 2 Terkunci');
        $responseCycle2Open->assertSee('+ Tambah Jadwal');
        $responseCycle2Open->assertSee('Tambah Jadwal Baru');
        $responseCycle2Open->assertSee('Import Excel');
    }
}

