<?php

namespace Tests\Feature;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitAttachment;
use App\Models\VisitStatusLog;
use App\Services\DutyScheduleService;
use App\Services\OfficerAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetugasTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas1;
    protected User $petugas2;
    protected User $admin;
    protected User $resepsionis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas1 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas1_test',
            'name' => 'Petugas Satu',
        ]);

        $this->petugas2 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas2_test',
            'name' => 'Petugas Dua',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test',
            'name' => 'Admin Master',
        ]);

        $this->resepsionis = User::factory()->create([
            'role' => 'resepsionis',
            'username' => 'resepsionis_test',
            'name' => 'Resepsionis Lobi',
        ]);
    }

    public function test_guest_cannot_access_portal_petugas(): void
    {
        $response = $this->get(route('petugas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_resepsionis_cannot_access_portal_petugas(): void
    {
        $response = $this->actingAs($this->resepsionis)->get(route('petugas.index'));

        $response->assertForbidden();
    }

    public function test_petugas_can_view_portal_and_assigned_visits(): void
    {
        $today = now()->toDateString();

        // Visit yang ditugaskan ke petugas 1
        $visitMy = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'assigned_officer_id' => $this->petugas1->id,
            'queue_number' => 'PST-01',
            'full_name' => 'Tamu Milik Petugas 1',
        ]);

        // Visit yang ditugaskan ke petugas 2
        $visitOther = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'assigned_officer_id' => $this->petugas2->id,
            'queue_number' => 'PST-02',
            'full_name' => 'Tamu Milik Petugas 2',
        ]);

        $response = $this->actingAs($this->petugas1)->get(route('petugas.index'));

        $response->assertOk()
            ->assertSee('Tamu Milik Petugas 1')
            ->assertDontSee('Tamu Milik Petugas 2');
    }

    public function test_petugas_can_start_service_on_assigned_visit(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'assigned_officer_id' => $this->petugas1->id,
            'queue_number' => 'PST-01',
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.start', $visit));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('dilayani', $visit->status);
        $this->assertNotNull($visit->started_at);

        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => 'dipanggil',
            'to_status' => 'dilayani',
            'user_id' => $this->petugas1->id,
        ]);
    }

    public function test_petugas_cannot_start_service_assigned_to_another_officer(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'assigned_officer_id' => $this->petugas2->id,
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.start', $visit));

        $response->assertForbidden();
    }

    public function test_petugas_can_finish_service_with_note(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'dilayani',
            'assigned_officer_id' => $this->petugas1->id,
            'started_at' => now()->subMinutes(15),
            'queue_number' => 'PST-01',
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.finish', $visit), [
                'officer_note' => 'Konsultasi metadata statistik selesai dan memuaskan.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('selesai', $visit->status);
        $this->assertNotNull($visit->finished_at);
        $this->assertEquals('Konsultasi metadata statistik selesai dan memuaskan.', $visit->officer_note);

        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => 'dilayani',
            'to_status' => 'selesai',
            'user_id' => $this->petugas1->id,
        ]);
    }

    public function test_cannot_finish_visit_that_is_not_being_served(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'menunggu',
            'assigned_officer_id' => $this->petugas1->id,
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.finish', $visit), [
                'officer_note' => 'Langsung selesai tanpa layani',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_petugas_can_mark_visit_as_tidak_hadir_or_dibatalkan(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'dipanggil',
            'assigned_officer_id' => $this->petugas1->id,
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.status', $visit), [
                'status' => 'tidak_hadir',
                'officer_note' => 'Tamu tidak merespons panggilan loket',
            ]);

        $response->assertRedirect();
        $visit->refresh();
        $this->assertEquals('tidak_hadir', $visit->status);
        $this->assertEquals('Tamu tidak merespons panggilan loket', $visit->officer_note);
    }

    public function test_petugas_can_follow_up_online_visit(): void
    {
        $visit = Visit::factory()->online()->create([
            'status' => 'menunggu',
            'full_name' => 'Tamu Online Konsultasi',
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.online.status', $visit), [
                'status' => 'selesai',
                'officer_note' => 'Jawaban rilis BPS dikirim melalui lampiran email tamu.',
            ]);

        $response->assertRedirect(route('petugas.index', ['tab' => 'online']));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('selesai', $visit->status);
        $this->assertEquals('Jawaban rilis BPS dikirim melalui lampiran email tamu.', $visit->officer_note);
        $this->assertEquals($this->petugas1->id, $visit->assigned_officer_id);
    }

    public function test_auto_assignment_assigns_to_least_loaded_on_duty_officer(): void
    {
        $today = now()->toDateString();

        // Jadwal piket asli: petugas 1 dan petugas 2 sama-sama piket hari ini
        DutySchedule::create([
            'duty_date' => $today,
            'officer_id' => $this->petugas1->id,
        ]);
        DutySchedule::create([
            'duty_date' => $today,
            'officer_id' => $this->petugas2->id,
        ]);

        // Berikan beban 2 tiket aktif ke petugas 1
        Visit::factory()->count(2)->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'assigned_officer_id' => $this->petugas1->id,
            'status' => 'menunggu',
        ]);

        // Berikan beban 0 tiket ke petugas 2
        // Saat tiket baru dibuat, harus otomatis masuk ke petugas 2!
        $service = app(OfficerAssignmentService::class);
        $best = $service->findBestOfficerForDate($today);

        $this->assertNotNull($best);
        $this->assertEquals($this->petugas2->id, $best->id);
    }

    public function test_duty_swap_creates_swap_record_without_modifying_duty_schedules(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        // Petugas 1 punya jadwal asli besok
        $schedule = DutySchedule::create([
            'duty_date' => $tomorrow,
            'officer_id' => $this->petugas1->id,
        ]);

        $originalScheduleId = $schedule->id;
        $originalOfficerId = $schedule->officer_id;

        // Petugas 1 mengajukan swap dengan Petugas 2
        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.swap.store'), [
                'duty_date' => $tomorrow,
                'replacement_officer_id' => $this->petugas2->id,
                'reason' => 'Ada dinas survei ubinan padi di Tembilahan Hulu',
            ]);

        $response->assertRedirect(route('petugas.index', ['tab' => 'swap']));
        $response->assertSessionHas('success');

        // PASTIKAN: Baris duty_schedules TIDAK BERUBAH SAMA SEKALI (PRD FR-23)
        $schedule->refresh();
        $this->assertEquals($originalScheduleId, $schedule->id);
        $this->assertEquals($originalOfficerId, $schedule->officer_id);

        // Baris duty_swaps bertambah
        $this->assertDatabaseHas('duty_swaps', [
            'duty_date' => $tomorrow,
            'original_officer_id' => $this->petugas1->id,
            'replacement_officer_id' => $this->petugas2->id,
            'reason' => 'Ada dinas survei ubinan padi di Tembilahan Hulu',
            'swapped_by' => $this->petugas1->id,
        ]);
    }

    public function test_swap_correctly_updates_effective_on_duty_officers(): void
    {
        $today = now()->toDateString();

        // Petugas 1 punya jadwal asli
        DutySchedule::create([
            'duty_date' => $today,
            'officer_id' => $this->petugas1->id,
        ]);

        $dutyService = new DutyScheduleService();

        // Sebelum swap: Petugas 1 piket, Petugas 2 tidak piket
        $this->assertTrue($dutyService->isOfficerOnDuty($this->petugas1->id, $today));
        $this->assertFalse($dutyService->isOfficerOnDuty($this->petugas2->id, $today));

        // Buat swap: Petugas 1 diganti oleh Petugas 2
        DutySwap::create([
            'duty_date' => $today,
            'original_officer_id' => $this->petugas1->id,
            'replacement_officer_id' => $this->petugas2->id,
            'reason' => 'Sakit mendadak',
            'swapped_by' => $this->petugas1->id,
            'created_at' => now(),
        ]);

        // Setelah swap: Petugas 1 BUKAN piket lagi, Petugas 2 MENJADI piket efektif
        $this->assertFalse($dutyService->isOfficerOnDuty($this->petugas1->id, $today));
        $this->assertTrue($dutyService->isOfficerOnDuty($this->petugas2->id, $today));
    }

    public function test_guest_photo_is_private_and_requires_auth(): void
    {
        Storage::fake('local');

        $photoContent = 'fake-jpeg-binary-stream';
        $path = 'photos/2026-10/test-photo.jpg';
        Storage::disk('local')->put($path, $photoContent);

        $visit = Visit::factory()->create([
            'photo_path' => $path,
        ]);

        // Unauthenticated access dialihkan ke login
        $guestResponse = $this->get(route('visit.photo', $visit));
        $guestResponse->assertRedirect(route('login'));

        // Authenticated access berhasil melihat foto
        $authResponse = $this->actingAs($this->petugas1)->get(route('visit.photo', $visit));
        $authResponse->assertOk();
    }

    public function test_attachment_download_is_private_and_requires_auth(): void
    {
        Storage::fake('local');

        $attachmentContent = 'surat-permohonan-data-bps';
        $path = 'attachments/2026-10/surat.pdf';
        Storage::disk('local')->put($path, $attachmentContent);

        $visit = Visit::factory()->create();
        $attachment = VisitAttachment::create([
            'visit_id' => $visit->id,
            'path' => $path,
            'original_name' => 'surat_resmi.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ]);

        // Unauthenticated ditolak
        $guestResponse = $this->get(route('visit.attachment', $attachment));
        $guestResponse->assertRedirect(route('login'));

        // Authenticated berhasil mengunduh
        $authResponse = $this->actingAs($this->petugas1)->get(route('visit.attachment', $attachment));
        $authResponse->assertOk();
    }

    public function test_resepsionis_can_reassign_visit_officer(): void
    {
        $today = now()->toDateString();

        $visit = Visit::factory()->create([
            'visit_date' => $today,
            'channel' => 'kiosk',
            'status' => 'menunggu',
            'assigned_officer_id' => $this->petugas1->id,
        ]);

        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.reassign', $visit), [
                'officer_id' => $this->petugas2->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals($this->petugas2->id, $visit->assigned_officer_id);
    }
}
