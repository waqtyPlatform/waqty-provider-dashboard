<?php

use App\Livewire\Auth\EmployeeLogin;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\InviteClaim;
use App\Livewire\Auth\OnboardingWizard;
use App\Livewire\Auth\ProviderLogin;
use App\Livewire\Bookings\BookingDetail;
use App\Livewire\Bookings\BookingList;
use App\Livewire\Bookings\Calendar;
use App\Livewire\Bookings\NewBooking;
use App\Livewire\Bookings\Payments;
use App\Livewire\Bookings\PrintSchedule as BookingsPrintSchedule;
use App\Livewire\Bookings\Rooms;
use App\Livewire\Bookings\Waitlist;
use App\Livewire\Customers\ClientAccounts;
use App\Livewire\Customers\Detail as CustomerDetail;
use App\Livewire\Customers\Groups as CustomerGroups;
use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Customers\LastVisits;
use App\Livewire\Customers\Reviews;
use App\Livewire\Customers\Statements as ClientStatements;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Employees\AttendMethods as EmployeesAttendMethods;
use App\Livewire\Employees\Attendance as EmployeesAttendance;
use App\Livewire\Employees\AttendanceSettings as EmployeesAttendanceSettings;
use App\Livewire\Employees\Availability as EmployeesAvailability;
use App\Livewire\Employees\BranchManagement as EmployeesBranchManagement;
use App\Livewire\Employees\CommissionSettings as EmployeesCommissionSettings;
use App\Livewire\Employees\Commissions as EmployeesCommissions;
use App\Livewire\Employees\Deductions as EmployeesDeductions;
use App\Livewire\Employees\Departments as EmployeesDepartments;
use App\Livewire\Employees\Detail as EmployeesDetail;
use App\Livewire\Employees\Fingerprints as EmployeesFingerprints;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Employees\Payroll as EmployeesPayroll;
use App\Livewire\Employees\Performance as EmployeesPerformance;
use App\Livewire\Employees\Permissions as EmployeesPermissions;
use App\Livewire\Employees\Positions as EmployeesPositions;
use App\Livewire\Employees\Roles as EmployeesRoles;
use App\Livewire\Employees\Schedule as EmployeesSchedule;
use App\Livewire\Employees\Targets as EmployeesTargets;
use App\Livewire\Employees\TimeTracking as EmployeesTimeTracking;
use App\Livewire\Employees\Transfers as EmployeesTransfers;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Finance\Settlement;
use App\Livewire\Help\BugReport as HelpBugReport;
use App\Livewire\Help\Center as HelpCenter;
use App\Livewire\Marketing\Ads as MarketingAds;
use App\Livewire\Marketing\Announcements as MarketingAnnouncements;
use App\Livewire\Marketing\Campaigns as MarketingCampaigns;
use App\Livewire\Marketing\Hub as MarketingHub;
use App\Livewire\Marketing\MarketingNotifications;
use App\Livewire\Marketing\Messages as MarketingMessages;
use App\Livewire\Marketing\Offers as MarketingOffers;
use App\Livewire\Marketing\Packages as MarketingPackages;
use App\Livewire\Marketing\PromoCodes as MarketingPromoCodes;
use App\Livewire\Marketing\ServiceGroups as MarketingServiceGroups;
use App\Livewire\Portal\AttendanceHistory as PortalAttendance;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use App\Livewire\Portal\ShiftsSchedule as PortalShifts;
use App\Livewire\Reports\Overview as ReportsOverview;
use App\Livewire\Reports\ReportCategory;
use App\Livewire\Reports\ReportDetail;
use App\Livewire\Reports\Revenue as ReportsRevenue;
use App\Livewire\Returns\CancelDownPayment;
use App\Livewire\Returns\CashRefund;
use App\Livewire\Returns\Index as ReturnsIndex;
use App\Livewire\Returns\PettyCashRefund;
use App\Livewire\Sales\Packages as SalesPackages;
use App\Livewire\Services\Index as ServicesIndex;
use App\Livewire\Settings\Appearance as SettingsAppearance;
use App\Livewire\Settings\AuditLog as SettingsAuditLog;
use App\Livewire\Settings\BranchDetail as SettingsBranchDetail;
use App\Livewire\Settings\Branches as SettingsBranches;
use App\Livewire\Settings\BusinessHours as SettingsBusinessHours;
use App\Livewire\Settings\DataManagement;
use App\Livewire\Settings\Devices as SettingsDevices;
use App\Livewire\Settings\DiaryAutomations as SettingsDiaryAutomations;
use App\Livewire\Settings\FingerprintAreas as SettingsFingerprintAreas;
use App\Livewire\Settings\FingerprintDevices as SettingsFingerprintDevices;
use App\Livewire\Settings\General as SettingsGeneral;
use App\Livewire\Settings\Integrations as SettingsIntegrations;
use App\Livewire\Settings\Invoice as SettingsInvoice;
use App\Livewire\Settings\Localization as SettingsLocalization;
use App\Livewire\Settings\Loyalty as SettingsLoyalty;
use App\Livewire\Settings\Notifications as SettingsNotifications;
use App\Livewire\Settings\PaymentMethods as SettingsPaymentMethods;
use App\Livewire\Settings\PettyCashItems as SettingsPettyCashItems;
use App\Livewire\Settings\PricingGroups as SettingsPricingGroups;
use App\Livewire\Settings\Profile as SettingsProfile;
use App\Livewire\Settings\Resources as SettingsResources;
use App\Livewire\Settings\Roles as SettingsRoles;
use App\Livewire\Settings\Safes as SettingsSafes;
use App\Livewire\Settings\Security as SettingsSecurity;
use App\Livewire\Settings\ServiceCategories as SettingsServiceCategories;
use App\Livewire\Settings\ServiceEmployees as SettingsServiceEmployees;
use App\Livewire\Settings\ServicePricing as SettingsServicePricing;
use App\Livewire\Settings\ServiceCreate as SettingsServiceCreate;
use App\Livewire\Settings\Services as SettingsServices;
use App\Livewire\Settings\ShiftAutomations as SettingsShiftAutomations;
use App\Livewire\Settings\ShiftTemplates as SettingsShiftTemplates;
use App\Livewire\Settings\Subscription as SettingsSubscription;
use App\Livewire\Settings\Tipping as SettingsTipping;
use App\Livewire\Transactions\AdvancePayments;
use App\Livewire\Transactions\BestSales;
use App\Livewire\Transactions\CashSales;
use App\Livewire\Transactions\ClientSales;
use App\Livewire\Transactions\Dailies;
use App\Livewire\Transactions\Index as TransactionsIndex;
use App\Livewire\Transactions\PackageSales;
use App\Livewire\Transactions\PettyCash;
use App\Livewire\Transactions\SafeBalances;
use App\Livewire\Transactions\Shifts;
use App\Livewire\Transactions\Transfers;
use App\Services\Waqty\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest (public) routes
|--------------------------------------------------------------------------
*/

