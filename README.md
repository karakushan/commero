# Commero

Reusable Laravel commerce, CMS, and Filament admin core for Laravel projects.

## Installation

Require the package in your Laravel app:

```bash
composer require karakushan/commero:dev-main
```

This package requires Filament and Filament Shield in the host application. On a clean Laravel app, it registers a ready-to-use `admin` Filament panel automatically. If the host app already has any panel provider in `app/Providers/Filament/*PanelProvider.php`, Commero does not register a second panel and will use the host panel setup instead.

If Composer auto-discovery is disabled, register the service provider manually:

```php
Commero\Providers\CommeroServiceProvider::class,
```

## Basic Setup

Publish the package config if you want to override defaults:

```bash
php artisan vendor:publish --tag=commero-config
```

Run migrations:

```bash
php artisan migrate
```

## Theme Integration

By default, the package expects storefront theme views in:

```text
resources/views/shophats
```

You can change that path in `config/commero.php`.

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
