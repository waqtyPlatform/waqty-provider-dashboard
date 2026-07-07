<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Money is always stored as integer minor units (piastres; 100 = EGP 1.00).
 * Port of src/lib/money.ts (Egypt market: minor_units_per_major=100, VAT 14%).
 */
final class Money
{
    public const MINOR_PER_MAJOR = 100;

    public const VAT_RATE = 0.14;

    public static function toMinor(float|int $major): int
    {
        return (int) round($major * self::MINOR_PER_MAJOR);
    }

    public static function fromMinor(int $minor): float
    {
        return $minor / self::MINOR_PER_MAJOR;
    }

    public static function vat(int $minor): int
    {
        return (int) round($minor * self::VAT_RATE);
    }

    /** Currency label localised to the active app locale. */
    public static function label(): string
    {
        return app()->getLocale() === 'ar' ? 'جنيه' : 'EGP';
    }

    /**
     * Format minor units: "1,250 EGP", "1,250.50 EGP" (2 dp only when non-whole).
     */
    public static function format(int $minor, bool $withCurrency = true): string
    {
        $major = self::fromMinor($minor);
        $decimals = fmod($major, 1.0) === 0.0 ? 0 : 2;
        $out = number_format($major, $decimals, '.', ',');

        return $withCurrency ? $out.' '.self::label() : $out;
    }

    /** Compact form for dense displays: "340K EGP", "3.0M EGP". */
    public static function compact(int $minor, bool $withCurrency = true): string
    {
        $major = self::fromMinor($minor);
        $abs = abs($major);

        if ($abs >= 1_000_000) {
            $out = number_format($major / 1_000_000, 1, '.', ',').'M';
        } elseif ($abs >= 1_000) {
            $out = number_format(round($major / 1_000), 0, '.', ',').'K';
        } else {
            $out = number_format(round($major), 0, '.', ',');
        }

        return $withCurrency ? $out.' '.self::label() : $out;
    }
}