Route::get('/login', ProviderLogin::class)->name('login');

Route::post('/logout', function (AuthService $auth) {
    $auth->logout();

    return redirect()->route('login');
})->name('logout');

// Language preference (works for guests and authenticated users).
Route::post('/preferences/locale', function (Request $request) {
    $locale = $request->input('locale') === 'ar' ? 'ar' : 'en';
    session([config('waqty.session.locale') => $locale]);
    cookie()->queue(cookie('waqty_language', $locale, 60 * 24 * 365));

    return back();
})->name('pref.locale');

Route::get('/forgot-password', ForgotPassword::class)->name('password.forgot');
Route::get('/employee-portal/login', EmployeeLogin::class)->name('employee-portal.login');

Route::get('/onboarding', OnboardingWizard::class)->name('onboarding');

// Public staff-invite claim (guest layout, no provider session required).
Route::get('/invite/{token}', InviteClaim::class)->name('invite.claim');

// Employee portal (employee token surface).
Route::post('/employee-portal/logout', function () {
    session()->forget([config('waqty.session.employee_token'), config('waqty.session.employee_profile')]);
    session()->regenerate();

    return redirect()->route('employee-portal.login');
})->name('employee-portal.logout');

Route::middleware('waqty.employee')->prefix('employee-portal')->name('employee-portal.')->group(function () {
    Route::get('/dashboard', PortalDashboard::class)->name('dashboard');
    Route::get('/attendance', PortalAttendance::class)->name('attendance');
    Route::get('/shifts', PortalShifts::class)->name('shifts');
});

