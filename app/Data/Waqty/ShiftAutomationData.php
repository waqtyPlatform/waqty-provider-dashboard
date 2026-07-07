<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A shift automation rule — /api/provider/settings/shift-automations.
 * `trigger` is one of shift_start|shift_end|late_checkin|missed_shift.
 * `action` is one of notify_manager|auto_clock_out|send_reminder.
 */
class ShiftAutomationData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public string $trigger = 'shift_start',
        public string $action = 'notify_manager',
        public bool $active = true,
    ) {}
}
