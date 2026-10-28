<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailDriver implements NotificationDriverInterface
{
    public function send(string $recipient, string $message, array $context = []): array
    {
        try {
            $subject = $context['subject'] ?? 'Pemberitahuan Layanan PST BPS Kab. Indragiri Hilir';
            $fromAddress = config('notification.drivers.mail.from_address', config('mail.from.address'));
            $fromName = config('notification.drivers.mail.from_name', config('mail.from.name'));

            Mail::raw($message, function ($mail) use ($recipient, $subject, $fromAddress, $fromName) {
                $mail->to($recipient)
                    ->subject($subject);

                if ($fromAddress) {
                    $mail->from($fromAddress, $fromName);
                }
            });

            return [
                'success' => true,
                'error' => null,
                'response' => [
                    'driver' => 'mail',
                    'sent_at' => now()->toIso8601String(),
                ],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null,
            ];
        }
    }
}
