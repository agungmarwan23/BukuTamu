<?php

namespace App\Http\Requests;

use App\Services\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => PhoneNormalizer::normalize($this->input('phone')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isKiosk = $this->input('channel') === 'kiosk';

        return [
            // Anti-bot honeypot: harus kosong
            'website_url' => ['nullable', 'max:0'],

            // Metadata saluran
            'channel' => ['required', Rule::in(['kiosk', 'online'])],

            // Langkah 1: Data Diri
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:20'],
            'institution' => ['required', 'string', 'max:255'],
            'purpose' => ['required', Rule::in(['pst', 'non_pst', 'pengaduan'])],

            // Langkah 2: Kondisional Layanan
            'service_type' => [
                Rule::requiredIf($this->input('purpose') === 'pst'),
                'nullable',
                Rule::in(['Konsultasi Statistik', 'Perpustakaan', 'Rekomendasi Kegiatan Statistik']),
            ],
            'non_pst_type' => [
                Rule::requiredIf($this->input('purpose') === 'non_pst'),
                'nullable',
                Rule::in(['Konsultasi', 'Kerjasama', 'Lainnya']),
            ],
            'complaint_title' => [
                Rule::requiredIf($this->input('purpose') === 'pengaduan'),
                'nullable',
                'string',
                'max:255',
            ],
            'detail' => ['required', 'string', 'max:3000'],

            // Lampiran Berkas Opsional (maks 5MB = 5120KB)
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,xlsx,docx',
                'max:5120',
            ],

            // Foto: Wajib di kios, tidak diminta di online
            'photo' => [
                $isKiosk ? 'required' : 'nullable',
                'string',
                function ($attribute, $value, $fail) use ($isKiosk) {
                    if ($isKiosk) {
                        if (blank($value) || ! str_starts_with($value, 'data:image/')) {
                            $fail('Foto tamu wajib diambil melalui kamera lobi.');
                        }
                    }
                },
            ],

            // Persetujuan Pemrosesan Data Pribadi (UU PDP)
            'consent' => ['accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website_url.max' => 'Pengiriman terdeteksi sebagai spam.',
            'channel.required' => 'Saluran pendaftaran harus ditentukan.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'phone.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'phone.min' => 'Nomor HP minimal 9 digit.',
            'institution.required' => 'Asal instansi / organisasi / umum wajib diisi.',
            'purpose.required' => 'Tujuan layanan wajib dipilih.',
            'service_type.required' => 'Jenis layanan PST wajib dipilih.',
            'non_pst_type.required' => 'Kategori non-PST wajib dipilih.',
            'complaint_title.required' => 'Judul aduan wajib diisi.',
            'detail.required' => 'Rincian keperluan atau isi aduan wajib diisi.',
            'attachment.mimes' => 'Format file pendukung harus berupa PDF, JPG, PNG, XLSX, atau DOCX.',
            'attachment.max' => 'Ukuran file pendukung maksimal 5 MB.',
            'photo.required' => 'Foto tamu wajib diambil melalui kamera lobi sebelum mengirim form.',
            'consent.accepted' => 'Anda harus menyetujui pernyataan persetujuan pemrosesan data pribadi untuk melanjutkan.',
        ];
    }
}
