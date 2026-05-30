# Commero Development Workflow

## Purpose

This document describes the correct workflow for:

- local package development
- testing the package inside a Laravel host project
- updating the host project through Composer

## Repository Layout

Use `commero` as a standalone package repository.

- package repo: `https://github.com/karakushan/commero.git`
- package name: `karakushan/commero`
- namespace: `Commero\\`

Do not edit files inside `vendor/karakushan/commero` directly. That directory contains Composer-installed package files and may be replaced at any time.

## Local Development

### 1. Clone the package repo

```bash
git clone https://github.com/karakushan/commero.git
cd commero
```

### 2. Open the package and the host project side by side

Recommended structure:

```text
/dev
  /commero
  /shophats-laravel
```

The package is developed in `/commero`.
The real application is tested in `/shophats-laravel`.

## Connecting the Package to the Host Project

During active local development, the host project should use a Composer `path` repository instead of the remote GitHub version.

### 1. Update host `composer.json`

Replace the VCS repository with a local path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../commero",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

And use:

```json
"karakushan/commero": "dev-main"
```

### 2. Refresh Composer in the host project

Run inside the Laravel host project:

```bash
./vendor/bin/sail composer update karakushan/commero -W
```

If `symlink` is enabled, host changes should reflect immediately after autoload/cache refresh.

## Testing Inside the Laravel Host Project

All Laravel commands must be run through Sail.

### Required checks

```bash
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

### Site availability check

```bash
curl -I http://localhost/
```

Expected result: `HTTP/1.1 200 OK`

### Data checks

For checking real data, use Laravel Tinker:

```bash
./vendor/bin/sail artisan tinker
```

Example checks:

```php
App\Models\Product::count();
App\Models\Category::with('translations')->first();
App\Models\Page::with('translations')->first();
```

## Runtime Compatibility Rules

The host project may still contain `App\\...` compatibility classes.

Keep them if they are still used by:

- theme Blade templates
- `bootstrap/app.php`
- config values that reference `App\\...`
- third-party packages expecting `App\\Models\\User`

Do not remove compatibility layers blindly. First search for usage in the host project.

## Releasing Changes from the Package Repo

### 1. Commit and push package changes

Inside the package repo:

```bash
git add .
git commit -m "Describe the package change"
git push origin main
```

### 2. Update the host project

If the host project uses the GitHub VCS repository:

```bash
./vendor/bin/sail composer update karakushan/commero -W
```

If the host still uses a local `path` repository, Composer update may not be necessary, but autoload and cache refresh is still recommended:

```bash
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail artisan optimize:clear
```

## Switching Back to GitHub VCS Dependency

After local development is complete:

1. change host `composer.json` from `path` to `vcs`
2. keep `karakushan/commero: dev-main` or move to a tagged version later
3. run:

```bash
./vendor/bin/sail composer update karakushan/commero -W
```

## Recommended Future Improvement

When the package stabilizes:

- add tagged releases such as `v0.1.0`
- switch host projects from `dev-main` to tagged versions
- add package-level automated tests
- add a small host integration test checklist for storefront, cart, checkout, and Filament admin
