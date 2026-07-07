<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Egyptian mobile validation/normalisation. Port of EGYPT_PHONE_REGEX +
 * isEgyptianPhone (src/lib/validations.ts) and toInternationalPhone (api.ts).
 */
final class EgyptPhone
{
    public const REGEX = '/^01[0125]\d{8}$/';

    public static function isValid(?string $value): bool
    {
        return (bool) preg_match(self::REGEX, self::normalize($value));
    }

    /** Strip spaces, hyphens and parentheses to bare digits. */
    public static function normalize(?string $value): string
    {
        return preg_replace('/[\s\-()]/', '', (string) $value) ?? '';
    }

    /** "01012345678" -> "+201012345678"; returns null if not a valid EG number. */
    public static function toInternational(?string $value): ?string
    {
        $digits = self::normalize($value);

        if (! preg_match(self::REGEX, $digits)) {
            return null;
        }

        return '+20'.substr($digits, 1);
    }
}
