<?php

namespace App\Contracts;

interface NotificationDriverInterface
{
    /**
     * Kirim notifikasi ke penerima tertentu.
     *
     * @param string $recipient Alamat email atau nomor HP penerima
     * @param string $message Isi pesan notifikasi
     * @param array $context Metadata tambahan (misal: subjek, tipe, id referensi)
     * @return array [bool $success, ?string $error, ?array $responsePayload]
     */
    public function send(string $recipient, string $message, array $context = []): array;
}
