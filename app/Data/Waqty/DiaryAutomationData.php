<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A diary automation — an action triggered by a booking event.
 * /api/provider/settings/diary-automations.
 * `trigger` is one of booking_created|booking_cancelled|no_show|birthday.
 * `action` is one of send_sms|send_email|send_whatsapp|notify_staff.
 */
class DiaryAutomationData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public string $trigger = 'booking_created',
        public string $action = 'send_sms',
        public bool $active = true,
    ) {}
}
