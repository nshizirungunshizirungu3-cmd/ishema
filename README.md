# ISHEMA - Hotel & Restaurant Management System

A multi-tenant SaaS platform for hotels, restaurants, and hotel & restaurant
businesses. Built with vanilla PHP 8.2 (MVC, no framework) and MySQL 8.

## Features

- **Multi-tenant** — every tenant owns an isolated `business_id` data slice.
- **Restaurant module** — tables, categories, products, kitchen orders,
  QR-code table ordering, delivery tracking, customer reviews, complaints,
  inventory, staff shifts, and reports.
- **Hotel module** — room types, rooms, guests, bookings, housekeeping,
  and billable hotel services.
- **Customer portal** — guests scan a table QR code to browse the menu,
  build a cart, place orders, track progress, confirm receipt, and rate
  the experience — no account required.
- **Guest booking portal** — public room search, availability check, and
  booking lookup.
- **Payments** — MTN Mobile Money, Airtel Money, bank transfer, Visa, and
  cash. Payments are never simulated as successful; unconfigured providers
  leave transactions `pending` until a real provider callback confirms them.
- **Super-admin platform** — business approval/suspension, subscription
  packages, user management, tutorials, contact messages, audit logs,
  and platform-wide reports.
- **i18n** — English, Kinyarwanda, French, and German.

## Requirements

- PHP >= 8.2 with extensions: `pdo_mysql`, `mbstring`, `gd`, `curl`, `openssl`
- MySQL >= 8.0
- Composer (for the autoloader)
- Web server (Apache with `mod_rewrite`, Nginx, or the PHP built-in server)

## Installation

1. **Install dependencies**

   ```bash
   composer install
   ```

2. **Point your web server at the `public` directory** (recommended), or at
   the project root (a root `index.php` forwards to `public/index.php`).

   PHP built-in server for local testing:

   ```bash
   php -S localhost:8000 -t public
   ```

3. **Run the installer** — open the site in your browser and follow the steps
   at `http://localhost:8000/install.php`:

   - enter the database credentials and a super-administrator account
     (the connection is verified before anything is written),
   - the installer creates the database, imports `database/schema.sql`,
     creates the super admin, and seeds the three default subscription
     packages (Starter, Professional, Enterprise),
   - optionally seed a demo business so every module is explorable.

   The installer is disabled once `storage/installed.lock` exists. To
   reinstall, delete that file.

   A `.env` file (git-ignored) is created from your input; see `.env.example`
   for every option.

## Default accounts

After installation:

| Role | Email (default) | Password |
| --- | --- | --- |
| Super admin | `admin@ishema.com` | the one you chose during install |

If you seeded the demo data, a sample business **Ishema Grand Hotel &
Restaurant** (`DEMO-HOTEL`) is created with owner `owner@ishema-grand.com`
(password `Owner12345!`), plus staff accounts (password `Staff12345!`).
**Change these passwords immediately.**

## Architecture

```
app/
  Controllers/    web controllers (one per module)
  Core/           App, Router, Database, Session, Auth, CSRF,
                  Translator, View, Security, helpers
  Middleware/     auth, guest, tenant, staff, super_admin, customer, ...
  Models/         BaseModel + query builder (HotelModels, RestaurantModels)
  Services/       ReportService, PaymentService, BusinessService, ...
config/           database.php
database/         schema.sql, seeds/
resources/
  languages/      en, rw, fr, de (10 translation groups)
  views/          layouts + module views
routes/           web.php
public/           index.php (entry point), assets/
```

- **Routing** — `routes/web.php` registers routes; `App\Core\Router` matches
  the request method and path, runs middleware, and dispatches to a
  controller method. All `POST` routes are CSRF-protected by default.
- **Tenant isolation** — `App\Core\Tenant` resolves the current business from
  the session; tenant-owned models are scoped by `business_id`.
- **Views** — plain PHP templates rendered by `App\Core\View`; layouts in
  `resources/views/layouts/`.
- **Translations** — `resources/languages/{locale}/{group}.php`; missing keys
  fall back to English, then render the raw key.

## Testing

```bash
# lint every PHP file
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# smoke-test the translator and autoloader
php -r "require 'vendor/autoload.php'; ..."
```

## Payment provider integration

`App\Services\PaymentService::callProviderVerify()` is the documented hook
for a real provider callback. Until a provider is configured, transactions
remain `pending` and are never marked successful client-side.

## License

Proprietary.
