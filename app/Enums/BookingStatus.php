<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canonical booking lifecycle. Port of the `BookingStatus` union,
 * `BOOKING_TRANSITIONS`, and `deriveVisitStatus` (src/lib/waqty_contract.ts).
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /** @return array<int, self> legal next states */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProgress, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransition(self $to): bool
    {
        return $this === $to || in_array($to, $this->transitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * Collapse per-line statuses into a single visit status (source deriveVisitStatus).
     *
     * @param  array<int, string|self>  $lineStatuses
     */
    public static function deriveVisitStatus(array $lineStatuses): self
    {
        $statuses = array_map(
            fn ($s) => $s instanceof self ? $s : (self::tryFrom((string) $s) ?? self::Pending),
            $lineStatuses,
        );

        if ($statuses === []) {
            return self::Pending;
        }

        $some = fn (self $s) => in_array($s, $statuses, true);
        $every = fn (self $s) => count(array_filter($statuses, fn ($x) => $x === $s)) === count($statuses);

        return match (true) {
            $some(self::InProgress) => self::InProgress,
            $every(self::Completed) => self::Completed,
            $every(self::NoShow) => self::NoShow,
            $every(self::Cancelled) => self::Cancelled,
            $some(self::Completed) => self::InProgress,
            $some(self::Confirmed) => self::Confirmed,
            default => self::Pending,
        };
    }

    public function label(): string
    {
        return __('status.'.$this->value);
    }

    /**
     * Hex accent for calendar blocks / charts (port of the source status tokens;
     * in_progress reads as "arrived" purple on the reception board).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Confirmed => '#3b82f6',
            self::InProgress => '#8b5cf6',
            self::Completed => '#10b981',
            self::Cancelled => '#ef4444',
            self::NoShow => '#6b7280',
        };
    }
}
