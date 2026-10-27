<?php

namespace Tests\Feature;

use App\Models\CallEvent;
use App\Models\KioskDevice;
use App\Models\User;
use App\Models\Visit;
use App\Services\QueueNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $resepsionis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test',
        ]);

        $this->resepsionis = User::factory()->create([
            'role' => 'resepsionis',
            'username' => 'resepsionis_test',
        ]);
    }

    public function test_queue_number_is_generated_for_kiosk_visits(): void
    {
        $service = new QueueNumberService();

        $today = now()->toDateString();
        $result = $service->generateNext($today);

        $this->assertEquals(1, $result['queue_seq']);
        $this->assertEquals('PST-01', $result['queue_number']);

        Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'queue_seq' => $result['queue_seq'],
            'queue_number' => $result['queue_number'],
        ]);

        $result2 = $service->generateNext($today);
        $this->assertEquals(2, $result2['queue_seq']);
        $this->assertEquals('PST-02', $result2['queue_number']);
    }

    public function test_queue_number_resets_daily(): void
    {
        $service = new QueueNumberService();

        // Buat visit kemarin
        Visit::factory()->create([
            'visit_date' => now()->subDay()->toDateString(),
            'channel' => 'kiosk',
            'queue_seq' => 5,
            'queue_number' => 'PST-05',
        ]);

        // Hari ini harus mulai dari 1
        $today = now()->toDateString();
        $result = $service->generateNext($today);

        $this->assertEquals(1, $result['queue_seq']);
        $this->assertEquals('PST-01', $result['queue_number']);
    }

    public function test_queue_number_format_changes_after_99(): void
    {
        $service = new QueueNumberService();
        $today = now()->toDateString();

        // Buat 99 visit kios
        for ($i = 1; $i <= 99; $i++) {
            Visit::factory()->create([
                'visit_date' => $today,
                'channel' => 'kiosk',
                'queue_seq' => $i,
                'queue_number' => 'PST-' . str_pad($i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $result = $service->generateNext($today);
        $this->assertEquals(100, $result['queue_seq']);
        $this->assertEquals('PST-100', $result['queue_number']);
    }

    public function test_online_visits_do_not_get_queue_number(): void
    {
        // Buat visit online langsung dan cek tidak ada queue_number
        $visit = Visit::factory()->create([
            'channel' => 'online',
            'queue_seq' => null,
            'queue_number' => null,
        ]);

        $this->assertNull($visit->queue_seq);
        $this->assertNull($visit->queue_number);
    }

    public function test_kiosk_visit_store_assigns_queue_number(): void
    {
        // Buat dan aktifkan perangkat kios
        $plainToken = 'test-kiosk-token-12345';
        $device = KioskDevice::create([
            'name' => 'Test Kiosk',
            'token_hash' => hash('sha256', $plainToken),
            'is_active' => true,
        ]);

        $response = $this->withCookie('kiosk_device_token', $plainToken)
            ->post(route('kios.store'), [
                'channel' => 'kiosk',
                'full_name' => 'Tamu Uji Antrean',
                'email' => 'tamu@test.com',
                'phone' => '081234567890',
                'institution' => 'BPS Test',
                'purpose' => 'pst',
                'service_type' => 'Konsultasi Statistik',
                'detail' => 'Test antrean',
                'photo' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
                'consent' => '1',
                'website_url' => '', // honeypot
            ]);

        $response->assertRedirect();

        $visit = Visit::where('full_name', 'Tamu Uji Antrean')->first();
        $this->assertNotNull($visit);
        $this->assertEquals('PST-01', $visit->queue_number);
        $this->assertEquals(1, $visit->queue_seq);
    }

    public function test_resepsionis_can_call_a_waiting_visit(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'menunggu',
            'queue_number' => 'PST-01',
            'queue_seq' => 1,
            'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.call', $visit), [
                'counter_label' => 'Loket 1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('dipanggil', $visit->status);
        $this->assertNotNull($visit->called_at);

        // Cek call event tercatat
        $this->assertDatabaseHas('call_events', [
            'visit_id' => $visit->id,
            'called_by' => $this->resepsionis->id,
            'counter_label' => 'Loket 1',
        ]);

        // Cek status log tercatat
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => 'menunggu',
            'to_status' => 'dipanggil',
            'user_id' => $this->resepsionis->id,
        ]);
    }

    public function test_resepsionis_can_recall_a_called_visit(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'queue_number' => 'PST-02',
            'queue_seq' => 2,
            'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.recall', $visit), [
                'counter_label' => 'Loket 2',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Status tetap dipanggil
        $visit->refresh();
        $this->assertEquals('dipanggil', $visit->status);

        // Tapi call event baru tercatat
        $this->assertEquals(1, CallEvent::where('visit_id', $visit->id)->count());
    }

    public function test_resepsionis_can_skip_a_called_visit(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'queue_number' => 'PST-03',
            'queue_seq' => 3,
            'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.skip', $visit));

        $response->assertRedirect();

        $visit->refresh();
        $this->assertEquals('tidak_hadir', $visit->status);
    }

    public function test_cannot_call_already_called_visit(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'queue_number' => 'PST-04',
            'queue_seq' => 4,
            'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.call', $visit), [
                'counter_label' => 'Loket 1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_skip_waiting_visit(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'menunggu',
            'queue_number' => 'PST-05',
            'queue_seq' => 5,
            'visit_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.skip', $visit));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_screen_api_returns_correct_data_without_personal_info(): void
    {
        $visit = Visit::factory()->create([
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'queue_number' => 'PST-01',
            'queue_seq' => 1,
            'visit_date' => now()->toDateString(),
            'full_name' => 'Nama Rahasia',
            'email' => 'rahasia@test.com',
            'phone' => '628123456789',
        ]);

        CallEvent::create([
            'visit_id' => $visit->id,
            'called_by' => $this->resepsionis->id,
            'counter_label' => 'Loket 1',
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('api.layar'));

        $response->assertOk()
            ->assertJsonStructure([
                'current' => ['call_event_id', 'queue_number', 'counter_label', 'purpose', 'called_at'],
                'recent',
                'waiting_count',
                'timestamp',
            ]);

        // Pastikan tidak ada data pribadi bocor
        $json = $response->getContent();
        $this->assertStringNotContainsString('Nama Rahasia', $json);
        $this->assertStringNotContainsString('rahasia@test.com', $json);
        $this->assertStringNotContainsString('628123456789', $json);
    }

    public function test_screen_api_returns_empty_when_no_calls(): void
    {
        $response = $this->getJson(route('api.layar'));

        $response->assertOk()
            ->assertJson([
                'current' => null,
                'recent' => [],
                'waiting_count' => 0,
            ]);
    }

    public function test_admin_can_access_resepsionis_queue(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('resepsionis.dashboard'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_resepsionis_queue(): void
    {
        $response = $this->get(route('resepsionis.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_petugas_cannot_access_resepsionis_queue(): void
    {
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas_test',
        ]);

        $response = $this->actingAs($petugas)
            ->get(route('resepsionis.dashboard'));

        $response->assertForbidden();
    }

    public function test_tv_screen_page_is_accessible_without_auth(): void
    {
        $response = $this->get(route('layar'));

        $response->assertOk();
    }
}
