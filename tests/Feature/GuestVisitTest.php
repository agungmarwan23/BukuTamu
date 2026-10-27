<?php

namespace Tests\Feature;

use App\Models\KioskDevice;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestVisitTest extends TestCase
{
    use RefreshDatabase;

    private function createKioskDevice(): array
    {
        $rawToken = 'kiosk_test_token_12345678901234567890';
        $tokenHash = hash('sha256', $rawToken);

        $device = KioskDevice::create([
            'name' => 'PC Kios Lobi 1',
            'token_hash' => $tokenHash,
            'is_active' => true,
        ]);

        return [$device, $rawToken];
    }

    public function test_unregistered_device_cannot_access_kiosk(): void
    {
        $response = $this->get('/kios');

        $response->assertStatus(403);
    }

    public function test_kiosk_activation_sets_cookie_and_allows_access(): void
    {
        [$device, $token] = $this->createKioskDevice();

        $response = $this->get("/kios/activate/{$token}");

        $response->assertRedirect('/kios');
        $response->assertCookie('kiosk_device_token', $token);
    }

    public function test_registered_device_can_access_kiosk(): void
    {
        [$device, $token] = $this->createKioskDevice();

        $response = $this->withCookie('kiosk_device_token', $token)->get('/kios');

        $response->assertStatus(200);
        $response->assertSee('KIOS BUKU TAMU DIGITAL');
    }

    public function test_online_form_is_accessible_to_public(): void
    {
        $this->get('/')->assertRedirect('/layanan');

        $response = $this->get('/layanan');
        $response->assertStatus(200);
        $response->assertSee('Formulir Permintaan Layanan');
    }

    public function test_kiosk_submission_requires_photo(): void
    {
        [$device, $token] = $this->createKioskDevice();

        $payload = [
            'channel' => 'kiosk',
            'full_name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'institution' => 'Bappeda Inhil',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Konsultasi data kemiskinan dan inflasi',
            'consent' => '1',
            // photo kosong
        ];

        $response = $this->withCookie('kiosk_device_token', $token)->post('/kios', $payload);

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_kiosk_submission_succeeds_with_photo_and_saves_photo_privately(): void
    {
        Storage::fake('local');
        [$device, $token] = $this->createKioskDevice();

        // 1x1 dummy base64 JPEG
        $dummyBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

        $payload = [
            'channel' => 'kiosk',
            'full_name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'institution' => 'Bappeda Inhil',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Konsultasi data PDRB',
            'photo' => $dummyBase64,
            'consent' => '1',
        ];

        $response = $this->withCookie('kiosk_device_token', $token)->post('/kios', $payload);

        $this->assertDatabaseCount('visits', 1);
        $visit = Visit::first();

        $this->assertEquals('kiosk', $visit->channel);
        $this->assertEquals('offline', $visit->pst_channel);
        $this->assertEquals('6281234567890', $visit->phone);
        $this->assertStringStartsWith('KIO-', $visit->ticket_code);
        $this->assertNotNull($visit->photo_path);

        Storage::disk('local')->assertExists($visit->photo_path);
        $response->assertRedirect(route('kios.success', ['ticket_code' => $visit->ticket_code]));
    }

    public function test_online_submission_succeeds_without_photo(): void
    {
        Storage::fake('local');

        $payload = [
            'channel' => 'online',
            'full_name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
            'phone' => '085211223344',
            'institution' => 'Universitas Riau',
            'purpose' => 'pst',
            'service_type' => 'Perpustakaan',
            'detail' => 'Mencari publikasi Inhil Dalam Angka',
            'consent' => '1',
        ];

        $response = $this->post('/layanan', $payload);

        $this->assertDatabaseCount('visits', 1);
        $visit = Visit::first();

        $this->assertEquals('online', $visit->channel);
        $this->assertEquals('online', $visit->pst_channel);
        $this->assertEquals('6285211223344', $visit->phone);
        $this->assertNull($visit->photo_path);
        $this->assertStringStartsWith('ONL-', $visit->ticket_code);

        $response->assertRedirect(route('layanan.success', ['ticket_code' => $visit->ticket_code]));
    }

    public function test_phone_number_is_normalized_to_62_format(): void
    {
        $payload = [
            'channel' => 'online',
            'full_name' => 'Ahmad Dahlan',
            'email' => 'ahmad@example.com',
            'phone' => '+62 813-9988-7766',
            'institution' => 'Umum',
            'purpose' => 'non_pst',
            'non_pst_type' => 'Konsultasi',
            'detail' => 'Konsultasi kerjasama riset',
            'consent' => '1',
        ];

        $this->post('/layanan', $payload);

        $visit = Visit::first();
        $this->assertEquals('6281399887766', $visit->phone);
    }

    public function test_conditional_validation_rules(): void
    {
        // 1. PST tanpa service_type -> gagal
        $response1 = $this->post('/layanan', [
            'channel' => 'online',
            'full_name' => 'Test Tamu',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'institution' => 'Dinas',
            'purpose' => 'pst',
            'detail' => 'Keperluan data',
            'consent' => '1',
        ]);
        $response1->assertSessionHasErrors('service_type');

        // 2. Non-PST tanpa non_pst_type -> gagal
        $response2 = $this->post('/layanan', [
            'channel' => 'online',
            'full_name' => 'Test Tamu',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'institution' => 'Dinas',
            'purpose' => 'non_pst',
            'detail' => 'Audiensi pimpinan',
            'consent' => '1',
        ]);
        $response2->assertSessionHasErrors('non_pst_type');

        // 3. Pengaduan tanpa complaint_title -> gagal
        $response3 = $this->post('/layanan', [
            'channel' => 'online',
            'full_name' => 'Test Tamu',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'institution' => 'Dinas',
            'purpose' => 'pengaduan',
            'detail' => 'Isi aduan',
            'consent' => '1',
        ]);
        $response3->assertSessionHasErrors('complaint_title');
    }

    public function test_honeypot_rejects_bot_submission(): void
    {
        $payload = [
            'website_url' => 'http://spam-bot-link.com', // honeypot diisi oleh bot
            'channel' => 'online',
            'full_name' => 'Bot Spammer',
            'email' => 'spam@bot.com',
            'phone' => '081234567890',
            'institution' => 'Spam Corp',
            'purpose' => 'pst',
            'service_type' => 'Perpustakaan',
            'detail' => 'Spam text',
            'consent' => '1',
        ];

        $response = $this->post('/layanan', $payload);

        $response->assertSessionHasErrors('website_url');
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_attachment_upload_stored_privately(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('surat_tugas.pdf', 500, 'application/pdf');

        $payload = [
            'channel' => 'online',
            'full_name' => 'Dewi Sartika',
            'email' => 'dewi@example.com',
            'phone' => '081234567890',
            'institution' => 'Dinas Pendidikan',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Permintaan data pendidikan',
            'attachment' => $file,
            'consent' => '1',
        ];

        $response = $this->post('/layanan', $payload);

        $visit = Visit::first();
        $this->assertCount(1, $visit->attachments);

        $attachment = $visit->attachments->first();
        $this->assertEquals('surat_tugas.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_oversized_attachment_is_rejected(): void
    {
        Storage::fake('local');

        // File 6 MB (melebihi batas 5 MB)
        $file = UploadedFile::fake()->create('besar.pdf', 6144, 'application/pdf');

        $payload = [
            'channel' => 'online',
            'full_name' => 'Dewi Sartika',
            'email' => 'dewi@example.com',
            'phone' => '081234567890',
            'institution' => 'Dinas Pendidikan',
            'purpose' => 'pst',
            'service_type' => 'Konsultasi Statistik',
            'detail' => 'Permintaan data',
            'attachment' => $file,
            'consent' => '1',
        ];

        $response = $this->post('/layanan', $payload);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('visits', 0);
    }
}
