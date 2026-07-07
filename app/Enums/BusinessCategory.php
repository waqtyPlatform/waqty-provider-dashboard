<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canonical business category. Port of `normalizeBusinessCategory` +
 * `BUSINESS_TERMINOLOGY` (src/lib/waqty_contract.ts). Drives sidebar labels,
 * KPI wording, and the clinic-only patient-intake form.
 */
enum BusinessCategory: string
{
    case Salon = 'salon';
    case Barber = 'barber';
    case Clinic = 'clinic';
    case Spa = 'spa';
    case Nails = 'nails';
    case Other = 'other';

    /**
     * Deterministic mapping from a raw provider `category.name` (EN or AR).
     * Substring order matches the source exactly.
     */
    public static function normalize(?string $raw): self
    {
        $s = mb_strtolower(trim((string) $raw));

        return match (true) {
            $s === '' => self::Other,
            self::has($s, ['clinic', 'عياد', 'طب']) => self::Clinic,
            self::has($s, ['barber', 'حلاق', 'باربر']) => self::Barber,
            self::has($s, ['spa', 'سبا', 'منتجع']) => self::Spa,
            self::has($s, ['nail', 'أظافر', 'اظافر']) => self::Nails,
            self::has($s, ['salon', 'صالون', 'تجميل']) => self::Salon,
            default => self::Other,
        };
    }

    /** @return array{label:string, customer:string, staff:string, appointment:string, requiresIntake:bool} */
    public function terminology(): array
    {
        return match ($this) {
            self::Salon => ['label' => __('term.salon'), 'customer' => __('term.client'), 'staff' => __('term.stylist'), 'appointment' => __('term.appointment'), 'requiresIntake' => false],
            self::Barber => ['label' => __('term.barbershop'), 'customer' => __('term.client'), 'staff' => __('term.barber'), 'appointment' => __('term.appointment'), 'requiresIntake' => false],
            self::Clinic => ['label' => __('term.clinic'), 'customer' => __('term.patient'), 'staff' => __('term.doctor'), 'appointment' => __('term.visit'), 'requiresIntake' => true],
            self::Spa => ['label' => __('term.spa'), 'customer' => __('term.guest'), 'staff' => __('term.therapist'), 'appointment' => __('term.appointment'), 'requiresIntake' => false],
            self::Nails => ['label' => __('term.nails'), 'customer' => __('term.client'), 'staff' => __('term.nailArtist'), 'appointment' => __('term.appointment'), 'requiresIntake' => false],
            self::Other => ['label' => __('term.business'), 'customer' => __('term.client'), 'staff' => __('term.staff'), 'appointment' => __('term.appointment'), 'requiresIntake' => false],
        };
    }

    public function requiresIntake(): bool
    {
        return $this->terminology()['requiresIntake'];
    }

    /** @param array<int, string> $needles */
    private static function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
