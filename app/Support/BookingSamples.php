<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Deterministic sample bookings for graceful degradation when the live API is
 * unreachable (mirrors the source CAL_SEED / FALLBACK_* approach). Employee
 * uuids line up with EmployeeService::fallbackData so calendar columns match.
 */
final class BookingSamples
{
    /**
     * A day's worth of bookings, dated to $date so the calendar always has data.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forDate(string $date): array
    {
        $rows = [
            ['emp' => ['E001', 'د. سارة أحمد'], 'client' => 'فاطمة رشاد', 'phone' => '01011112222', 'service' => 'صبغة شعر', 'dur' => 90, 'start' => '09:00', 'status' => 'confirmed', 'price' => 45000, 'pay' => 'paid'],
            ['emp' => ['E003', 'خالد حسن'], 'client' => 'عمر خالد', 'phone' => '01022223333', 'service' => 'قصّة شعر كلاسيك', 'dur' => 30, 'start' => '09:30', 'status' => 'completed', 'price' => 15000, 'pay' => 'paid'],
            ['emp' => ['E001', 'د. سارة أحمد'], 'client' => 'نور الدين', 'phone' => '01033334444', 'service' => 'جلسة عناية بالبشرة', 'dur' => 60, 'start' => '11:00', 'status' => 'in_progress', 'price' => 40000, 'pay' => 'partial'],
            ['emp' => ['E004', 'ياسمين فاروق'], 'client' => 'مريم عادل', 'phone' => '01044445555', 'service' => 'مساج الأنسجة العميقة', 'dur' => 60, 'start' => '10:00', 'status' => 'confirmed', 'price' => 55000, 'pay' => 'unpaid'],
            ['emp' => ['E003', 'خالد حسن'], 'client' => 'يوسف علي', 'phone' => '01055556666', 'service' => 'تهذيب اللحية', 'dur' => 30, 'start' => '11:30', 'status' => 'pending', 'price' => 8000, 'pay' => 'unpaid'],
            ['emp' => ['E004', 'ياسمين فاروق'], 'client' => 'سلمى إبراهيم', 'phone' => '01066667777', 'service' => 'مانيكير', 'dur' => 45, 'start' => '12:30', 'status' => 'confirmed', 'price' => 20000, 'pay' => 'paid'],
            ['emp' => ['E001', 'د. سارة أحمد'], 'client' => 'هناء فتحي', 'phone' => '01077778888', 'service' => 'مكياج عرائس', 'dur' => 120, 'start' => '14:00', 'status' => 'confirmed', 'price' => 150000, 'pay' => 'partial'],
            ['emp' => ['E003', 'خالد حسن'], 'client' => 'كريم مصطفى', 'phone' => '01088889999', 'service' => 'قصّة شعر كلاسيك', 'dur' => 30, 'start' => '15:00', 'status' => 'cancelled', 'price' => 15000, 'pay' => 'unpaid'],
            ['emp' => ['E004', 'ياسمين فاروق'], 'client' => 'ليلى حسن', 'phone' => '01099990000', 'service' => 'أظافر جل', 'dur' => 60, 'start' => '16:00', 'status' => 'confirmed', 'price' => 30000, 'pay' => 'unpaid'],
        ];

        return array_map(function ($r, $i) use ($date) {
            [$empUuid, $empName] = $r['emp'];
            $end = self::addMinutes($r['start'], $r['dur']);

            return [
                'uuid' => 'BK-'.str_pad((string) ($i + 1001), 4, '0', STR_PAD_LEFT),
                'branch_uuid' => 'B1',
                'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'],
                'service_uuid' => 'S'.$i,
                'service' => ['name' => $r['service'], 'estimated_duration_minutes' => $r['dur']],
                'employee_uuid' => $empUuid,
                'employee' => ['uuid' => $empUuid, 'name' => $empName],
                'user' => ['name' => $r['client'], 'phone' => $r['phone']],
                'booking_date' => $date,
                'start_time' => $r['start'].':00',
                'end_time' => $end.':00',
                'status' => $r['status'],
                'payment_status' => $r['pay'],
                'price' => $r['price'],
                'notes' => null,
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * Sample calendar columns — active employees the sample bookings reference.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function employees(): array
    {
        return [
            ['uuid' => 'E001', 'name' => 'د. سارة أحمد', 'branch_uuid' => 'B1', 'active' => true, 'blocked' => false, 'position' => 'المالك'],
            ['uuid' => 'E003', 'name' => 'خالد حسن', 'branch_uuid' => 'B1', 'active' => true, 'blocked' => false, 'position' => 'مصفف شعر'],
            ['uuid' => 'E004', 'name' => 'ياسمين فاروق', 'branch_uuid' => 'B1', 'active' => true, 'blocked' => false, 'position' => 'معالج'],
        ];
    }

    /** A single sample booking for the detail page fallback. @return array<string, mixed> */
    public static function one(string $uuid, string $date): array
    {
        $day = self::forDate($date);
        foreach ($day as $b) {
            if ($b['uuid'] === $uuid) {
                return $b;
            }
        }

        return $day[0];
    }

    /**
     * Sample activity trail for the detail timeline.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activities(): array
    {
        return [
            ['uuid' => 'A1', 'event' => 'created', 'label' => 'تم إنشاء الحجز', 'actor_type' => 'staff', 'actor_name' => 'الاستقبال', 'created_at' => '2026-07-02 08:12:00'],
            ['uuid' => 'A2', 'event' => 'confirmed', 'label' => 'تم تأكيد الحجز', 'actor_type' => 'staff', 'actor_name' => 'منى عادل', 'created_at' => '2026-07-02 08:20:00'],
            ['uuid' => 'A3', 'event' => 'reminder_sent', 'label' => 'تم إرسال تذكير إلى العميل', 'actor_type' => 'system', 'actor_name' => null, 'created_at' => '2026-07-03 09:00:00'],
        ];
    }

    private static function addMinutes(string $hhmm, int $minutes): string
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        $total = $h * 60 + $m + $minutes;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
