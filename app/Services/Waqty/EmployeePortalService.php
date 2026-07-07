<?php

declare(strict_types=1);

namespace App\Services\Waqty;

/**
 * Employee-portal API (employee token surface) — /api/employee/*.
 * Every call goes through WaqtyApiClient::asEmployee() so it uses the
 * session employee token, not the provider one.
 */
class EmployeePortalService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    private function api(): WaqtyApiClient
    {
        return $this->api->asEmployee();
    }

    /** Today's bookings for the signed-in employee. @return array<int, array<string, mixed>> */
    public function todayBookings(): array
    {
        return $this->rows($this->api()->get('/api/employee/bookings', ['today' => true]));
    }

    /**
     * Attendance records in a date window (YYYY-MM-DD).
     *
     * @return array<int, array<string, mixed>>
     */
    public function attendance(string $from, string $to): array
    {
        return $this->rows($this->api()->get('/api/employee/attendance', [
            'date_from' => $from,
            'date_to' => $to,
        ]));
    }

    public function checkIn(): void
    {
        $this->api()->post('/api/employee/attendance/check-in');
    }

    public function checkOut(): void
    {
        $this->api()->post('/api/employee/attendance/check-out');
    }

    /** Scheduled shifts for the signed-in employee. @return array<int, array<string, mixed>> */
    public function shifts(): array
    {
        return $this->rows($this->api()->get('/api/employee/shifts'));
    }

    /**
     * Normalise a response that may be a bare list or a {data, meta} envelope.
     *
     * @return array<int, mixed>
     */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
