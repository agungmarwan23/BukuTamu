<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppDriver implements NotificationDriverInterface
{
    public function send(string $recipient, string $message, array $context = []): array
    {
        $config = config('notification.drivers.whatsapp');
        $apiUrl = $config['api_url'] ?? '';
        $apiToken = $config['api_token'] ?? '';
        $sender = $config['sender_number'] ?? '';
        $timeout = (int) ($config['timeout'] ?? 10);

        // Normalisasi nomor telepon ke format internasional (misal 0812... -> 62812...)
        $normalizedPhone = preg_replace('/[^0-9]/', '', $recipient);
        if (str_starts_with($normalizedPhone, '0')) {
            $normalizedPhone = '62' . substr($normalizedPhone, 1);
        }

        if (empty($apiUrl)) {
            return [
                'success' => false,
                'error' => 'Konfigurasi WA_API_URL belum diatur.',
                'response' => null,
            ];
        }

        try {
            $payload = [
                'sender' => $sender,
                'recipient' => $normalizedPhone,
                'message' => $message,
                'type' => $context['type'] ?? 'text',
                'context' => $context,
            ];

            $request = Http::timeout($timeout);

            if (! empty($apiToken)) {
                $request = $request->withToken($apiToken);
            }

            $response = $request->post($apiUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'error' => null,
                    'response' => $response->json() ?? ['status' => $response->status()],
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: " . substr($response->body(), 0, 300),
                'response' => $response->json(),
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
