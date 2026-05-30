# Commero Release Process

## Purpose

This document explains how to:

- develop the package locally
- push package changes to GitHub
- update the submodule pointer in the host project
- publish package updates for Composer/Packagist usage

## Important Rule

`packages/commero` is the package working directory.

Changes made there:

- affect the local Laravel host project immediately
- do **not** automatically go to GitHub
- do **not** automatically go to Packagist

## Local Development Flow

Edit package code here:

```text
packages/commero
```

Because the host project uses a `path` repository with `symlink: true`, local changes are visible in the app without reinstalling the package.

Recommended refresh commands in the host project:

```bash
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

## How to Push Package Changes to GitHub

Go to the package repo:

```bash
cd packages/commero
```

Then commit and push:

```bash
git add .
git commit -m "Describe the package change"
git push origin main
```

This updates:

- `https://github.com/karakushan/commero.git`

## How to Update the Main Laravel Project After Package Changes

After pushing package changes, go back to the main project root:

```bash
cd ..
```

The submodule commit pointer in the host project must also be committed.

Run:

```bash
git add packages/commero
git commit -m "Update Commero submodule"
```

Without this step, the main project will still point to the previous package commit.

## How Packagist Works

Packagist does **not** read code from your local `packages/commero` directory.

Packagist reads package source from the GitHub repository:

- `https://github.com/karakushan/commero.git`

So the real release flow is:

1. edit package in `packages/commero`
2. commit and push package repo
3. Packagist reads the updated GitHub repo
4. optionally commit updated submodule pointer in the host project

## Using the Package Through Composer in Other Projects

If another Laravel project installs the package through Composer, it gets code from the remote package source, not from your local submodule copy.

Example:

```bash
composer require karakushan/commero:dev-main
```

## Recommended Stable Release Flow

For production use, prefer tags instead of `dev-main`.

Package repo:

```bash
cd packages/commero
git tag v0.1.0
git push origin v0.1.0
```

Then in consumer projects:

```bash
composer require karakushan/commero:^0.1
```

## Quick Summary

- edit package in `packages/commero`
- push package changes from inside `packages/commero`
- commit submodule pointer in the main Laravel project
- Packagist updates only from GitHub, never from local files
