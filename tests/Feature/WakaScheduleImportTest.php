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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class WakaScheduleImportTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $wakaRole = Role::create(['role_name' => 'Waka Kurikulum']);
        $teacherRole = Role::create(['role_name' => 'Guru']);
        $this->waka = $this->makeUser('Waka', 'waka', $wakaRole);
        $this->teacher = $this->makeUser('Budi Santoso', 'budi', $teacherRole, 'Matematika');

        SystemConfiguration::create([
            'active_cycle' => '1',
            'is_schedule_locked' => false,
            'updated_by' => $this->waka->id,
        ]);

        Classes::create(['class_name' => 'X RPL 1']);
        Room::create(['room_name' => 'Ruang 1']);
        Period::create([
            'period_number' => 1,
            'label' => 'Jam ke-1',
            'start_time' => '08:00',
            'end_time' => '08:35',
            'is_break' => false,
            'day_group' => 'senin',
        ]);
        Period::create([
            'period_number' => 2,
            'label' => 'Jam ke-2',
            'start_time' => '08:35',
            'end_time' => '09:10',
            'is_break' => false,
            'day_group' => 'senin',
        ]);
    }

    public function test_it_imports_schedules_for_registered_teachers(): void
    {
        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '1 jadwal berhasil diimpor.');
        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $this->teacher->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:10',
            'cycle_type' => '1',
        ]);
    }

    public function test_it_serves_an_excel_import_template(): void
    {
        $response = $this->actingAs($this->waka)
            ->get(route('waka.schedules.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=template_import_jadwal.xlsx')
            ->assertDownload('template_import_jadwal.xlsx');

        $file = $response->baseResponse->getFile();
        $this->assertGreaterThan(1000, $file->getSize());
        $spreadsheet = IOFactory::load($file->getPathname());
        $this->assertSame(['Jadwal', 'Petunjuk'], $spreadsheet->getSheetNames());
        $this->assertSame([
            'Putaran',
            'Pengajar',
            'Kelas',
            'Ruangan',
            'Mata Pelajaran',
            'Hari',
            'Jam Mulai (Jam ke-x)',
            'Jam Selesai (Jam ke-x)',
        ], $spreadsheet->getSheetByName('Jadwal')->rangeToArray('A1:H1')[0]);

        ob_start();
        $response->baseResponse->sendContent();
        $downloadedContents = ob_get_clean();
        $this->assertStringStartsWith('PK', $downloadedContents);
    }

    public function test_it_serves_a_valid_user_import_template_with_a_single_xlsx_extension(): void
    {
        $response = $this->actingAs($this->waka)
            ->get(route('waka.master.users.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=template_import_user_scp.xlsx')
            ->assertDownload('template_import_user_scp.xlsx');

        $file = $response->baseResponse->getFile();
        $this->assertGreaterThan(1000, $file->getSize());
        $spreadsheet = IOFactory::load($file->getPathname());
        $this->assertSame([
            'No',
            'Nama Lengkap',
            'Email',
            'Username',
            'Role',
            'Kelas',
            'Mata Pelajaran',
        ], $spreadsheet->getActiveSheet()->rangeToArray('A1:G1')[0]);

        ob_start();
        $response->baseResponse->sendContent();
        $downloadedContents = ob_get_clean();
        $this->assertStringStartsWith('PK', $downloadedContents);
    }

    public function test_it_rejects_unregistered_teachers_without_partial_import(): void
    {
        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
                ['1', 'Guru Tidak Terdaftar', 'X RPL 1', 'Ruang 1', 'Fisika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'tidak ada dalam daftar pengguna')
        );
        $this->assertSame(0, Schedule::count());
    }

    public function test_it_rejects_a_teacher_who_does_not_teach_the_imported_subject(): void
    {
        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Fisika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'Baris 2')
            && str_contains($message, 'Kolom Pengajar / Mata Pelajaran')
            && str_contains($message, 'mengajar "Matematika", bukan "Fisika"')
        );
        $this->assertSame(0, Schedule::count());
    }

    public function test_it_rejects_a_teacher_who_does_not_teach_the_subject_when_adding_a_schedule(): void
    {
        $response = $this->actingAs($this->waka)->post(route('waka.schedules.store'), [
            'cycle_type' => '1',
            'teacher_id' => $this->teacher->id,
            'class_id' => Classes::first()->id,
            'room_id' => Room::first()->id,
            'subject' => 'Fisika',
            'day_of_week' => 'Senin',
            'period_start_id' => Period::where('period_number', 1)->value('id'),
            'period_end_id' => Period::where('period_number', 2)->value('id'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'Kolom Pengajar / Mata Pelajaran')
            && str_contains($message, 'mengajar "Matematika", bukan "Fisika"')
        );
        $this->assertSame(0, Schedule::count());
    }

    public function test_it_rejects_conflicting_import_rows_without_partial_import(): void
    {
        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'Baris 3')
            && str_contains($message, 'bentrok dengan jadwal pada baris 2')
        );
        $this->assertSame(0, Schedule::count());
    }

    public function test_it_rejects_a_conflict_with_an_existing_schedule_without_partial_import(): void
    {
        Schedule::create([
            'teacher_id' => $this->teacher->id,
            'class_id' => Classes::first()->id,
            'room_id' => Room::first()->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:10',
            'period_id' => Period::where('period_number', 1)->value('id'),
            'cycle_type' => '1',
        ]);

        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'Baris 2')
            && str_contains($message, 'bentrok dengan jadwal Putaran 1')
            && str_contains($message, 'Guru (Budi Santoso)')
        );
        $this->assertSame(1, Schedule::count());
    }

    public function test_putaran_two_does_not_conflict_with_the_same_resources_in_putaran_one(): void
    {
        Schedule::create([
            'teacher_id' => $this->teacher->id,
            'class_id' => Classes::first()->id,
            'room_id' => Room::first()->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:10',
            'period_id' => Period::where('period_number', 1)->value('id'),
            'cycle_type' => '1',
        ]);

        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['2', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', 'Jam ke-1', 'Jam ke-2'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '1 jadwal berhasil diimpor.');
        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:10',
            'cycle_type' => '2',
        ]);
        $this->assertSame(2, Schedule::count());
    }

    public function test_conflict_error_identifies_the_putaran_of_the_import_row_and_existing_schedule(): void
    {
        Schedule::create([
            'teacher_id' => $this->teacher->id,
            'class_id' => Classes::first()->id,
            'room_id' => Room::first()->id,
            'subject' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '09:10',
            'period_id' => Period::where('period_number', 1)->value('id'),
            'cycle_type' => '1',
        ]);
        Period::create([
            'period_number' => 3,
            'label' => 'Jam ke-3',
            'start_time' => '09:00',
            'end_time' => '09:30',
            'is_break' => false,
            'day_group' => 'jumat',
        ]);
        Period::create([
            'period_number' => 4,
            'label' => 'Jam ke-4',
            'start_time' => '09:30',
            'end_time' => '10:00',
            'is_break' => false,
            'day_group' => 'jumat',
        ]);

        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', 'Jam ke-1', 'Jam ke-2'],
                ['2', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Jumat', 'Jam ke-3', 'Jam ke-4'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) =>
            str_contains($message, 'Baris 2 (Putaran 1)')
            && str_contains($message, 'bentrok dengan jadwal Putaran 1')
        );
        $this->assertSame(1, Schedule::count());
    }

    public function test_locked_schedule_rejects_imports(): void
    {
        SystemConfiguration::first()->update(['is_schedule_locked' => true]);

        $response = $this->actingAs($this->waka)->post(route('waka.schedules.import'), [
            'file' => $this->csvFile([
                ['1', 'Budi Santoso', 'X RPL 1', 'Ruang 1', 'Matematika', 'Senin', '08:00', '09:10'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Jadwal sedang terkunci. Tidak bisa mengimpor jadwal.');
        $this->assertSame(0, Schedule::count());
    }

    private function csvFile(array $rows): UploadedFile
    {
        $contents = implode(',', [
            'Putaran',
            'Pengajar',
            'Kelas',
            'Ruangan',
            'Mata Pelajaran',
            'Hari',
            'Jam Mulai',
            'Jam Selesai',
        ]) . "\n";

        foreach ($rows as $row) {
            $contents .= implode(',', array_map(
                fn (string $value) => '"' . str_replace('"', '""', $value) . '"',
                $row
            )) . "\n";
        }

        return UploadedFile::fake()->createWithContent('jadwal.csv', $contents);
    }

    private function makeUser(string $fullname, string $username, Role $role, ?string $subject = null): User
    {
        return User::create([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'subject' => $subject,
        ]);
    }
}
