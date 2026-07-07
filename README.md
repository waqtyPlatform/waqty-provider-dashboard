# Waqty — Service Provider Dashboard (Laravel + Livewire)

A **UI-only** rebuild of the Waqty service-provider dashboard in **Laravel 13 + Livewire 4**, ported from the original [Next.js frontend](https://github.com/waqtyPlatform/Waqty-Service-provider). It is a fully **bilingual (English / Arabic) RTL** management console for salons, clinics, barbershops, spas, and nail studios.

The app owns **no local domain database**. It is a server-side client of the external Waqty REST API — every screen renders through a *Livewire component → domain service → API client → DTO* pipeline. The primary user is a **non-technical, Arabic-first business owner**, so the UI is engineered to read naturally in Arabic RTL with no technical jargon leaking through.

<p>
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3+-777bb4">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13-ff2d20">
  <img alt="Livewire" src="https://img.shields.io/badge/Livewire-4-fb70a9">
  <img alt="Tailwind" src="https://img.shields.io/badge/Tailwind-4-38bdf8">
  <img alt="Tests" src="https://img.shields.io/badge/tests-438%20passing-3ba55d">
</p>

---

## Table of contents

- [Feature overview](#feature-overview)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [Environment variables](#environment-variables)
- [Project structure](#project-structure)
- [Architecture](#architecture)
- [Design system](#design-system)
- [Internationalization & RTL](#internationalization--rtl)
- [Adding a new screen (recipe)](#adding-a-new-screen-recipe)
- [Testing](#testing)
- [Code style](#code-style)
- [Conventions & gotchas](#conventions--gotchas)
- [Deployment notes](#deployment-notes)

---

## Feature overview

~90 provider screens + the employee portal, across 14 modules:

| Module | Highlights |
|---|---|
| **Dashboard** | KPI strip, booking-status donut, revenue area chart, top clients/staff/services, next appointment |
| **Bookings** | Day/week/month calendar, list, detail + activity timeline, multi-step new-booking wizard, rooms grid, waitlist, payments, printable schedule sheet |
| **Customers / CRM** | List + CRUD, detail (statements/reviews/staff-notes), groups, statements, last-visits, reviews |
| **Employees** | Core CRUD + full HR: departments, positions, roles & permission matrices, transfers, availability, schedule grid, attendance, time-tracking, attendance methods/settings, fingerprints, performance, targets, deductions, payroll, commissions, tabbed detail |
| **Transactions / Finance** | Log + cash-sales, client-sales, advance-payments, petty-cash, transfers, safe-balances, shift totals, dailies, best-sales, package-sales (tabbed) |
| **Returns** | List + 3 refund wizards (cash refund, cancel down-payment, petty-cash refund) |
| **Expenses** | List + CRUD, approvals, category breakdown |
| **Sales / POS** | Service catalog, quick-sale, packages |
| **Marketing** | Hub + offers, promo codes, packages, announcements, messages, notifications, service groups, ads, campaigns |
| **Reports** | Overview, revenue, category hubs + report drill-down (ApexCharts, CSV/PDF export) |
| **Settings** | ~35 pages: business/profile/branches, catalog & pricing, scheduling, cash, devices, security, automations, program (loyalty/tipping), localization, appearance… |
| **Help** | FAQ center, bug report |
| **Employee Portal** | Separate token surface: dashboard, attendance history, shifts |
| **Auth / Onboarding** | Provider login, forgot-password (OTP), onboarding wizard, public staff-invite claim |

Cross-cutting: full EN/AR i18n + RTL, light/dark theme, business-type-aware terminology, RBAC, command palette (⌘/Ctrl-K), toasts, and graceful API-down fallback everywhere.

---

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel **13.8** (PHP **8.3+**) |
| UI runtime | Livewire **4.3** + Alpine.js (bundled) |
| Styling | Tailwind CSS **4** (`@theme` tokens) via Vite **8** |
| DTOs | `spatie/laravel-data` **4** (readonly data objects) |
| Charts | ApexCharts **5** (Alpine wrapper) |
| Testing | Pest **4** + `pest-plugin-laravel`, `Http::fake()` |
| Formatting | Laravel Pint |

By the numbers: **115** Livewire components · **35** DTOs · **28** services · **19** shared UI primitives · **124** routes · **88** test files / **438** tests · **5,487** aligned EN/AR i18n keys.

---

## Requirements

- PHP **8.3+** (with the usual Laravel extensions)
- Composer 2
- Node **18+** and npm
- Network access to the Waqty API (or run against the built-in fallback demo data)

No external database server is required — the default **SQLite** connection backs Laravel's framework tables (sessions, cache, queue). There is **no domain schema**.

---

## Quick start

```bash
git clone https://github.com/waqtyPlatform/waqty-provider-dashboard.git
cd waqty-provider-dashboard

# One-shot bootstrap (install, .env, key, migrate framework tables, build)
composer setup

# Point at the API + keep the session (which holds the JWT) encrypted
#   in .env:
#   WAQTY_API_BASE_URL=https://waqty.alemtayaz.shop/public
#   SESSION_ENCRYPT=true

# Run everything (server + queue + logs + Vite) in one terminal
composer dev
```

Or run pieces manually:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate            # framework tables only
npm run build                  # or: npm run dev
php artisan serve
```

Open `http://127.0.0.1:8000`.

**Local preview:** in `local` env, `/dev-login/salon` (or `/clinic`, `/barber`, `/spa`, `/nails`) seeds a fake provider session so the authenticated shell renders without live credentials; `/dev-login-employee` does the same for the employee portal. These routes are gated by `app()->environment('local')` — **remove them before any real deployment**.

---

## Environment variables

| Variable | Purpose | Default |
|---|---|---|
| `WAQTY_API_BASE_URL` | Base URL of the Waqty REST API | `https://waqty.alemtayaz.shop/public` |
| `WAQTY_API_TIMEOUT` | Hard request timeout (seconds) | `15` |
| `WAQTY_GET_CACHE_TTL` | Short GET de-dup cache window (seconds) | `5` |
| `WAQTY_SUPPORT_EMAIL` | Shown on the Help contact card | `support@waqty.com` |
| `WAQTY_SUPPORT_WHATSAPP` | Shown on the Help contact card | `201000000000` |
| `SESSION_ENCRYPT` | Encrypt the session (it stores the JWT) | `true` |

All live in [`config/waqty.php`](config/waqty.php). `.env.example` ships the framework defaults; add the `WAQTY_*` keys as needed.

---

## Project structure

```
app/
  Data/Waqty/            # 35 readonly DTOs (spatie/laravel-data) — the API contract
  Enums/                 # BookingStatus, UserRole, BusinessCategory (all localize via __())
  Http/Middleware/       # EnsureProviderAuthenticated, EnsureEmployeeAuthenticated,
                         #   EnforceRouteRestrictions, SetLocale
  Livewire/              # 115 page components, grouped by module
    Auth/ App/ Dashboard/ Bookings/ Customers/ Employees/ Services/
    Transactions/ Returns/ Expenses/ Sales/ Marketing/ Reports/ Settings/
    Portal/ Help/ Finance/
  Services/
    Waqty/               # WaqtyApiClient, WaqtyApiException + ~25 domain services
                         #   (BookingService, CustomerService, FinanceService,
                         #    EmployeeHrService, ReportService, SettingsService, …)
    NavigationService.php
  Support/               # Money, EgyptPhone, RouteAccess, CurrentProvider, BookingSamples
    Concerns/HandlesWaqtyErrors.php
config/
  waqty.php              # API base/timeout/cache + session keys + support contacts
  waqty_roles.php        # ROLE_RESTRICTED_ROUTES (port of the source RoleGuard)
  navigation.php         # sidebar tree (labels are i18n keys / dynamic tokens)
lang/
  en.json  ar.json       # 5,487 aligned flat dot-keys each
resources/
  css/app.css            # Tailwind 4 @theme design tokens
  js/app.js              # Alpine setup + ApexCharts chart() wrapper
  views/
    components/layouts/  # app / employee / guest (each sets <html lang dir data-theme>)
    components/app/       # sidebar, topbar, mobile-bottom-nav, mobile-nav-drawer, nav-group
    components/ui/        # 19 anonymous Blade primitives (button, badge, kpi-card, …)
    components/transactions/tabs.blade.php
    livewire/            # 115 views mirroring the components
routes/web.php
tests/Unit  tests/Feature/Livewire   # 88 files / 438 Pest tests
```

---

## Architecture

### Stateless proxy client

There is no local domain model. Each screen fetches on demand:

```
Livewire render / action
  → domain service (app/Services/Waqty/*)
    → WaqtyApiClient   (Guzzle via Http:: with bearer token + Accept-Language)
      → {success, message, data} envelope
    ← spatie/laravel-data DTO (app/Data/Waqty/*)
  ← rendered Blade
```

- **`WaqtyApiClient`** — thin wrapper over Laravel's HTTP client. Injects `Authorization: Bearer <session token>` and `Accept-Language: <locale>`, applies the 15s timeout, unwraps the envelope, and throws `WaqtyApiException` on failure (422 exposes `validationErrors`; connection/timeout becomes a `(0, 'Request timed out')` error). Provider vs. employee surface is selected via `->asEmployee()`.
- **`WaqtyApiException`** + the **`HandlesWaqtyErrors`** trait — components wrap service calls in `$this->waqty(fn () => …)`. On 422 the field errors are pushed into the Livewire error bag; on 401 the session is flushed and the user is redirected to login; other failures dispatch a `notify` toast.
- **Domain services** own the endpoint map for one area (e.g. `BookingService`, `FinanceService`, `EmployeeHrService`) and return DTOs (or raw rows for reporting endpoints).

### Auth & sessions — JWT stays server-side

The provider/employee JWT lives in the **encrypted Laravel session**, never in the browser:

```php
session('waqty.provider.token')   // provider surface
session('waqty.employee.token')   // employee portal surface
```

(keys defined in `config/waqty.php → session`). This removes the XSS token-theft surface the source documents for its `localStorage` approach. Which surface a route uses is decided by its middleware group, not by URL sniffing.

### Middleware (aliases in `bootstrap/app.php`)

| Alias | Class | Role |
|---|---|---|
| `waqty.provider` | `EnsureProviderAuthenticated` | redirect to `/login` when no provider token |
| `waqty.employee` | `EnsureEmployeeAuthenticated` | redirect to `/employee-portal/login` |
| `waqty.roles` | `EnforceRouteRestrictions` | RBAC gate (see below) |
| — | `SetLocale` | appended to the `web` group; resolves locale for every request |

### RBAC

`config/waqty_roles.php` maps restricted path prefixes to allowed roles (a verbatim port of the source `RoleGuard`). `App\Support\RouteAccess` is the single source of truth used by **both** `EnforceRouteRestrictions` (blocks the request) **and** the navigation/tab builders (hides the link) — so visibility and protection can never drift. The active role comes from `CurrentProvider::role()`.

### Money

All monetary values are **integer minor units** (piastres; 100 = 1 EGP). Format for display with `App\Support\Money`:

```php
Money::format(125000);   // "1,250 EGP"
Money::compact(3400000); // "34K EGP"
Money::toMinor(12.50);   // 1250   (form input → storage)
```

### Navigation

`NavigationService::build()` reads `config/navigation.php` and produces the sidebar/command-palette tree. Group/child labels are **i18n keys** (or `dynamic`/`@…` tokens resolved to business-type-aware labels), children may carry `header` (section label) or `soon` (disabled "coming soon") flags, and role-restricted entries are filtered via `RouteAccess`.

### Graceful degradation

When the API is unreachable, every list/detail screen catches `WaqtyApiException` and renders **localized sample data** behind a calm blue "demo mode" `x-ui.alert` — so the whole app is always demonstrable offline. Sample data lives in each component's `fallbackData()` (and `App\Support\BookingSamples`).

---

## Design system

- **Tokens** — `resources/css/app.css` defines the palette, spacing, radius, shadows, and z-index via Tailwind 4 `@theme`. Light/dark is a `data-theme` attribute on `<html>` (cookie `waqty_theme`, resolved before paint by a no-FOUC head script).
- **UI primitives** — 19 anonymous Blade components under `resources/views/components/ui/`: `button`, `badge`, `kpi-card`, `status-pill`, `alert`, `card`, `empty-state`, `dropdown` / `dropdown-item`, `slide-over`, `modal`, `confirm-dialog`, `input`, `select`, `toggle`, `pagination`, `page-header`, `skeleton`, `avatar`. Prefer these over hand-rolled markup.
- **Charts** — ApexCharts via a small Alpine `chart()` wrapper (`resources/js/app.js`). A component computes an options array server-side and dispatches an event; the chart lives inside `wire:ignore`. See `Reports/Overview` for the canonical pattern.

---

## Internationalization & RTL

- Every user-facing string goes through `__('dot.key')`. Keys live in **`lang/en.json`** and **`lang/ar.json`** as flat dot-keyed JSON; the two files are kept **1:1 aligned** (5,487 keys each).
- `SetLocale` resolves the locale from `session('waqty.locale')` → cookie `waqty_language` → default `en`, and feeds the same value to the API `Accept-Language` header.
- The layout sets `<html lang dir>`; RTL is achieved with **logical Tailwind utilities only** — use `ms-/me-/ps-/pe-/text-start/text-end/start-/end-`, never `ml-/mr-/left-/right-`. Directional icons (chevrons, back arrows) get `rtl:rotate-180`.
- **Business-type terminology** — `BusinessCategory::terminology()` returns localized labels so the same screen reads "clients/stylists/appointments" for a salon and "patients/doctors/visits" for a clinic. Access via `CurrentProvider::terminology()` (exposed to views as `$provider`).

> ⚠️ `__('missing.key')` returns the **key string itself**, so a missing translation renders as raw text but does **not** fail tests. Never rely on `__('x') ?? 'fallback'` (it can't fire). After adding a screen, grep its blade for keys absent from `lang/en.json`.

---

## Adding a new screen (recipe)

The house pattern, using two canonical references — `App\Livewire\Settings\Safes` (CRUD list) and `App\Livewire\Transactions\Index` (read-only list):

1. **Service** — add the endpoint methods to the relevant `app/Services/Waqty/*Service.php` (return a DTO, or a raw row array for reporting endpoints).
2. **DTO** *(if the shape is new)* — add a readonly `spatie/laravel-data` class under `app/Data/Waqty/`.
3. **Component** — `app/Livewire/<Module>/<Name>.php`:
   ```php
   #[Layout('components.layouts.app')]
   #[Title('…')]
   class Name extends Component
   {
       use HandlesWaqtyErrors;               // for write screens
       // cache the source; on WaqtyApiException set $fallbackUsed + load Arabic fallbackData()
       #[Computed] public function items(): array { … }
       public function usingFallback(): bool { … }
       // openCreate/openEdit/save/confirmDelete/delete mirror Settings\Safes
   }
   ```
4. **View** — `resources/views/livewire/<module>/<name>.blade.php`:
   ```blade
   <div class="p-4 sm:p-6">
       <x-ui.page-header :title="__('…')" :subtitle="__('…')">
           <x-slot:actions>…</x-slot:actions>
       </x-ui.page-header>
       @if ($this->usingFallback())
           <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
       @endif
       {{-- KPIs, filters, <x-ui.card> + table (overflow-x-auto), <x-ui.pagination>,
            row actions via <x-ui.dropdown>, empty → <x-ui.empty-state> --}}
   </div>
   ```
   Every string via `__()`; sample data in Arabic; logical RTL utilities only.
5. **Route** — register in `routes/web.php`. Dynamic-param routes (`/x/{id}`) must come **after** the static siblings so they don't shadow them, and role-restricted paths are picked up automatically from `config/waqty_roles.php`.
6. **Navigation** — add an entry to `config/navigation.php` (label = i18n key) if it belongs in the sidebar.
7. **i18n** — add every new key to **both** `lang/en.json` and `lang/ar.json` (keep them aligned).
8. **Test** — `tests/Feature/Livewire/<Name>Test.php` (see below).

---

## Testing

```bash
php artisan test                 # all 438 tests
php artisan test --compact       # one-line summary
php artisan test tests/Feature/Livewire/SafesTest.php   # one file
composer test                    # clears config, then runs the suite
```

Tests use Pest + `Http::fake()` and **never hit the live API**. Typical Livewire test:

```php
it('renders and creates', function () {
    Http::fake([
        '*/api/provider/safes' => Http::response(['success' => true, 'data' => [...]]),
    ]);

    Livewire::test(App\Livewire\Settings\Safes::class)
        ->assertSee('…')
        ->call('openCreate')
        ->set('form_name', '…')
        ->call('save')
        ->assertDispatched('notify');
});
```

Force the **fallback path** by faking a connection error (or leaving the endpoint unmocked) and asserting the Arabic sample data + the "sample data" banner.

---

## Code style

```bash
./vendor/bin/pint            # format
./vendor/bin/pint --test     # check only (CI)
```

Match the surrounding code: `declare(strict_types=1)`, PHP 8 attributes for Livewire (`#[Layout]`, `#[Computed]`, `#[Url]`, `#[Title]`), and the shared UI primitives over bespoke markup.

---

## Conventions & gotchas

- **Money is minor units** — never format a raw integer; always `Money::format()`. Forms convert with `Money::toMinor()` / `fromMinor()`.
- **`__()` returns the key when missing** — no silent fallbacks; keep `lang/en.json` and `lang/ar.json` aligned.
- **Logical RTL utilities only** — `ms-/me-/ps-/pe-/start-/end-`, `text-start/text-end`; add `rtl:rotate-180` to directional icons.
- **Route ordering** — static routes before dynamic `{param}` routes (e.g. `/bookings/print` before `/bookings/{uuid}`; `/reports/revenue` before `/reports/{category}`).
- **Nav `header` children have no `href`** — always guard `$child['href']` before dereferencing (sidebar, drawer, command palette).
- **Alpine `@click` needs an `x-data` scope** — a bare element outside any `x-data` won't fire handlers.
- **Enums localize** — `BookingStatus::label()`, `UserRole::label()`, `BusinessCategory::terminology()` all return `__()` keys; don't hardcode English at call sites.
- **Two token surfaces** — keep provider (`/api/provider/*`) and employee (`asEmployee()` → `/api/employee/*`) calls distinct.

---

## Deployment notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, and `SESSION_ENCRYPT=true`.
- Remove the local-only `/dev-login*` routes (they are already `local`-gated).
- Build assets with `npm run build`; cache config/routes/views (`php artisan optimize`).
- Point `WAQTY_API_BASE_URL` at the production API. Ensure the session store persists across app servers (Redis/DB) since it holds the JWT.

---

UI-only clone for the Waqty platform — it renders and calls the external Waqty API; no bundled database or backend. Ported from [`waqtyPlatform/Waqty-Service-provider`](https://github.com/waqtyPlatform/Waqty-Service-provider).
