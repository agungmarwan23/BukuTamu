<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Log;

class LogDriver implements NotificationDriverInterface
{
    public function send(string $recipient, string $message, array $context = []): array
    {
        Log::channel(config('notification.drivers.log.channel', 'stack'))->info(
            sprintf('[NOTIFIKASI - LOG DRIVER] Ke: %s | Tipe: %s | Pesan: %s', 
                $recipient, 
                $context['type'] ?? 'umum', 
                $message
            ),
            $context
        );

        return [
            'success' => true,
            'error' => null,
            'response' => [
                'driver' => 'log',
                'logged_at' => now()->toIso8601String(),
            ],
        ];
    }
}
