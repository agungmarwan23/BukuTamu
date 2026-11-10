<?php

namespace Tests\Feature;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\KioskDevice;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas1;
    protected User $petugas2;
    protected User $resepsionis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_tester',
            'name' => 'Admin Utama',
        ]);

        $this->petugas1 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas1_tester',
            'name' => 'Petugas Satu',
        ]);

        $this->petugas2 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas2_tester',
            'name' => 'Petugas Dua',
        ]);

        $this->resepsionis = User::factory()->create([
            'role' => 'resepsionis',
            'username' => 'resepsionis_tester',
            'name' => 'Resepsionis Kantor',
        ]);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        // Tamu (guest unauthenticated) dialihkan ke login
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        // Petugas ditolak (403 Forbidden)
        $this->actingAs($this->petugas1)->get(route('admin.dashboard'))->assertForbidden();

        // Resepsionis ditolak (403 Forbidden)
        $this->actingAs($this->resepsionis)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_access_dashboard_with_metrics_and_charts(): void
    {
        $today = now()->toDateString();

        Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Dasbor Eksekutif &amp; Analitik Layanan', false)
            ->assertSee('Konsultasi Statistik');
    }

    public function test_admin_can_view_visits_list_and_filter(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $v1 = Visit::factory()->create([
            'visit_date' => $today,
            'full_name' => 'Ahmad Tamu Hari Ini',
            'channel' => 'kiosk',
        ]);

        $v2 = Visit::factory()->create([
            'visit_date' => $yesterday,
            'full_name' => 'Budi Tamu Kemarin',
            'channel' => 'online',
        ]);

        // Tanpa filter: melihat keduanya
        $response = $this->actingAs($this->admin)->get(route('admin.visits.index'));
        $response->assertOk()
            ->assertSee('Ahmad Tamu Hari Ini')
            ->assertSee('Budi Tamu Kemarin');

        // Filter search nama
        $searchResponse = $this->actingAs($this->admin)
            ->get(route('admin.visits.index', ['search' => 'Ahmad']));
        $searchResponse->assertOk()
            ->assertSee('Ahmad Tamu Hari Ini')
            ->assertDontSee('Budi Tamu Kemarin');

        // Filter channel
        $channelResponse = $this->actingAs($this->admin)
            ->get(route('admin.visits.index', ['channel' => 'online']));
        $channelResponse->assertOk()
            ->assertSee('Budi Tamu Kemarin')
            ->assertDontSee('Ahmad Tamu Hari Ini');
    }

    public function test_admin_can_view_visit_detail_with_audit_trail(): void
    {
        $visit = Visit::factory()->create([
            'full_name' => 'Siti Khodijah',
            'detail' => 'Konsultasi data kemiskinan Inhil 2025',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.visits.show', $visit));

        $response->assertOk()
            ->assertSee('Siti Khodijah')
            ->assertSee('Konsultasi data kemiskinan Inhil 2025')
            ->assertSee('Jejak Audit Status');
    }

    public function test_admin_can_export_visits_to_csv_matching_filters(): void
    {
        Visit::factory()->create([
            'full_name' => 'Tamu Ekspor Test',
            'channel' => 'kiosk',
            'institution' => 'Bappeda Inhil',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.visits.export', ['channel' => 'kiosk']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Tamu Ekspor Test', $content);
        $this->assertStringContainsString('Bappeda Inhil', $content);
    }

    public function test_admin_can_create_daily_duty_schedule(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'duty_date' => $tomorrow,
            'officer_id' => $this->petugas1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('duty_schedules', [
            'duty_date' => $tomorrow,
            'officer_id' => $this->petugas1->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_mass_assign_monthly_duty_schedules_round_robin(): void
    {
        $month = 11;
        $year = 2026;

        $response = $this->actingAs($this->admin)->post(route('admin.schedules.mass_assign'), [
            'month' => $month,
            'year' => $year,
            'officer_ids' => [$this->petugas1->id, $this->petugas2->id],
            'overwrite' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan jadwal tercipta untuk kedua petugas
        $countP1 = DutySchedule::whereMonth('duty_date', $month)
            ->whereYear('duty_date', $year)
            ->where('officer_id', $this->petugas1->id)
            ->count();

        $countP2 = DutySchedule::whereMonth('duty_date', $month)
            ->whereYear('duty_date', $year)
            ->where('officer_id', $this->petugas2->id)
            ->count();

        $this->assertGreaterThan(5, $countP1);
        $this->assertGreaterThan(5, $countP2);
    }

    public function test_admin_can_delete_duty_schedule(): void
    {
        $schedule = DutySchedule::create([
            'duty_date' => now()->addDays(5)->toDateString(),
            'officer_id' => $this->petugas1->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.schedules.destroy', $schedule));

        $response->assertRedirect();
        $this->assertDatabaseMissing('duty_schedules', ['id' => $schedule->id]);
    }

    public function test_admin_can_view_swap_analytics_ranking(): void
    {
        $today = now()->toDateString();

        DutySwap::create([
            'duty_date' => $today,
            'original_officer_id' => $this->petugas1->id,
            'replacement_officer_id' => $this->petugas2->id,
            'reason' => 'Dinas luar kantor',
            'swapped_by' => $this->petugas1->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.swaps'));

        $response->assertOk()
            ->assertSee('Analitik Pertukaran Jadwal Piket', false)
            ->assertSee('Petugas Satu')
            ->assertSee('Petugas Dua')
            ->assertSee('Dinas luar kantor');
    }

    public function test_admin_can_create_new_user_account(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Staf Baru',
            'username' => 'staf_baru',
            'email' => 'staf@bps.go.id',
            'phone' => '08987654321',
            'role' => 'petugas',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'staf_baru',
            'role' => 'petugas',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $this->assertTrue($this->petugas1->is_active);

        // Nonaktifkan
        $response = $this->actingAs($this->admin)->post(route('admin.users.toggle', $this->petugas1));
        $response->assertRedirect();

        $this->petugas1->refresh();
        $this->assertFalse($this->petugas1->is_active);

        // Aktifkan kembali
        $this->actingAs($this->admin)->post(route('admin.users.toggle', $this->petugas1));
        $this->petugas1->refresh();
        $this->assertTrue($this->petugas1->is_active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.toggle', $this->admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.reset_password', $this->petugas1), [
            'new_password' => 'password_baru_123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->petugas1->refresh();
        $this->assertTrue(Hash::check('password_baru_123', $this->petugas1->password));
    }

    public function test_admin_can_register_new_kiosk_device_and_get_activation_url(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.kiosks.store'), [
            'name' => 'PC Kios Lobi 2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('activation_url');

        $this->assertDatabaseHas('kiosk_devices', [
            'name' => 'PC Kios Lobi 2',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_and_delete_kiosk_device(): void
    {
        $device = KioskDevice::create([
            'name' => 'PC Kios Uji',
            'token_hash' => hash('sha256', 'plain-token-test'),
            'is_active' => true,
        ]);

        // Toggle nonaktif
        $this->actingAs($this->admin)->post(route('admin.kiosks.toggle', $device));
        $device->refresh();
        $this->assertFalse($device->is_active);

        // Hapus
        $this->actingAs($this->admin)->delete(route('admin.kiosks.destroy', $device));
        $this->assertDatabaseMissing('kiosk_devices', ['id' => $device->id]);
    }

    public function test_admin_can_regenerate_kiosk_device_activation_token(): void
    {
        $device = KioskDevice::create([
            'name' => 'PC Kios Lobi Lama',
            'token_hash' => hash('sha256', 'old-token'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.kiosks.regenerate', $device));

        $response->assertRedirect();
        $response->assertSessionHas('activation_url');
        $response->assertSessionHas('success');

        $device->refresh();
        $this->assertNotEquals(hash('sha256', 'old-token'), $device->token_hash);
        $this->assertNull($device->activated_by);
    }

    public function test_admin_can_download_visits_csv_template(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.visits.template'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_import_visits_via_csv(): void
    {
        $csvData = "tanggal,nama_lengkap,instansi,no_hp,email,tujuan,jenis_layanan,rincian_keperluan,status\n"
                 . "2026-08-10,Andi Prasetyo,Dinas Sosial,081234567890,andi@test.com,pst,Konsultasi Statistik,Konsultasi data DTKS,selesai\n"
                 . "2026-08-11,Dewi Sartika,Mahasiswa Unisi,081298765432,,non_pst,Kerjasama,Riset skripsi,selesai\n";

        $file = UploadedFile::fake()->createWithContent('riwayat_kunjungan.csv', $csvData);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.visits.import'), [
                'csv_file' => $file,
                'source_name' => 'Spreadsheet Arsip 2026',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'full_name' => 'Andi Prasetyo',
            'is_imported' => true,
            'import_source' => 'Spreadsheet Arsip 2026',
            'status' => 'selesai',
        ]);

        $this->assertDatabaseHas('visits', [
            'full_name' => 'Dewi Sartika',
            'is_imported' => true,
            'import_source' => 'Spreadsheet Arsip 2026',
            'status' => 'selesai',
        ]);
    }

    public function test_admin_can_filter_visits_by_is_imported(): void
    {
        // 1 kunjungan langsung
        Visit::factory()->create([
            'full_name' => 'Tamu Langsung',
            'is_imported' => false,
        ]);

        // 1 kunjungan impor
        Visit::factory()->create([
            'full_name' => 'Tamu Riwayat Impor',
            'is_imported' => true,
            'import_source' => 'Excel 2025',
        ]);

        // Filter hanya data impor
        $resImported = $this->actingAs($this->admin)
            ->get(route('admin.visits.index', ['is_imported' => '1']));
        $resImported->assertSee('Tamu Riwayat Impor');
        $resImported->assertDontSee('Tamu Langsung');

        // Filter hanya kunjungan langsung
        $resDirect = $this->actingAs($this->admin)
            ->get(route('admin.visits.index', ['is_imported' => '0']));
        $resDirect->assertSee('Tamu Langsung');
        $resDirect->assertDontSee('Tamu Riwayat Impor');
    }

    public function test_admin_can_update_visit_status_and_officer_note(): void
    {
        $officer = User::factory()->petugas()->create([
            'name' => 'Petugas Ditugaskan',
        ]);

        $pastVisit = Visit::factory()->create([
            'visit_date' => now()->subDays(2)->toDateString(),
            'channel' => 'kiosk',
            'status' => 'menunggu',
            'assigned_officer_id' => null,
            'officer_note' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.visits.update', $pastVisit), [
                'status' => 'selesai',
                'assigned_officer_id' => $officer->id,
                'officer_note' => 'Diselesaikan secara manual oleh admin karena terlewat',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pastVisit->refresh();
        $this->assertEquals('selesai', $pastVisit->status);
        $this->assertEquals($officer->id, $pastVisit->assigned_officer_id);
        $this->assertEquals('Diselesaikan secara manual oleh admin karena terlewat', $pastVisit->officer_note);
        $this->assertNotNull($pastVisit->finished_at);

        // Pastikan transisi status tercatat di status_logs
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $pastVisit->id,
            'from_status' => 'menunggu',
            'to_status' => 'selesai',
            'user_id' => $this->admin->id,
        ]);
    }
}
