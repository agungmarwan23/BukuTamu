<?php

namespace App\Services\Notification;

use App\Contracts\NotificationDriverInterface;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\Notification\Drivers\LogDriver;
use App\Services\Notification\Drivers\MailDriver;
use App\Services\Notification\Drivers\WhatsAppDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class NotificationService
{
    /**
     * Resolusi instance driver notifikasi berdasarkan nama kanal.
     */
    public function driver(?string $name = null): NotificationDriverInterface
    {
        $channel = $name ?: config('notification.default', 'log');

        return match (strtolower($channel)) {
            'log' => new LogDriver(),
            'mail' => new MailDriver(),
            'whatsapp', 'wa' => new WhatsAppDriver(),
            default => throw new InvalidArgumentException("Driver notifikasi [{$channel}] tidak didukung."),
        };
    }

    /**
     * Kirim notifikasi umum dan catat ke tabel notification_logs.
     * Kegagalan pengiriman tidak akan menjatuhkan alur utama aplikasi (fail-safe).
     */
    public function send(string $type, ?string $channel, string $recipient, string $message, array $context = []): NotificationLog
    {
        $activeChannel = $channel ?: config('notification.default', 'log');

        // 1. Buat catatan log awal dengan status pending
        $log = NotificationLog::create([
            'type' => $type,
            'channel' => $activeChannel,
            'recipient' => $recipient,
            'payload' => array_merge(['message' => $message], $context),
            'status' => 'pending',
            'error' => null,
            'created_at' => now(),
        ]);

        try {
            $driver = $this->driver($activeChannel);
            $result = $driver->send($recipient, $message, $context);

            if (! empty($result['success'])) {
                $log->update([
                    'status' => 'sent',
                    'error' => null,
                ]);
            } else {
                $errorMsg = $result['error'] ?? 'Pengiriman gagal tanpa pesan error.';
                $log->update([
                    'status' => 'failed',
                    'error' => $errorMsg,
                ]);
                Log::warning("Gagal mengirim notifikasi [{$type}] ke [{$recipient}] via [{$activeChannel}]: {$errorMsg}");
            }
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            Log::error("Pengecualian saat mengirim notifikasi [{$type}] ke [{$recipient}] via [{$activeChannel}]: " . $e->getMessage());
        }

        return $log;
    }

    /**
     * Kirim pesan pengingat jadwal piket harian kepada petugas (FR-40).
     */
    public function sendDutyReminder(User $officer, Carbon|string $date): ?NotificationLog
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $formattedDate = $carbonDate->locale('id')->translatedFormat('l, d F Y');

        $activeChannel = config('notification.default', 'log');
        $recipient = match ($activeChannel) {
            'mail' => $officer->email,
            'whatsapp', 'wa' => $officer->phone,
            default => $officer->email ?: $officer->phone ?: $officer->username,
        };

        if (empty($recipient)) {
            Log::warning("Pengingat piket untuk petugas {$officer->name} dilewati: tidak memiliki alamat email/nomor telepon.");
            return null;
        }

        $message = sprintf(
            "Yth. Bapak/Ibu %s,\n\nMengingatkan bahwa pada hari ini (%s), Anda terjadwal sebagai Petugas Pelayanan Statistik Terpadu (PST) di BPS Kabupaten Indragiri Hilir.\n\nSelamat bertugas melayani masyarakat!",
            $officer->name,
            $formattedDate
        );

        $context = [
            'type' => 'duty_reminder',
            'subject' => "Pengingat Jadwal Piket PST BPS Kab. Indragiri Hilir - {$formattedDate}",
            'officer_id' => $officer->id,
            'duty_date' => $carbonDate->toDateString(),
        ];

        return $this->send('duty_reminder', $activeChannel, $recipient, $message, $context);
    }

    /**
     * Kirim tautan Survei Kepuasan Masyarakat (SKM) ke tamu saat kunjungan selesai (FR-41).
     * Jika settings.skm_url kosong, pengiriman dilewati dan dicatat sebagai peringatan.
     */
    public function sendSkmSurvey(Visit $visit): ?NotificationLog
    {
        $skmUrl = Setting::getValue('skm_url');

        if (empty(trim((string) $skmUrl))) {
            Log::warning("Pengiriman survei SKM dilewati untuk kunjungan #{$visit->id} (Tiket: {$visit->ticket_code}): Pengaturan 'skm_url' masih kosong.");
            return null;
        }

        $activeChannel = config('notification.default', 'log');
        $recipient = match ($activeChannel) {
            'mail' => $visit->email,
            'whatsapp', 'wa' => $visit->phone,
            default => $visit->phone ?: $visit->email,
        };

        if (empty($recipient)) {
            Log::warning("Pengiriman survei SKM dilewati untuk kunjungan #{$visit->id}: Tamu tidak menyertakan email maupun nomor telepon.");
            return null;
        }

        $message = sprintf(
            "Halo %s,\n\nTerima kasih telah berkunjung ke Pelayanan Statistik Terpadu (PST) BPS Kabupaten Indragiri Hilir (Kode Tiket: %s).\n\nUntuk meningkatkan kualitas pelayanan kami, mohon kesediaan Bapak/Ibu mengisi Survei Kepuasan Masyarakat (SKM) pada tautan berikut:\n%s\n\nMasukan Anda sangat berharga bagi kami.",
            $visit->full_name,
            $visit->ticket_code,
            $skmUrl
        );

        $context = [
            'type' => 'skm_survey',
            'subject' => 'Survei Kepuasan Layanan PST BPS Kab. Indragiri Hilir',
            'visit_id' => $visit->id,
            'ticket_code' => $visit->ticket_code,
            'skm_url' => $skmUrl,
        ];

        $log = $this->send('skm_survey', $activeChannel, $recipient, $message, $context);

        // Jika berhasil terkirim, catat waktu pengiriman pada model Visit
        if ($log->status === 'sent') {
            $visit->update(['skm_sent_at' => now()]);
        }

        return $log;
    }
}
