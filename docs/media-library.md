# Commero media library

Commero keeps the Media Library original on the configured private disk and
stores named conversions on the configured public disk. During the compatibility
release, `path`, `thumbnail_path`, and `icon_path` remain public legacy mirrors.

Change image presets in `config/commero.php` under `media.sizes`:

```php
'sizes' => [
    'thumb' => ['width' => 320, 'height' => 320, 'fit' => 'crop', 'format' => 'webp'],
],
```

Use the resolver in PHP or Blade:

```blade
<img src="{{ commero_media_url($image, 'product_gallery', 'thumb') }}" alt="{{ $image->alt }}">
```

The resolver falls back from the requested conversion to the private original
endpoint and then to the legacy public path. Existing Commero code that uses
`Storage::disk('public')->url($image->path)` continues to work.

Generate missing files from the host application or scheduler:

```bash
php artisan commero:media:generate --only-missing
php artisan commero:media:generate --model=product-image --sync --only-missing
php artisan commero:media:generate --force
```
