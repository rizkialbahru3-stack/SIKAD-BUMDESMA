# AGENTS.md

Laravel 10 (PHP `^8.1`) + Blade + Vite. HR/attendance app (BUMDESMA) layered on a public promo site. `README.md` is stock Laravel boilerplate — ignore it.

## Commands

```bash
php artisan serve                    # dev server (http://localhost:8000)
npm run dev                          # Vite dev (entries: resources/css/app.css, resources/js/app.js)
npm run build                        # production frontend build
php artisan migrate --seed           # full setup; seeds demo accounts (see below)
php artisan migrate:fresh --seed     # clean reset
php artisan test                     # or: vendor/bin/phpunit
php artisan test --filter=ExampleTest # single test/class
./vendor/bin/pint --test             # lint check (laravel/pint, no config = defaults)
./vendor/bin/pint                    # autofix
```

No `composer test` / npm test scripts, no CI, no `opencode.json` — use the commands above verbatim.

## Architecture (not obvious from filenames)

- `routes/web.php` is the whole app; `routes/api.php` is an unused Sanctum stub (`GET /user` only). Don't add API routes unless asked.
- Two domains in one file: public promo pages (`/`, `/{page}` via `HomeController@index|page`) + authenticated HR (`/dashboard`, `/attendance`, `/leave-requests`, `/payroll`, `/recognition`, `/reports/*`, `/employees*`). All HR routes sit inside `middleware('auth')`.
- Catch-all `Route::get('/{page}')` with `whereIn` (`tentang`, `profil-lkd`, `profil`, `kegiatan`, `program`, `potensi`, `berita`, `galeri`, `kontak`) **must stay last** — anything after it is unreachable. `tentang` and `profil-lkd` are aliases for the `profil` content slug (`HomeController@page`).
- Role gate: `admin` alias (`app/Http/Kernel.php:57`) → `AdminMiddleware` aborts 403 unless `User::isAdmin()` (`role === 'admin'`). Admin-only: employee CRUD, payroll generate/status, recognition writes, attendance settings + early-checkout approval, all `/reports/*`, and `PATCH /leave-requests/{leaveRequest}/review`. Checkout rule (`App\Services\CheckoutPolicy::checkoutEligibility`): in-window checkout needs no approval; early checkout (before window) needs admin approval, and post-window checkout is rejected (`too_late`). Window/time math lives in `CheckoutPolicy::workTimes` — employee `WorkSchedule` overrides `AttendanceSetting`, server time is authoritative.
- Admins are blocked from check-in/out (`AttendanceController` aborts 403 for admins) — test attendance flows as an employee user.
- Attendance evidence: check-in/out require selfie + GPS (`photo|latitude|longitude|accuracy`, photo ≤2 MB → `attendance/check-in|check-out/Y/m` on `public` disk). Radius enforced server-side by `AttendanceLocationService::check` (Haversine) against the active `AttendanceLocation`; no active location = record-only (`*_location_valid = null`). Check-in enforces radius only when the location's `enforce_radius` is on (seed default on); check-out is record-only (boleh dari lapangan) unless `enforce_checkout_radius` is on (seed/migration default off). Office (`AttendanceLocationSeeder`): `Kantor BUMDESMA TARUB LKD`, Tarub Tegal (-6.926433, 109.185844), radius 100 m, batas akurasi GPS 500 m (longgar untuk GPS laptop). `attendance.show` (owner or admin only) has the Leaflet map.
- Employee routes are duplicated: `/employees*` and `/admin/karyawan*` both map to `EmployeeController`. Prefer `/employees*` for new links.
- Reports (`app/Http/Controllers/ReportController.php` + `app/Exports/ReportExport.php`): `index` is the real handler, filtered by `?type=` (`all|attendance|leaves|payroll|reward-punishment|employees`) + `month/year/employee_id/status`; the `/attendance`, `/leaves`, `/payroll`, `/reward-punishment`, `/employees` routes are thin wrappers that merge `type` then call `index`. `/print` renders `reports.print`; `/pdf` downloads it via `barryvdh/laravel-dompdf` (A4 landscape); `/excel` downloads via `maatwebsite/excel`.
- Key models (`app/Models/`): `User -(hasOne)-> Employee`; `Employee` belongs to `User|Position|WorkSchedule`, uses `SoftDeletes` (reports query `withTrashed()`). Others: `Attendance, LeaveRequest, Payroll, Reward, Punishment, Holiday, SalaryComponent, Content`.
- Seeders: `DatabaseSeeder` runs `ContentSeeder` + `AttendanceSeeder` (creates `admin@bumdesma.test` + `KRY-001`) + `DemoKaryawanSeeder` (`KRY-002`–`KRY-006`) + `AttendanceLocationSeeder`. Demo logins (password `password`): `admin@bumdesma.test` (admin), `kry-001@bumdesma.test` (`Karyawan 1`, `KRY-001`) – `kry-006@bumdesma.test` (`Karyawan 6`, `KRY-006`). Demo users are skipped when `APP_ENV=production` — create the prod admin via `php artisan admin:create`. Daily MySQL backups: `php artisan db:backup --keep=7` (scheduled 01:00; enable cron `schedule:run` on the server).

## Env / test gotchas

- Local DB is MySQL: `DB_DATABASE=bumdesma_tarub` in `.env`. No SQLite fallback.
- `phpunit.xml` sets `APP_ENV=testing` + array drivers but **has `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` commented out** — tests hit the real MySQL DB unless you override env. Critical: `AttendanceEvidenceTest` uses `RefreshDatabase` and will wipe whatever DB it points at — never run it against the dev DB (override env to a scratch DB first). `ExampleTest` (no `RefreshDatabase`, hits `/` expecting 200) and `LogoutTest` (no DB writes) are safe.
- Frontend: no Blade view uses `@vite` — all pages load Bootstrap/CDN assets + inline `<style>`/`<script>`. `resources/css/app.css` + `resources/js/app.js` and `public/build` output are currently dead weight; `npm run build` / Node is NOT needed on the server. (Side effect: `data-public-header` scroll effect + carousel JS in `app.js` never run — cosmetic only.)
- Public promo content comes from the `contents` table (`ContentSeeder`, type `page` + `slide|program|activity|product|news|gallery`) rendered via `resources/views/content.blade.php` (`page` action aborts 404 on unknown slug) and `welcome.blade.php`. `HomeController` falls back to hardcoded defaults on `QueryException`, so missing tables don't crash public pages.
- Uploads: employee photos on the `public` disk (`employee-photos`, served via `asset('storage/...')` — run `php artisan storage:link` after fresh setup or images break; if images still 404 after that, check `public/storage` is a symlink/junction, not a stale real folder — delete it and re-run `storage:link`). Leave attachments use the default `local` disk (`leave-attachments`, served only via the auth-gated `leave.attachment` download route — not publicly accessible). Profile photo changes have a 1-week cooldown (`ProfileController::photoCooldown` via `photo_updated_at`); don't remove it as a bug.
