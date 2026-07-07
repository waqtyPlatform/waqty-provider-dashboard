<?php

/*
|--------------------------------------------------------------------------
| Role-restricted routes
|--------------------------------------------------------------------------
|
| Verbatim port of ROLE_RESTRICTED_ROUTES (src/components/RoleGuard.tsx).
| Matched by exact path OR "{route}/" prefix. Consumed by both the
| EnforceRouteRestrictions middleware and the NavigationService so sidebar
| visibility and route protection can never drift.
|
*/

return [
    '/settings/security' => ['admin'],
    '/settings/roles' => ['admin'],
    '/settings/audit-log' => ['admin'],
    '/settings/subscription' => ['admin'],
    '/settings/integrations' => ['admin'],
    '/settings/devices' => ['admin'],

    '/employees/permissions' => ['admin'],
    '/employees/roles' => ['admin', 'manager'],
    '/employees/payroll' => ['admin', 'manager'],
    '/employees/commissions' => ['admin', 'manager'],
    '/employees/commission-settings' => ['admin'],
    '/employees/deductions' => ['admin', 'manager'],

    '/transactions/safe-balances' => ['admin', 'manager'],
    '/transactions/petty-cash' => ['admin', 'manager'],
    '/transactions/transfers' => ['admin', 'manager'],
];
