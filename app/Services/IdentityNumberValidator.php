<?php

namespace App\Services;

use Illuminate\Validation\Rule;

/**
 * [VALIDASI NIP & NIS]
 * Helper terpusat untuk memvalidasi format NIP dan NIS:
 * - NIP: wajib angka (0-9), maksimal 18 digit.
 * - NIS: wajib angka (0-9), panjang digit bebas (tidak boleh huruf).
 */
class IdentityNumberValidator
{
    // Pola regex: hanya angka 0-9, maksimal 18 digit untuk NIP
    public const NIP_REGEX = '/^[0-9]{1,18}$/';

    // Pola regex: hanya angka 0-9, jumlah digit bebas untuk NIS (tidak boleh huruf/simbol)
    public const NIS_REGEX = '/^[0-9]+$/';

    /**
     * Aturan validasi NIP (angka maksimal 18 digit + unik di tabel users).
     *
     * @return array<int, mixed>
     */
    public static function nipRules(bool $required = false, ?int $ignoreUserId = null): array
    {
        $uniqueRule = 'unique:users,nip'.($ignoreUserId !== null ? ','.$ignoreUserId : '');

        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:'.self::NIP_REGEX,
            $uniqueRule,
        ];
    }

    /**
     * Aturan format NIP opsional (dipakai saat role non-guru/staf tidak membutuhkan NIP).
     *
     * @return array<int, string>
     */
    public static function optionalNipFormatRules(): array
    {
        return ['nullable', 'string', 'regex:'.self::NIP_REGEX];
    }

    /**
     * Aturan validasi NIS (hanya angka, panjang bebas + unik per role di tabel users).
     *
     * @return array<int, mixed>
     */
    public static function nisRules(int $roleId, bool $required = true, ?int $ignoreUserId = null): array
    {
        $uniqueRule = Rule::unique('users', 'nis')
            ->where(fn ($query) => $query->where('role_id', $roleId));

        if ($ignoreUserId !== null) {
            $uniqueRule->ignore($ignoreUserId);
        }

        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:'.self::NIS_REGEX,
            $uniqueRule,
        ];
    }

    /**
     * Aturan format NIS opsional (dipakai saat role non-siswa tidak membutuhkan NIS).
     *
     * @return array<int, string>
     */
    public static function optionalNisFormatRules(): array
    {
        return ['nullable', 'string', 'regex:'.self::NIS_REGEX];
    }

    /**
     * Cek apakah string NIP valid (hanya angka 1-18 digit).
     */
    public static function isValidNip(string $value): bool
    {
        return preg_match(self::NIP_REGEX, $value) === 1;
    }

    /**
     * Cek apakah string NIS valid (hanya angka, panjang bebas).
     */
    public static function isValidNis(string $value): bool
    {
        return preg_match(self::NIS_REGEX, $value) === 1;
    }

    /**
     * Pesan error bahasa Indonesia untuk validasi NIP dan NIS.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'nip.regex' => 'NIP wajib berupa angka dan maksimal 18 digit (tidak boleh mengandung huruf).',
            'nis.regex' => 'NIS wajib berupa angka dan tidak boleh mengandung huruf.',
        ];
    }
}

