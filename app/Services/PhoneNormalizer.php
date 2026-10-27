<?php

namespace App\Services;

class PhoneNormalizer
{
    /**
     * Normalize Indonesian phone number into 62... format.
     */
    public static function normalize(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Hapus karakter non-digit kecuali tanda tambah awal
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (empty($cleaned)) {
            return null;
        }

        // 0812... -> 62812...
        if (str_starts_with($cleaned, '0')) {
            return '62' . substr($cleaned, 1);
        }

        // 812... -> 62812...
        if (str_starts_with($cleaned, '8')) {
            return '62' . $cleaned;
        }

        // 62812... -> 62812...
        if (str_starts_with($cleaned, '62')) {
            return $cleaned;
        }

        return $cleaned;
    }
}