/*
|--------------------------------------------------------------------------
| Authenticated provider routes
|--------------------------------------------------------------------------
*/

Route::middleware(['waqty.provider', 'waqty.roles'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    // Bookings — order matters: static segments before the {uuid} catch-all.
    Route::get('/bookings', Calendar::class)->name('bookings');
    Route::get('/bookings/list', BookingList::class)->name('bookings.list');
    Route::get('/bookings/new', NewBooking::class)->name('bookings.new');
    Route::get('/bookings/payments', Payments::class)->name('bookings.payments');
    Route::get('/bookings/waitlist', Waitlist::class)->name('bookings.waitlist');
    Route::get('/bookings/rooms', Rooms::class)->name('bookings.rooms');
    Route::get('/bookings/print', BookingsPrintSchedule::class)->name('bookings.print');
    Route::get('/bookings/{uuid}', BookingDetail::class)->name('bookings.detail');

    Route::get('/customers', CustomersIndex::class)->name('customers');
    Route::get('/customers/clients', ClientAccounts::class)->name('customers.clients');
    Route::get('/customers/groups', CustomerGroups::class)->name('customers.groups');
    Route::get('/customers/statements', ClientStatements::class)->name('customers.statements');
    Route::get('/customers/last-visits', LastVisits::class)->name('customers.last-visits');
    Route::get('/customers/reviews', Reviews::class)->name('customers.reviews');
    Route::get('/customers/{uuid}', CustomerDetail::class)->name('customers.detail');
    Route::get('/employees', EmployeesIndex::class)->name('employees');
    Route::get('/employees/departments', EmployeesDepartments::class)->name('employees.departments');
    Route::get('/employees/positions', EmployeesPositions::class)->name('employees.positions');
    Route::get('/employees/deductions', EmployeesDeductions::class)->name('employees.deductions');
    Route::get('/employees/targets', EmployeesTargets::class)->name('employees.targets');
    Route::get('/employees/transfers', EmployeesTransfers::class)->name('employees.transfers');
    Route::get('/employees/availability', EmployeesAvailability::class)->name('employees.availability');
    Route::get('/employees/performance', EmployeesPerformance::class)->name('employees.performance');
    Route::get('/employees/branch-management', EmployeesBranchManagement::class)->name('employees.branch-management');
    Route::get('/employees/attendance', EmployeesAttendance::class)->name('employees.attendance');
    Route::get('/employees/time-tracking', EmployeesTimeTracking::class)->name('employees.time-tracking');
    Route::get('/employees/attend-methods', EmployeesAttendMethods::class)->name('employees.attend-methods');
    Route::get('/employees/fingerprints', EmployeesFingerprints::class)->name('employees.fingerprints');
    Route::get('/employees/roles', EmployeesRoles::class)->name('employees.roles');
    Route::get('/employees/permissions', EmployeesPermissions::class)->name('employees.permissions');
    Route::get('/employees/attendance-settings', EmployeesAttendanceSettings::class)->name('employees.attendance-settings');
    Route::get('/employees/schedule', EmployeesSchedule::class)->name('employees.schedule');
    Route::get('/employees/commission-settings', EmployeesCommissionSettings::class)->name('employees.commission-settings');
    Route::get('/employees/payroll', EmployeesPayroll::class)->name('employees.payroll');
    Route::get('/employees/commissions', EmployeesCommissions::class)->name('employees.commissions');
    // Dynamic detail — MUST stay after all static /employees/* routes.
    Route::get('/employees/{uuid}', EmployeesDetail::class)->name('employees.detail');
    Route::get('/sales', ServicesIndex::class)->name('sales');
    Route::get('/sales/packages', SalesPackages::class)->name('sales.packages');

    // Money
    Route::get('/transactions', TransactionsIndex::class)->name('transactions');
    Route::get('/transactions/cash-sales', CashSales::class)->name('transactions.cash-sales');
    Route::get('/transactions/client-sales', ClientSales::class)->name('transactions.client-sales');
    Route::get('/transactions/advance-payments', AdvancePayments::class)->name('transactions.advance-payments');
    Route::get('/transactions/petty-cash', PettyCash::class)->name('transactions.petty-cash');
    Route::get('/transactions/transfers', Transfers::class)->name('transactions.transfers');
    Route::get('/transactions/safe-balances', SafeBalances::class)->name('transactions.safe-balances');
    Route::get('/transactions/shifts', Shifts::class)->name('transactions.shifts');
    Route::get('/transactions/dailies', Dailies::class)->name('transactions.dailies');
    Route::get('/transactions/best-sales', BestSales::class)->name('transactions.best-sales');
    Route::get('/transactions/package-sales', PackageSales::class)->name('transactions.package-sales');
    Route::get('/expenses', ExpensesIndex::class)->name('expenses');
    Route::get('/returns', ReturnsIndex::class)->name('returns');
    Route::get('/returns/cash-refund', CashRefund::class)->name('returns.cash-refund');
    Route::get('/returns/cancel-down-payment', CancelDownPayment::class)->name('returns.cancel-down-payment');
    Route::get('/returns/petty-cash-refund', PettyCashRefund::class)->name('returns.petty-cash-refund');

    // Reports
    Route::get('/reports', ReportsOverview::class)->name('reports');
    Route::get('/reports/revenue', ReportsRevenue::class)->name('reports.revenue');
    Route::get('/reports/{category}', ReportCategory::class)->name('reports.category');
    Route::get('/reports/{category}/{report}', ReportDetail::class)->name('reports.detail');

    Route::get('/finance/settlement', Settlement::class)->name('finance.settlement');

    // Marketing
    Route::get('/marketing', MarketingHub::class)->name('marketing');
    Route::get('/marketing/packages', MarketingPackages::class)->name('marketing.packages');
    Route::get('/marketing/offers', MarketingOffers::class)->name('marketing.offers');
    Route::get('/marketing/promo-codes', MarketingPromoCodes::class)->name('marketing.promo-codes');
    Route::get('/marketing/announcements', MarketingAnnouncements::class)->name('marketing.announcements');
    Route::get('/marketing/service-groups', MarketingServiceGroups::class)->name('marketing.service-groups');
    Route::get('/marketing/campaigns', MarketingCampaigns::class)->name('marketing.campaigns');
    Route::get('/marketing/messages', MarketingMessages::class)->name('marketing.messages');
    Route::get('/marketing/notifications', MarketingNotifications::class)->name('marketing.notifications');
    Route::get('/marketing/ads', MarketingAds::class)->name('marketing.ads');

    // Settings
    Route::get('/settings', SettingsGeneral::class)->name('settings');
    Route::get('/settings/hours', SettingsBusinessHours::class)->name('settings.hours');
    Route::get('/settings/payment-methods', SettingsPaymentMethods::class)->name('settings.payment-methods');
    Route::get('/settings/localization', SettingsLocalization::class)->name('settings.localization');
    Route::get('/settings/appearance', SettingsAppearance::class)->name('settings.appearance');
    Route::get('/settings/notifications', SettingsNotifications::class)->name('settings.notifications');
    Route::get('/settings/invoice', SettingsInvoice::class)->name('settings.invoice');
    Route::get('/settings/tipping', SettingsTipping::class)->name('settings.tipping');
    Route::get('/settings/loyalty', SettingsLoyalty::class)->name('settings.loyalty');
    Route::get('/settings/safes', SettingsSafes::class)->name('settings.safes');
    Route::get('/settings/resources', SettingsResources::class)->name('settings.resources');
    Route::get('/settings/petty-cash-items', SettingsPettyCashItems::class)->name('settings.petty-cash-items');
    Route::get('/settings/services', SettingsServices::class)->name('settings.services');
    Route::get('/settings/services/new', SettingsServiceCreate::class)->name('settings.services.new');
    Route::get('/settings/roles', SettingsRoles::class)->name('settings.roles');
    Route::get('/settings/service-categories', SettingsServiceCategories::class)->name('settings.service-categories');
    Route::get('/settings/service-employees', SettingsServiceEmployees::class)->name('settings.service-employees');
    Route::get('/settings/service-pricing', SettingsServicePricing::class)->name('settings.service-pricing');
    Route::get('/settings/security', SettingsSecurity::class)->name('settings.security');
    Route::get('/settings/branches', SettingsBranches::class)->name('settings.branches');
    Route::get('/settings/branches/{uuid}', SettingsBranchDetail::class)->name('settings.branches.detail');
    Route::get('/settings/pricing-groups', SettingsPricingGroups::class)->name('settings.pricing-groups');
    Route::get('/settings/shifts', SettingsShiftTemplates::class)->name('settings.shifts');
    Route::get('/settings/diary-automations', SettingsDiaryAutomations::class)->name('settings.diary-automations');
    Route::get('/settings/shift-automations', SettingsShiftAutomations::class)->name('settings.shift-automations');
    Route::get('/settings/devices', SettingsDevices::class)->name('settings.devices');
    Route::get('/settings/fingerprint-devices', SettingsFingerprintDevices::class)->name('settings.fingerprint-devices');
    Route::get('/settings/fingerprint-areas', SettingsFingerprintAreas::class)->name('settings.fingerprint-areas');
    Route::get('/settings/profile', SettingsProfile::class)->name('settings.profile');
    Route::get('/settings/audit-log', SettingsAuditLog::class)->name('settings.audit-log');
    Route::get('/settings/subscription', SettingsSubscription::class)->name('settings.subscription');
    Route::get('/settings/integrations', SettingsIntegrations::class)->name('settings.integrations');
    Route::get('/settings/data', DataManagement::class)->name('settings.data');

    // Help
    Route::get('/help', HelpCenter::class)->name('help');
    Route::get('/help/bug-report', HelpBugReport::class)->name('help.bug-report');
});

// TEMP: local-only shell preview (seeds a fake session so the authenticated
// chrome renders without the external API). Remove before shipping.
if (app()->environment('local')) {
    Route::get('/dev-login/{type?}', function (string $type = 'clinic') {
        session([
            config('waqty.session.provider_token') => 'dev-token',
            config('waqty.session.provider_profile') => [
                'name' => 'Dr. Sara Ahmed',
                'email' => $type.'@waqty.com',
                'role' => 'admin',
                'business_type' => $type,
                'category' => ['name' => ucfirst($type)],
            ],
        ]);

        return redirect('/');
    });

    Route::get('/dev-login-employee', function () {
        session([
            config('waqty.session.employee_token') => 'dev-employee-token',
            config('waqty.session.employee_profile') => [
                'name' => 'Omar Khaled',
                'email' => 'omar@waqty.com',
            ],
        ]);

        return redirect()->route('employee-portal.dashboard');
    });
}
