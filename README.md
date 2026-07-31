# Commero

Reusable Laravel commerce, CMS, and Filament admin core for Laravel projects.

## Installation

Require the package in your Laravel app:

```bash
composer require karakushan/commero:^1.0
php artisan commero:install
```

This package requires Filament and Filament Shield in the host application. On a clean Laravel app, it registers a ready-to-use `admin` Filament panel automatically. If the host app already has any panel provider in `app/Providers/Filament/*PanelProvider.php`, Commero does not register a second panel and will use the host panel setup instead.

If Composer auto-discovery is disabled, register the service provider manually:

```php
Commero\Providers\CommeroServiceProvider::class,
```

## Basic Setup

The package uses `Commero\Models\User` automatically when the host app still uses the default `App\Models\User`. If the host app explicitly configures another auth user model, Commero respects that override.

The `commero:install` command publishes the package config, generates Filament assets, runs migrations, generates Filament Shield permissions, seeds the package roles and permissions, and interactively offers to create an admin user in the host application.

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

Content block implementation is also expected on the host project side. The package only provides the contracts and default empty infrastructure.

## Localization

Commero uses `config/commero.php` as the source of truth for locales:

- `locales.default` sets the application and Filament admin locale
- `locales.fallback` sets the Laravel fallback locale
- `locales.supported` sets the supported locale list used by the package

## Email Notifications

Commero includes default email notifications for:

- new orders for users with `Receive:OrderNotifications`
- order confirmations for every customer email saved on the order, regardless of role or permissions
- order status changes for every customer email saved on the order, regardless of role or permissions
- new marketing leads for users with `Receive:MarketingLeadNotifications`
- new product reviews for users with `Receive:ProductReviewNotifications`

The `admin` role receives all three notification permissions by default. They are available in the Filament Shield custom permissions tab; other roles can be configured there.

The default templates are:

```text
resources/views/emails/order-notification.blade.php
resources/views/emails/marketing-lead-notification.blade.php
resources/views/emails/product-review-notification.blade.php
```

Host applications can override them without editing the package by creating files with the same names under:

```text
resources/views/vendor/commero/emails/
```

The template view names can also be changed in `config/commero.php` under `notifications`.

The sender address and sender name can be configured in the admin panel under
`Site settings -> Налаштування відправки пошти`. If either field is empty, Commero
uses the corresponding `mail.from` value from the host application's mail configuration.
The default email templates also use the store logo configured in Site settings.

## What the Package Provides

- storefront routes
- catalog, cart, wishlist, checkout, and account flows
- CMS pages and blog
- ready-to-use Filament admin panel on clean installs
- Filament admin resources and pages for custom host panels
- package migrations, translations, and config

## Development

See:

- `docs/content-blocks.md`
- `docs/development-workflow.md`
- `docs/release-process.md`
