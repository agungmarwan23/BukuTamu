<?php

namespace Tests\Feature;

use App\Models\DutySchedule;
use App\Models\DutySwap;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas1;
    protected User $petugas2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test',
            'email' => 'admin@bps-inhil.go.id',
            'phone' => '081234567890',
        ]);

        $this->petugas1 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas1',
            'name' => 'Petugas Satu',
            'email' => 'petugas1@bps-inhil.go.id',
            'phone' => '081234567891',
        ]);

        $this->petugas2 = User::factory()->create([
            'role' => 'petugas',
            'username' => 'petugas2',
            'name' => 'Petugas Dua',
            'email' => 'petugas2@bps-inhil.go.id',
            'phone' => '081234567892',
        ]);
    }

    public function test_log_driver_records_sent_status_to_database(): void
    {
        config(['notification.default' => 'log']);

        $service = app(NotificationService::class);
        $log = $service->send('test_type', 'log', '08123456789', 'Pesan pengujian log');

        $this->assertInstanceOf(NotificationLog::class, $log);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals('log', $log->channel);
        $this->assertEquals('test_type', $log->type);
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'sent',
            'recipient' => '08123456789',
        ]);
    }

    public function test_mail_driver_sends_email_and_logs(): void
    {
        Mail::fake();
        config(['notification.default' => 'mail']);

        $service = app(NotificationService::class);
        $log = $service->send('test_mail', 'mail', 'tamu@example.com', 'Halo dari mail driver', [
            'subject' => 'Subjek Email Uji',
        ]);

        $this->assertEquals('sent', $log->status);
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'channel' => 'mail',
            'status' => 'sent',
        ]);
    }

    public function test_whatsapp_driver_success_via_http_client(): void
    {
        config([
            'notification.default' => 'whatsapp',
            'notification.drivers.whatsapp.api_url' => 'https://api.wa.local/send',
            'notification.drivers.whatsapp.api_token' => 'dummy_token_123',
            'notification.drivers.whatsapp.sender_number' => '628999999999',
        ]);

        Http::fake([
            'https://api.wa.local/send' => Http::response(['status' => 'success', 'message_id' => 'WA123'], 200),
        ]);

        $service = app(NotificationService::class);
        $log = $service->send('test_wa', 'whatsapp', '08123456789', 'Halo via WhatsApp');

        $this->assertEquals('sent', $log->status);
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);
    }

    public function test_whatsapp_driver_handles_failure_without_throwing_exception(): void
    {
        config([
            'notification.default' => 'whatsapp',
            'notification.drivers.whatsapp.api_url' => 'https://api.wa.local/send',
        ]);

        Http::fake([
            'https://api.wa.local/send' => Http::response(['error' => 'Gateway timeout'], 504),
        ]);

        $service = app(NotificationService::class);
        $log = $service->send('test_wa_fail', 'whatsapp', '08123456789', 'Halo gagal');

        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->error);
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'channel' => 'whatsapp',
            'status' => 'failed',
        ]);
    }

    public function test_duty_reminders_command_sends_to_scheduled_officer(): void
    {
        // Tetapkan jadwal untuk hari kerja mendatang (misal hari Senin)
        $monday = Carbon::parse('next monday');
        $dateStr = $monday->toDateString();

        DutySchedule::create([
            'duty_date' => $dateStr,
            'officer_id' => $this->petugas1->id,
            'created_by' => $this->admin->id,
        ]);

        $this->artisan('duty:send-reminders', ['--date' => $dateStr])
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'type' => 'duty_reminder',
            'recipient' => $this->petugas1->email ?: $this->petugas1->phone ?: $this->petugas1->username,
            'status' => 'sent',
        ]);
    }

    public function test_duty_reminders_command_respects_duty_swap(): void
    {
        $monday = Carbon::parse('next monday');
        $dateStr = $monday->toDateString();

        // Jadwal asli: Petugas 1
        DutySchedule::create([
            'duty_date' => $dateStr,
            'officer_id' => $this->petugas1->id,
            'created_by' => $this->admin->id,
        ]);

        // Swap: Petugas 1 digantikan Petugas 2
        DutySwap::create([
            'duty_date' => $dateStr,
            'original_officer_id' => $this->petugas1->id,
            'replacement_officer_id' => $this->petugas2->id,
            'reason' => 'Izin dinas luar kantor',
            'swapped_by' => $this->petugas1->id,
            'created_at' => now(),
        ]);

        $this->artisan('duty:send-reminders', ['--date' => $dateStr])
            ->assertSuccessful();

        // Pengingat harus terkirim ke Petugas 2 (pengganti), bukan Petugas 1
        $this->assertDatabaseHas('notification_logs', [
            'type' => 'duty_reminder',
            'recipient' => $this->petugas2->email ?: $this->petugas2->phone ?: $this->petugas2->username,
            'status' => 'sent',
        ]);

        $this->assertDatabaseMissing('notification_logs', [
            'type' => 'duty_reminder',
            'recipient' => $this->petugas1->email ?: $this->petugas1->phone ?: $this->petugas1->username,
        ]);
    }

    public function test_duty_reminders_command_skips_weekends_unless_forced(): void
    {
        $sunday = Carbon::parse('next sunday')->toDateString();

        DutySchedule::create([
            'duty_date' => $sunday,
            'officer_id' => $this->petugas1->id,
            'created_by' => $this->admin->id,
        ]);

        // Tanpa --force, harus dilewati
        $this->artisan('duty:send-reminders', ['--date' => $sunday])
            ->expectsOutputToContain('akhir pekan')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notification_logs', [
            'type' => 'duty_reminder',
        ]);

        // Dengan --force, harus dikirim
        $this->artisan('duty:send-reminders', ['--date' => $sunday, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'type' => 'duty_reminder',
        ]);
    }

    public function test_finish_service_sends_skm_survey_when_skm_url_configured(): void
    {
        Setting::setValue('skm_url', 'https://skm.bps.go.id/survey/inhil');

        $visit = Visit::factory()->create([
            'visit_date' => now()->toDateString(),
            'channel' => 'kiosk',
            'queue_number' => 'PST-01',
            'status' => 'dilayani',
            'assigned_officer_id' => $this->petugas1->id,
            'full_name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567899',
            'skm_sent_at' => null,
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.finish', $visit), [
                'officer_note' => 'Konsultasi SAKERNAS selesai.',
            ]);

        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('selesai', $visit->status);
        $this->assertNotNull($visit->skm_sent_at);

        $this->assertDatabaseHas('notification_logs', [
            'type' => 'skm_survey',
            'status' => 'sent',
        ]);
    }

    public function test_finish_service_skips_skm_survey_when_skm_url_is_empty(): void
    {
        Setting::setValue('skm_url', ''); // kosongkan

        $visit = Visit::factory()->create([
            'visit_date' => now()->toDateString(),
            'channel' => 'kiosk',
            'queue_number' => 'PST-02',
            'status' => 'dilayani',
            'assigned_officer_id' => $this->petugas1->id,
            'email' => 'tamu@example.com',
            'skm_sent_at' => null,
        ]);

        $response = $this->actingAs($this->petugas1)
            ->post(route('petugas.finish', $visit), [
                'officer_note' => 'Selesai.',
            ]);

        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('selesai', $visit->status);
        $this->assertNull($visit->skm_sent_at); // Tetap null

        $this->assertDatabaseMissing('notification_logs', [
            'type' => 'skm_survey',
        ]);
    }

    public function test_admin_can_download_duty_schedules_template(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.schedules.template'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_import_duty_schedules_via_csv(): void
    {
        $csvContent = "tanggal,username,catatan\n2026-11-10,petugas1,Piket 10 Nov\n2026-11-11,petugas2,Piket 11 Nov\n";
        $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.schedules.import'), [
                'csv_file' => $file,
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('duty_schedules', [
            'duty_date' => '2026-11-10',
            'officer_id' => $this->petugas1->id,
        ]);

        $this->assertDatabaseHas('duty_schedules', [
            'duty_date' => '2026-11-11',
            'officer_id' => $this->petugas2->id,
        ]);
    }

    public function test_admin_can_import_duty_schedules_with_overwrite(): void
    {
        // Sudah ada jadwal 2026-11-12 untuk petugas 1
        DutySchedule::create([
            'duty_date' => '2026-11-12',
            'officer_id' => $this->petugas1->id,
            'created_by' => $this->admin->id,
        ]);

        // File CSV baru menugaskan petugas 2 pada 2026-11-12
        $csvContent = "tanggal,username\n2026-11-12,petugas2\n";
        $file = UploadedFile::fake()->createWithContent('jadwal.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.schedules.import'), [
                'csv_file' => $file,
                'overwrite' => '1',
            ]);

        $response->assertSessionHas('success');

        // Petugas harus berganti menjadi petugas 2
        $this->assertDatabaseHas('duty_schedules', [
            'duty_date' => '2026-11-12',
            'officer_id' => $this->petugas2->id,
        ]);
    }
}
