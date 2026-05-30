# Commero

Reusable Laravel commerce, CMS, and Filament admin core for Laravel projects.

## Installation

Require the package in your Laravel app:

```bash
composer require karakushan/commero:dev-main
```

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
- Filament admin resources and pages
- package migrations, translations, and config

## Development

See:

- `docs/development-workflow.md`
- `docs/release-process.md`
