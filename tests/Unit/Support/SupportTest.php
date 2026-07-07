<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\BusinessCategory;
use App\Support\EgyptPhone;
use App\Support\Money;
use App\Support\RouteAccess;

it('formats money from minor units', function () {
    expect(Money::format(125000))->toBe('1,250 EGP')
        ->and(Money::format(125050))->toBe('1,250.50 EGP')
        ->and(Money::format(99900, false))->toBe('999')
        ->and(Money::compact(34000000))->toBe('340K EGP')
        ->and(Money::compact(296300000))->toBe('3.0M EGP')
        ->and(Money::compact(12500, false))->toBe('125')
        ->and(Money::vat(10000))->toBe(1400)
        ->and(Money::toMinor(12.5))->toBe(1250)
        ->and(Money::fromMinor(1250))->toBe(12.5);
});

it('validates and normalises Egyptian phones', function () {
    expect(EgyptPhone::isValid('01012345678'))->toBeTrue()
        ->and(EgyptPhone::isValid('010 1234-5678'))->toBeTrue()
        ->and(EgyptPhone::isValid('0131234567'))->toBeFalse()
        ->and(EgyptPhone::isValid('01312345678'))->toBeFalse()
        ->and(EgyptPhone::normalize('010 1234-5678'))->toBe('01012345678')
        ->and(EgyptPhone::toInternational('01012345678'))->toBe('+201012345678')
        ->and(EgyptPhone::toInternational('nope'))->toBeNull();
});

it('normalises business categories from EN and AR', function () {
    expect(BusinessCategory::normalize('Beauty Salon'))->toBe(BusinessCategory::Salon)
        ->and(BusinessCategory::normalize('صالون تجميل'))->toBe(BusinessCategory::Salon)
        ->and(BusinessCategory::normalize('Dental Clinic'))->toBe(BusinessCategory::Clinic)
        ->and(BusinessCategory::normalize('عيادة أسنان'))->toBe(BusinessCategory::Clinic)
        ->and(BusinessCategory::normalize('Barber Shop'))->toBe(BusinessCategory::Barber)
        ->and(BusinessCategory::normalize('حلاق'))->toBe(BusinessCategory::Barber)
        ->and(BusinessCategory::normalize('Spa & Wellness'))->toBe(BusinessCategory::Spa)
        ->and(BusinessCategory::normalize('Nail Studio'))->toBe(BusinessCategory::Nails)
        ->and(BusinessCategory::normalize('Gym'))->toBe(BusinessCategory::Other)
        ->and(BusinessCategory::normalize(''))->toBe(BusinessCategory::Other);
});

it('exposes business terminology', function () {
    expect(BusinessCategory::Clinic->requiresIntake())->toBeTrue()
        ->and(BusinessCategory::Clinic->terminology()['customer'])->toBe('Patient')
        ->and(BusinessCategory::Salon->requiresIntake())->toBeFalse()
        ->and(BusinessCategory::Barber->terminology()['staff'])->toBe('Barber');
});

it('enforces booking status transitions', function () {
    expect(BookingStatus::Pending->canTransition(BookingStatus::Confirmed))->toBeTrue()
        ->and(BookingStatus::Pending->canTransition(BookingStatus::Completed))->toBeFalse()
        ->and(BookingStatus::Completed->isTerminal())->toBeTrue()
        ->and(BookingStatus::deriveVisitStatus(['confirmed', 'confirmed']))->toBe(BookingStatus::Confirmed)
        ->and(BookingStatus::deriveVisitStatus(['completed', 'completed']))->toBe(BookingStatus::Completed)
        ->and(BookingStatus::deriveVisitStatus(['in_progress', 'pending']))->toBe(BookingStatus::InProgress)
        ->and(BookingStatus::deriveVisitStatus(['completed', 'pending']))->toBe(BookingStatus::InProgress)
        ->and(BookingStatus::deriveVisitStatus([]))->toBe(BookingStatus::Pending);
});

it('enforces route-level RBAC with prefix matching', function () {
    expect(RouteAccess::allowed('/employees/payroll', 'staff'))->toBeFalse()
        ->and(RouteAccess::allowed('/employees/payroll', 'manager'))->toBeTrue()
        ->and(RouteAccess::allowed('/settings/security', 'manager'))->toBeFalse()
        ->and(RouteAccess::allowed('/settings/security', 'admin'))->toBeTrue()
        ->and(RouteAccess::allowed('/employees/roles/edit/123', 'manager'))->toBeTrue()
        ->and(RouteAccess::allowed('/', 'staff'))->toBeTrue()
        ->and(RouteAccess::allowed('/customers', 'staff'))->toBeTrue();
});
