# Commero

Reusable Laravel commerce, CMS, and Filament admin core for Laravel projects.

## Installation

Require the package in your Laravel app:

```bash
composer require karakushan/commero:dev-main
php artisan commero:install
```

This package requires Filament and Filament Shield in the host application. On a clean Laravel app, it registers a ready-to-use `admin` Filament panel automatically. If the host app already has any panel provider in `app/Providers/Filament/*PanelProvider.php`, Commero does not register a second panel and will use the host panel setup instead.

If Composer auto-discovery is disabled, register the service provider manually:

```php
Commero\Providers\CommeroServiceProvider::class,
```

## Basic Setup

The `commero:install` command configures `AUTH_MODEL=Commero\Models\User`, publishes the package config, generates Filament assets, runs migrations, generates Filament Shield permissions, seeds the package roles and permissions, and interactively offers to create an admin user in the host application.

Useful flags:

```bash
php artisan commero:install --no-assets
php artisan commero:install --no-migrate
php artisan commero:install --no-admin
php artisan commero:install --force
```

## Theme Integration

By default, the package expects storefront theme views in:

```text
resources/views/shophats
```

You can change that path in `config/commero.php`.

## Localization

Commero uses `config/commero.php` as the source of truth for locales:

- `locales.default` sets the application and Filament admin locale
- `locales.fallback` sets the Laravel fallback locale
- `locales.supported` sets the supported locale list used by the package

## What the Package Provides

- storefront routes
- catalog, cart, wishlist, checkout, and account flows
- CMS pages and blog
- ready-to-use Filament admin panel on clean installs
- Filament admin resources and pages for custom host panels
- package migrations, translations, and config

## Development

See:

- `docs/development-workflow.md`
- `docs/release-process.md`
