<?php

namespace Tests\Feature;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\KioskDevice;
use App\Models\User;
use App\Models\Visit;
use App\Services\DutyScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcceptanceCriteriaTest extends TestCase
{
    use RefreshDatabase;

    private function createKioskDevice(): array
    {
        $rawToken = 'ac_kiosk_token_12345678901234567890';
        $tokenHash = hash('sha256', $rawToken);

        $device = KioskDevice::create([
            'name' => 'PC Kios Uji AC',
            'token_hash' => $tokenHash,
            'is_active' => true,
        ]);

        return [$device, $rawToken];
    }

    /**
     * AC-1: Tamu di kantor (mode kios) tidak bisa mengirim form tanpa foto & field wajib;
     *       tamu online tidak diminta foto.
     */
    public function test_ac_01_kiosk_requires_photo_and_mandatory_fields_while_online_does_not_require_photo(): void
    {
        Storage::fake('local');
        [, $token] = $this->createKioskDevice();

        // 1. Kios tanpa foto -> gagal validasi
        $kioskPayloadWithoutPhoto = [
            'channel' => 'kiosk',
            'full_name' => 'Tamu Kios',
            'email' => 'tamu@example.com',
            'phone' => '08123456789',
            'institution' => 'BPS Inhil',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Konsultasi data inflasi',
            'consent' => '1',
            // photo sengaja dikosongkan
        ];

        $responseKiosk = $this->withCookie('kiosk_device_token', $token)
            ->post('/kios', $kioskPayloadWithoutPhoto);

        $responseKiosk->assertSessionHasErrors('photo');

        // 2. Kios dengan foto base64 -> sukses
        $dummyBase64Photo = 'data:image/jpeg;base64,' . base64_encode('fake image binary content');
        $kioskPayloadWithPhoto = array_merge($kioskPayloadWithoutPhoto, [
            'photo' => $dummyBase64Photo,
        ]);

        $responseKioskSuccess = $this->withCookie('kiosk_device_token', $token)
            ->post('/kios', $kioskPayloadWithPhoto);

        $responseKioskSuccess->assertRedirect();
        $this->assertDatabaseHas('visits', [
            'full_name' => 'Tamu Kios',
            'channel' => 'kiosk',
        ]);
        $createdKioskVisit = Visit::where('full_name', 'Tamu Kios')->first();
        $this->assertNotNull($createdKioskVisit->photo_path);

        // 3. Online tanpa foto -> sukses
        $onlinePayload = [
            'channel' => 'online',
            'full_name' => 'Tamu Online',
            'email' => 'online@example.com',
            'phone' => '08987654321',
            'institution' => 'Universitas Riau',
            'purpose' => 'pst',
            'service_type' => 'Perpustakaan',
            'detail' => 'Kebutuhan data riset skripsi',
            'consent' => '1',
        ];

        $responseOnline = $this->post('/layanan', $onlinePayload);
        $responseOnline->assertRedirect();
        $this->assertDatabaseHas('visits', [
            'full_name' => 'Tamu Online',
            'channel' => 'online',
            'photo_path' => null,
        ]);
    }

    /**
     * AC-2: Dua tamu mendaftar bersamaan tidak pernah mendapat nomor antrean yang sama.
     */
    public function test_ac_02_visitors_registered_on_same_day_get_unique_sequential_queue_numbers(): void
    {
        Storage::fake('local');
        [, $token] = $this->createKioskDevice();
        $dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('fake photo');

        $payload1 = [
            'channel' => 'kiosk',
            'full_name' => 'Tamu Antrean 1',
            'email' => 'tamu1@example.com',
            'phone' => '08111111111',
            'institution' => 'Dinas A',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Keperluan 1',
            'photo' => $dummyPhoto,
            'consent' => '1',
        ];

        $payload2 = [
            'channel' => 'kiosk',
            'full_name' => 'Tamu Antrean 2',
            'email' => 'tamu2@example.com',
            'phone' => '08222222222',
            'institution' => 'Dinas B',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Keperluan 2',
            'photo' => $dummyPhoto,
            'consent' => '1',
        ];

        $this->withCookie('kiosk_device_token', $token)->post('/kios', $payload1);
        $this->withCookie('kiosk_device_token', $token)->post('/kios', $payload2);

        $v1 = Visit::where('full_name', 'Tamu Antrean 1')->first();
        $v2 = Visit::where('full_name', 'Tamu Antrean 2')->first();

        $this->assertNotNull($v1);
        $this->assertNotNull($v2);
        $this->assertNotNull($v1->queue_number);
        $this->assertNotNull($v2->queue_number);
        $this->assertNotEquals($v1->queue_number, $v2->queue_number);
        $this->assertEquals('PST-01', $v1->queue_number);
        $this->assertEquals('PST-02', $v2->queue_number);
    }

    /**
     * AC-3: Memilih PST memerlukan jenis layanan + rincian keperluan;
     *       memilih Pengaduan memerlukan judul aduan + isi pengaduan.
     */
    public function test_ac_03_pst_and_pengaduan_conditional_validation(): void
    {
        // 1. PST tanpa service_type harus gagal
        $pstInvalid = [
            'channel' => 'online',
            'full_name' => 'Uji PST',
            'email' => 'uji@example.com',
            'phone' => '08123456789',
            'institution' => 'Umum',
            'purpose' => 'pst',
            'detail' => 'Penjelasan kebutuhan',
            'consent' => '1',
        ];
        $res1 = $this->post('/layanan', $pstInvalid);
        $res1->assertSessionHasErrors(['service_type']);

        // 2. Pengaduan tanpa complaint_title harus gagal
        $pengaduanInvalid = [
            'channel' => 'online',
            'full_name' => 'Uji Pengaduan',
            'email' => 'uji@example.com',
            'phone' => '08123456789',
            'institution' => 'Masyarakat',
            'purpose' => 'pengaduan',
            'detail' => 'Rincian aduan',
            'consent' => '1',
        ];
        $res2 = $this->post('/layanan', $pengaduanInvalid);
        $res2->assertSessionHasErrors(['complaint_title']);

        // 3. Pengaduan dengan data lengkap berhasil
        $pengaduanValid = [
            'channel' => 'online',
            'full_name' => 'Pengadu Sah',
            'email' => 'pengadu@example.com',
            'phone' => '08123456789',
            'institution' => 'Masyarakat Inhil',
            'purpose' => 'pengaduan',
            'complaint_title' => 'Layanan Publik Terlambat',
            'detail' => 'Penjelasan lengkap perihal pengaduan',
            'consent' => '1',
        ];
        $res3 = $this->post('/layanan', $pengaduanValid);
        $res3->assertRedirect();
        $this->assertDatabaseHas('visits', [
            'full_name' => 'Pengadu Sah',
            'purpose' => 'pengaduan',
            'complaint_title' => 'Layanan Publik Terlambat',
        ]);
    }

    /**
     * AC-4: Swap petugas membuat baris baru di duty_swaps, jadwal asli tetap utuh.
     */
    public function test_ac_04_swap_creates_duty_swap_without_modifying_original_schedule(): void
    {
        $officerOriginal = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
        ]);
        $officerSubstitute = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $dutyDate = Carbon::tomorrow()->toDateString();

        // Jadwal asli dibuat untuk officerOriginal
        $originalSchedule = DutySchedule::create([
            'officer_id' => $officerOriginal->id,
            'duty_date' => $dutyDate,
            'created_by' => $officerOriginal->id,
        ]);

        // Ajukan pertukaran jadwal (swap)
        $response = $this->actingAs($officerOriginal)->post('/petugas/swap', [
            'duty_date' => $dutyDate,
            'replacement_officer_id' => $officerSubstitute->id,
            'reason' => 'Ada dinas luar mendesak ke Tembilahan Hulu',
        ]);

        $response->assertRedirect();

        // Verifikasi 1: Baris duty_swaps terbentuk
        $this->assertDatabaseHas('duty_swaps', [
            'original_officer_id' => $officerOriginal->id,
            'replacement_officer_id' => $officerSubstitute->id,
            'duty_date' => $dutyDate,
        ]);

        // Verifikasi 2: Baris jadwal asli TIDAK PERNAH DIUBAH (officer_id tetap officerOriginal)
        $scheduleAfterSwap = DutySchedule::find($originalSchedule->id);
        $this->assertEquals($officerOriginal->id, $scheduleAfterSwap->officer_id);

        // Verifikasi 3: Service mendeteksi petugas pengganti sebagai petugas efektif
        $scheduleService = app(DutyScheduleService::class);
        $effectiveOfficers = $scheduleService->getEffectiveOfficersForDate($dutyDate);

        $this->assertTrue($effectiveOfficers->contains('id', $officerSubstitute->id));
        $this->assertFalse($effectiveOfficers->contains('id', $officerOriginal->id));
    }

    /**
     * AC-5: Layar antrean publik dan API layar tidak menampilkan nama atau kontak tamu.
     */
    public function test_ac_05_queue_display_screen_and_api_do_not_reveal_guest_personal_data(): void
    {
        $secretName = 'Rahasia Budi Santoso';
        $secretPhone = '6281299998888';
        $secretEmail = 'budi.rahasia@example.com';

        $resepsionis = User::factory()->create(['role' => 'resepsionis', 'is_active' => true]);
        $visit = Visit::factory()->create([
            'full_name' => $secretName,
            'phone' => $secretPhone,
            'email' => $secretEmail,
            'queue_number' => 'PST-77',
            'status' => 'dipanggil',
            'channel' => 'kiosk',
            'visit_date' => now()->toDateString(),
            'called_at' => now(),
        ]);

        \App\Models\CallEvent::create([
            'visit_id' => $visit->id,
            'called_by' => $resepsionis->id,
            'counter_label' => 'Loket 1',
        ]);

        // 1. Halaman web display publik /layar
        $screenResponse = $this->get('/layar');
        $screenResponse->assertStatus(200);
        $screenResponse->assertDontSee($secretName);
        $screenResponse->assertDontSee($secretPhone);
        $screenResponse->assertDontSee($secretEmail);

        // 2. API polling /api/layar
        $apiResponse = $this->getJson('/api/layar');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment(['queue_number' => 'PST-77']);

        $jsonContent = $apiResponse->getContent();
        $this->assertStringNotContainsString($secretName, $jsonContent);
        $this->assertStringNotContainsString($secretPhone, $jsonContent);
        $this->assertStringNotContainsString($secretEmail, $jsonContent);
    }

    /**
     * AC-6: Foto privat dan berkas lampiran tidak bisa dibuka lewat URL tanpa login.
     */
    public function test_ac_06_private_photos_and_attachments_cannot_be_accessed_without_auth(): void
    {
        Storage::fake('local');
        $photoPath = 'photos/test_private_photo.jpg';
        Storage::disk('local')->put($photoPath, 'fake photo bytes');

        $visit = Visit::factory()->create([
            'photo_path' => $photoPath,
        ]);

        // Tamu tanpa login mengakses URL foto -> diarahkan ke login
        $unauthResponse = $this->get("/kunjungan/{$visit->id}/foto");
        $unauthResponse->assertRedirect('/login');

        // Login sebagai petugas terotorisasi -> foto berhasil diakses
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);
        $authResponse = $this->actingAs($petugas)->get("/kunjungan/{$visit->id}/foto");
        $authResponse->assertStatus(200);
        $this->assertEquals('image/jpeg', $authResponse->headers->get('Content-Type'));
    }

    /**
     * AC-7: Ekspor CSV memuat kolom sesuai filter yang aktif.
     */
    public function test_ac_07_csv_export_matches_active_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        Visit::factory()->create([
            'ticket_code' => 'KIO-20261028-0001',
            'full_name' => 'Tamu Kios Filter',
            'channel' => 'kiosk',
            'visit_date' => now()->toDateString(),
        ]);

        Visit::factory()->create([
            'ticket_code' => 'ONL-20261028-0002',
            'full_name' => 'Tamu Online Filter',
            'channel' => 'online',
            'visit_date' => now()->toDateString(),
        ]);

        // Ekspor dengan filter channel=kiosk
        $response = $this->actingAs($admin)->get('/admin/kunjungan/ekspor?channel=kiosk');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));

        $streamedContent = $response->streamedContent();
        $this->assertStringContainsString('Tamu Kios Filter', $streamedContent);
        $this->assertStringNotContainsString('Tamu Online Filter', $streamedContent);
    }

    /**
     * AC-8: /kios dapat diakses langsung via link tanpa perlu pendaftaran perangkat token.
     */
    public function test_ac_08_kiosk_route_is_accessible_directly_without_device_token(): void
    {
        $response = $this->get('/kios');

        $response->assertStatus(200);
        $response->assertSee('Buku Tamu');
    }

    /**
     * AC-9: File foto lebih dari 365 hari terhapus otomatis, sedangkan data teks kunjungan tetap tersimpan.
     */
    public function test_ac_09_photos_older_than_365_days_are_purged_while_text_data_is_preserved(): void
    {
        Storage::fake('local');

        // 1. Visit lama (370 hari lalu) dengan foto
        $oldPhotoPath = 'photos/old_visitor.jpg';
        Storage::disk('local')->put($oldPhotoPath, 'old photo content');

        $oldVisit = Visit::factory()->create([
            'full_name' => 'Tamu Tahun Lalu',
            'ticket_code' => 'KIO-OLD-0001',
            'visit_date' => now()->subDays(370)->toDateString(),
            'photo_path' => $oldPhotoPath,
            'institution' => 'Kantor Lama',
            'purpose' => 'pst',
            'detail' => 'Keperluan masa lalu',
        ]);

        // 2. Visit baru (30 hari lalu) dengan foto
        $recentPhotoPath = 'photos/recent_visitor.jpg';
        Storage::disk('local')->put($recentPhotoPath, 'recent photo content');

        $recentVisit = Visit::factory()->create([
            'full_name' => 'Tamu Bulan Lalu',
            'ticket_code' => 'KIO-RECENT-0002',
            'visit_date' => now()->subDays(30)->toDateString(),
            'photo_path' => $recentPhotoPath,
            'institution' => 'Kantor Baru',
        ]);

        // Jalankan perintah retensi foto
        $this->artisan('photos:purge-expired')
            ->expectsOutputToContain('Mencari foto kunjungan lebih dari 365 hari')
            ->assertSuccessful();

        // Verifikasi kunjungan lama:
        // - File foto dihapus dari disk lokal
        Storage::disk('local')->assertMissing($oldPhotoPath);
        // - Field photo_path di database diset null
        $oldVisit->refresh();
        $this->assertNull($oldVisit->photo_path);
        // - Data teks kunjungan TETAP ADA
        $this->assertEquals('Tamu Tahun Lalu', $oldVisit->full_name);
        $this->assertEquals('Kantor Lama', $oldVisit->institution);
        $this->assertEquals('Keperluan masa lalu', $oldVisit->detail);

        // Verifikasi kunjungan baru:
        // - File foto dan photo_path tetap utuh
        Storage::disk('local')->assertExists($recentPhotoPath);
        $recentVisit->refresh();
        $this->assertEquals($recentPhotoPath, $recentVisit->photo_path);
    }

    /**
     * AC-10: Security headers terpasang pada respons HTTP.
     */
    public function test_ac_10_security_headers_are_attached_to_responses(): void
    {
        $response = $this->get('/layanan');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', $response->headers->get('Permissions-Policy'));

        // Display TV /layar menggunakan SAMEORIGIN agar dapat di-frame jika diperlukan
        $screenResponse = $this->get('/layar');
        $screenResponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
