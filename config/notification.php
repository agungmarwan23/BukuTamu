<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Notification Driver
    |--------------------------------------------------------------------------
    |
    | Driver aktif untuk pengiriman notifikasi (pengingat piket, tautan SKM).
    | Pilihan yang didukung: 'log', 'mail', 'whatsapp'.
    |
    */
    'default' => env('NOTIFICATION_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Driver Configurations
    |--------------------------------------------------------------------------
    */
    'drivers' => [

        'log' => [
            'channel' => env('LOG_CHANNEL', 'stack'),
        ],

        'mail' => [
            'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@bps-inhil.go.id'),
            'from_name' => env('MAIL_FROM_NAME', 'Buku Tamu BPS Kab. Indragiri Hilir'),
        ],

        'whatsapp' => [
            // Kerangka HTTP client untuk gateway WhatsApp
            'api_url' => env('WA_API_URL', 'https://api.whatsapp-gateway.local/v1/messages'),
            'api_token' => env('WA_API_TOKEN', ''),
            'sender_number' => env('WA_SENDER_NUMBER', ''),
            'timeout' => env('WA_TIMEOUT', 10), // detik
        ],

    ],

];
