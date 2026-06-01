# Commero Content Blocks

## Purpose

`commero` no longer owns concrete Filament Builder blocks.

The package provides only infrastructure:

- `Commero\\Contracts\\ContentBlockRegistry`
- `Commero\\Contracts\\ContentBlockHydrator`
- default empty implementations for projects that do not register blocks yet

The host project must provide the real block implementation.

## Required Host Implementations

Configure these classes in `config/commero.php`:

```php
'content_blocks' => [
    'registry' => \App\Support\Filament\PageContentBlocks::class,
    'hydrator' => \App\Support\HomePageBlockHydrator::class,
],
```

### Registry

The registry class must implement `Commero\\Contracts\\ContentBlockRegistry`.

Responsibilities:

- return Filament Builder blocks through `builderBlocks()`
- return frontend `type => view` map through `viewMap()`

Recommended location:

- `app/Support/Filament/PageContentBlocks.php`

### Hydrator

The hydrator class must implement `Commero\\Contracts\\ContentBlockHydrator`.

Responsibilities:

- receive saved `blocks` JSON
- enrich block data before rendering
- leave unrelated block payload unchanged

Recommended location:

- `app/Support/HomePageBlockHydrator.php`

## Where Block Files Live

Recommended project structure:

- Builder schema: `app/Support/Filament/PageContentBlocks.php`
- Block hydration: `app/Support/HomePageBlockHydrator.php`
- Blade templates: `resources/views/shophats/blocks/*.blade.php`
- Admin block translations: `lang/{locale}/admin-content-blocks.php`

## Adding a New Block

1. Add a new unique `type` in the project registry.
2. Register the Filament Builder schema in `builderBlocks()`.
3. Add the frontend Blade view to `viewMap()`.
4. Create the Blade template in `resources/views/shophats/blocks`.
5. Add block admin translations in:
   - `lang/en/admin-content-blocks.php`
   - `lang/uk/admin-content-blocks.php`
   - `lang/ru/admin-content-blocks.php`
6. If the block needs runtime data enrichment, update the project hydrator.

## Compatibility Rules

- Do not rename existing saved block `type` values unless you also migrate stored JSON.
- Keep saved block shape compatible:

```php
[
    [
        'type' => 'example_block',
        'data' => [
            // block payload
        ],
    ],
]
```

- Unknown block types are ignored during frontend rendering.

## Verification

Use only Sail commands in the host project.

### Quick tinker checks

```bash
./vendor/bin/sail artisan tinker
```

Examples:

```php
app(\Commero\Contracts\ContentBlockRegistry::class)->builderBlocks();
app(\Commero\Contracts\ContentBlockRegistry::class)->viewMap();
app(\Commero\Contracts\ContentBlockHydrator::class)->hydrate([], 'uk');
```

### Test runs

```bash
./vendor/bin/sail artisan test --filter=HomePageCmsTest
./vendor/bin/sail artisan test --filter=CatalogCategoryBlocksTest
./vendor/bin/sail artisan test --filter=PageAdminLocalizationTest
```

## Important

Do not add project-specific blocks inside `packages/commero`.
All real block implementation now belongs to the host project.
