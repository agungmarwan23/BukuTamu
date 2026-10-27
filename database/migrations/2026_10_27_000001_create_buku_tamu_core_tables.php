<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Kiosk Devices (perangkat terdaftar)
        Schema::create('kiosk_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_hash')->unique();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Settings (konfigurasi sistem)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 3. Visits (kunjungan / tiket tamu)
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date')->index();
            $table->enum('channel', ['kiosk', 'online'])->index();
            $table->string('ticket_code')->unique();
            $table->unsignedInteger('queue_seq')->nullable();
            $table->string('queue_number')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('institution');
            $table->enum('purpose', ['pst', 'non_pst', 'pengaduan']);
            $table->enum('pst_channel', ['offline', 'online'])->nullable();
            $table->string('service_type')->nullable();
            $table->string('non_pst_type')->nullable();
            $table->text('detail')->nullable();
            $table->string('complaint_title')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->enum('status', [
                'menunggu',
                'dipanggil',
                'dilayani',
                'selesai',
                'tidak_hadir',
                'dibatalkan'
            ])->default('menunggu');
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('officer_note')->nullable();
            $table->timestamp('skm_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['visit_date', 'queue_number']);
            $table->index(['visit_date', 'status']);
        });

        // 4. Visit Attachments (dokumen pendukung privat)
        Schema::create('visit_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->timestamps();
        });

        // 5. Visit Status Logs (audit trail transisi status)
        Schema::create('visit_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // 6. Call Events (riwayat panggilan antrean)
        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('called_by')->constrained('users')->cascadeOnDelete();
            $table->string('counter_label')->default('Loket 1');
            $table->timestamp('created_at')->useCurrent();
        });

        // 7. Duty Schedules (jadwal piket asli)
        Schema::create('duty_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('duty_date')->index();
            $table->foreignId('officer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['duty_date', 'officer_id']);
        });

        // 8. Duty Swaps (riwayat swap / penggantian piket)
        Schema::create('duty_swaps', function (Blueprint $table) {
            $table->id();
            $table->date('duty_date')->index();
            $table->foreignId('original_officer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('replacement_officer_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->foreignId('swapped_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // 9. Notification Logs (riwayat pengiriman notifikasi)
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('channel');
            $table->string('recipient');
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('duty_swaps');
        Schema::dropIfExists('duty_schedules');
        Schema::dropIfExists('call_events');
        Schema::dropIfExists('visit_status_logs');
        Schema::dropIfExists('visit_attachments');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('kiosk_devices');
    }
};
