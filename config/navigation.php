<?php

/*
|--------------------------------------------------------------------------
| Sidebar navigation tree
|--------------------------------------------------------------------------
|
| Port of buildNavigation (src/lib/navigation.tsx). Group/child labels are
| translation keys resolved by NavigationService; groups flagged 'dynamic'
| take a business-type-aware label (bookings/customers/employees). `roles`
| gates visibility (combined with ROLE_RESTRICTED_ROUTES via RouteAccess).
|
*/

return [
    'primary' => [
        ['id' => 'home', 'label' => 'sidebar.dashboard', 'icon' => 'layout-dashboard', 'href' => '/'],

        ['id' => 'bookings', 'dynamic' => 'bookings', 'icon' => 'calendar-days', 'children' => [
            ['label' => 'sidebar.calendar', 'href' => '/bookings'],
            ['label' => 'sidebar.bookingList', 'href' => '/bookings/list'],
            ['label' => 'sidebar.newBooking', 'href' => '/bookings/new'],
            ['label' => 'sidebar.rooms', 'href' => '/bookings/rooms'],
            ['label' => 'waitlist.title', 'href' => '/bookings/waitlist'],
            ['label' => 'sidebar.payments', 'href' => '/bookings/payments'],
            ['label' => 'bookings.printTab', 'href' => '/bookings/print'],
        ]],

        ['id' => 'sales', 'label' => 'sidebar.sales', 'icon' => 'shopping-bag', 'children' => [
            ['label' => 'sidebar.services', 'href' => '/sales'],
            ['label' => 'sidebar.packages', 'href' => '/sales/packages'],
        ]],

        ['id' => 'clients', 'dynamic' => 'customers', 'icon' => 'users', 'children' => [
            ['label' => '@customers', 'href' => '/customers'],
            ['label' => 'sidebar.clientAccounts', 'href' => '/customers/clients'],
            ['label' => 'sidebar.groups', 'href' => '/customers/groups'],
            ['label' => 'sidebar.statements', 'href' => '/customers/statements'],
            ['label' => 'sidebar.lastVisits', 'href' => '/customers/last-visits'],
            ['label' => 'reviews.title', 'href' => '/customers/reviews'],
        ]],

        ['id' => 'team', 'dynamic' => 'employees', 'icon' => 'user-cog', 'roles' => ['admin', 'manager'], 'children' => [
            ['label' => '@employees', 'href' => '/employees'],

            ['header' => 'emp.secStructure'],
            ['label' => 'sidebar.departments', 'href' => '/employees/departments'],
            ['label' => 'sidebar.positions', 'href' => '/employees/positions'],
            ['label' => 'sidebar.roles', 'href' => '/employees/roles'],
            ['label' => 'sidebar.permissions', 'href' => '/employees/permissions'],
            ['label' => 'sidebar.transfers', 'href' => '/employees/transfers'],
            ['label' => 'sidebar.branchMgmt', 'href' => '/employees/branch-management'],

            ['header' => 'emp.secScheduling'],
            ['label' => 'sidebar.schedule', 'href' => '/employees/schedule'],
            ['label' => 'sidebar.availability', 'href' => '/employees/availability'],
            ['label' => 'sidebar.attendance', 'href' => '/employees/attendance'],
            ['label' => 'emp.timeTracking.title', 'href' => '/employees/time-tracking'],
            ['label' => 'emp.attendMethods.title', 'href' => '/employees/attend-methods'],
            ['label' => 'emp.attendanceSettings.title', 'href' => '/employees/attendance-settings'],
            ['label' => 'sidebar.fingerprints', 'href' => '/employees/fingerprints'],

            ['header' => 'emp.secPay'],
            ['label' => 'empLayout.tabPerformance', 'href' => '/employees/performance'],
            ['label' => 'emp.targets.title', 'href' => '/employees/targets'],
            ['label' => 'sidebar.payroll', 'href' => '/employees/payroll'],
            ['label' => 'sidebar.commissions', 'href' => '/employees/commissions'],
            ['label' => 'emp.commissions.title', 'href' => '/employees/commission-settings'],
            ['label' => 'emp.deductions.title', 'href' => '/employees/deductions'],
        ]],

        ['id' => 'money', 'label' => 'sidebar.money', 'icon' => 'wallet', 'children' => [
            ['label' => 'sidebar.log', 'href' => '/transactions'],
            ['label' => 'sidebar.expenses', 'href' => '/expenses'],
            ['label' => 'sidebar.returns', 'href' => '/returns', 'roles' => ['admin', 'manager']],
            ['label' => 'sidebar.settlement', 'href' => '/finance/settlement', 'roles' => ['admin']],
        ]],

        ['id' => 'marketing', 'label' => 'sidebar.marketing', 'icon' => 'megaphone', 'children' => [
            ['label' => 'sidebar.overview', 'href' => '/marketing'],
            ['label' => 'sidebar.offers', 'href' => '/marketing/offers'],
            ['label' => 'sidebar.packages', 'href' => '/marketing/packages'],
            ['label' => 'mktAds.title', 'href' => '/marketing/ads'],
            ['label' => 'sidebar.campaigns', 'href' => '/marketing/campaigns'],
            ['label' => 'sidebar.notifications', 'href' => '/marketing/notifications'],
            ['label' => 'sidebar.promoCodes', 'href' => '/marketing/promo-codes'],
            ['label' => 'sidebar.messages', 'href' => '/marketing/messages'],
            ['label' => 'sidebar.serviceGroups', 'href' => '/marketing/service-groups'],
            ['label' => 'announcements.title', 'href' => '/marketing/announcements'],
        ]],
    ],

    'footer' => [
        ['id' => 'reports', 'label' => 'sidebar.reports', 'icon' => 'bar-chart-3', 'roles' => ['admin'], 'children' => [
            ['label' => 'sidebar.reports', 'href' => '/reports'],
            ['label' => 'sidebar.revenue', 'href' => '/reports/revenue'],
            ['label' => 'reports.cat.bookings.title', 'href' => '/reports/bookings'],
            ['label' => 'reports.cat.clients.title', 'href' => '/reports/clients'],
            ['label' => 'reports.cat.employees.title', 'href' => '/reports/employees'],
            ['label' => 'reports.cat.services.title', 'href' => '/reports/services'],
        ]],

        ['id' => 'settings', 'label' => 'sidebar.settings', 'icon' => 'settings', 'roles' => ['admin'], 'children' => [
            ['header' => 'settings.secBasics'],
            ['label' => 'sidebar.general', 'href' => '/settings'],
            ['label' => 'sidebar.profile', 'href' => '/settings/profile'],
            ['label' => 'sidebar.branches', 'href' => '/settings/branches'],

            ['header' => 'settings.secCatalog'],
            ['label' => 'sidebar.services', 'href' => '/settings/services'],
            ['label' => 'sidebar.svcCategories', 'href' => '/settings/service-categories'],
            ['label' => 'sidebar.svcEmployees', 'href' => '/settings/service-employees'],
            ['label' => 'sidebar.svcPricing', 'href' => '/settings/service-pricing'],
            ['label' => 'sidebar.pricingGroups', 'href' => '/settings/pricing-groups'],

            ['header' => 'settings.secScheduling'],
            ['label' => 'sidebar.hours', 'href' => '/settings/hours'],
            ['label' => 'sidebar.shifts', 'href' => '/settings/shifts'],
            ['label' => 'sidebar.resources', 'href' => '/settings/resources'],
            ['label' => 'sidebar.roles', 'href' => '/settings/roles'],
            ['label' => 'sidebar.diaryAutomations', 'href' => '/settings/diary-automations'],
            ['label' => 'sidebar.shiftAutomations', 'href' => '/settings/shift-automations'],

            ['header' => 'settings.secMoney'],
            ['label' => 'sidebar.paymentMethods', 'href' => '/settings/payment-methods'],
            ['label' => 'sidebar.invoiceSettings', 'href' => '/settings/invoice'],
            ['label' => 'sidebar.safes', 'href' => '/settings/safes'],
            ['label' => 'sidebar.pettyCash', 'href' => '/settings/petty-cash-items'],
            ['label' => 'tipping.title', 'href' => '/settings/tipping'],
            ['label' => 'loyalty.title', 'href' => '/settings/loyalty'],

            ['header' => 'settings.secDevices'],
            ['label' => 'sidebar.devices', 'href' => '/settings/devices'],
            ['label' => 'sidebar.fpDevices', 'href' => '/settings/fingerprint-devices'],
            ['label' => 'sidebar.fpAreas', 'href' => '/settings/fingerprint-areas'],

            ['header' => 'settings.secAdvanced'],
            ['label' => 'sidebar.appearance', 'href' => '/settings/appearance'],
            ['label' => 'sidebar.localization', 'href' => '/settings/localization'],
            ['label' => 'sidebar.settingsNotifications', 'href' => '/settings/notifications'],
            ['label' => 'sidebar.security', 'href' => '/settings/security'],
            ['label' => 'sidebar.integrations', 'href' => '/settings/integrations'],
            ['label' => 'sidebar.subscription', 'href' => '/settings/subscription'],
            ['label' => 'sidebar.dataManagement', 'href' => '/settings/data'],
            ['label' => 'sidebar.auditLog', 'href' => '/settings/audit-log'],
        ]],

        ['id' => 'help', 'label' => 'help.title', 'icon' => 'help-circle', 'children' => [
            ['label' => 'help.title', 'href' => '/help'],
            ['label' => 'help.bugReport', 'href' => '/help/bug-report'],
        ]],
    ],
];
