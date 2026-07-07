# Waqty — Service Provider Dashboard (Laravel + Livewire)

A **UI-only** rebuild of the Waqty service-provider dashboard in **Laravel 13 + Livewire 4**, ported from the original [Next.js frontend](https://github.com/waqtyPlatform/Waqty-Service-provider). It is a fully **bilingual (English / Arabic) RTL** management console for salons, clinics, barbershops, spas, and nail studios.

Like the original, this app owns **no local database**. It is a server-side client of the external Waqty REST API, rendering each screen through a domain service → API client → DTO pipeline. The primary audience is a **non-technical, Arabic-first business owner**, so every screen is designed to read naturally in Arabic RTL with no technical jargon leaking through.

## Highlights

- **~90 screens across 14 modules** — Dashboard, Bookings (calendar / list / detail / new / rooms / waitlist / payments / print), Customers & CRM, Employees (core + full HR: attendance, payroll, commissions, schedule, transfers, performance…), Transactions & Finance, Returns, Expenses, Sales/POS, Marketing, Reports (with charts + drill-down), ~35 Settings pages, Help, and the Employee Portal.
- **Full i18n + RTL** — every string flows through `__()` keys (EN/AR aligned); layout uses logical Tailwind utilities (`ms-`/`me-`/`ps-`/`pe-`/`start-`/`end-`) and flips cleanly to `dir="rtl"`.
- **Business-type-aware terminology** — labels adapt (clients ↔ patients, stylists ↔ doctors ↔ barbers, appointments ↔ visits) based on the provider category.
- **Design system** — token-driven Tailwind theme with light/dark mode and a set of reusable Blade UI primitives (buttons, badges, KPI cards, status pills, slide-overs, confirm dialogs, tables, charts).
- **Graceful degradation** — when the API is unreachable, screens fall back to localized sample data behind a calm "demo mode" notice, so the UI is always demonstrable.
- **Tested** — **438 Pest feature/unit tests** covering rendering, filters, CRUD flows, validation, and API-fallback behavior.

## Tech stack

Laravel 13 · PHP 8.3+ · Livewire 4 · Alpine.js · Tailwind CSS 4 (Vite) · spatie/laravel-data (DTOs) · ApexCharts · Pest.

## Architecture

- **Stateless proxy client.** Each Livewire component calls a domain service (`app/Services/Waqty/*`) → `WaqtyApiClient` → HTTP round-trip → unwraps the `{success, message, data}` envelope → hydrates a `spatie/laravel-data` DTO.
- **Auth via encrypted server session.** The provider/employee JWT lives in the encrypted Laravel session (`session('waqty.provider.token')` / `session('waqty.employee.token')`) — **never** in the browser. Provider vs. employee surface is chosen by route group.
- **Money as integer minor units** (piastres; 100 = 1 EGP), formatted via `App\Support\Money`.
- **RBAC** ported from the source `RoleGuard` (`config/waqty_roles.php`) enforced by middleware and mirrored in the sidebar.

## Getting started

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
#   set WAQTY_API_BASE_URL (default: https://waqty.alemtayaz.shop/public)
#   keep SESSION_ENCRYPT=true

# 3. Build assets + run
npm run build          # or: npm run dev
php artisan serve
```

Open `http://127.0.0.1:8000`. In `local` env a shell preview is available at `/dev-login/salon` (seeds a fake provider session; remove before shipping).

## Testing

```bash
php artisan test          # 438 tests
./vendor/bin/pint         # code style
```

Tests use `Http::fake()` and never hit the live API.

## Configuration

| Env | Purpose | Default |
|---|---|---|
| `WAQTY_API_BASE_URL` | Base URL of the Waqty API | `https://waqty.alemtayaz.shop/public` |
| `SESSION_ENCRYPT` | Encrypt session (holds the JWT) | `true` |
| `WAQTY_API_TIMEOUT` | API request timeout (seconds) | `15` |

---

UI-only clone for the Waqty platform. It renders and calls the external Waqty API — no bundled database or backend.
