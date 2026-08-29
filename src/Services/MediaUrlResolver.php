<?php

namespace Commero\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaUrlResolver
{
    public static function url(Model $model, string $collection, ?string $conversion = null): ?string
    {
        $media = $model->getFirstMedia($collection);

        if ($media) {
            if ($conversion && $media->hasGeneratedConversion($conversion)) {
                $path = $media->getPathRelativeToRoot($conversion);

                if (Storage::disk($media->conversions_disk)->exists($path)) {
                    return $media->getUrl($conversion);
                }
            }

            if (Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
                return route('commero.media.original', ['media' => $media->uuid]);
            }
        }

        $field = app(MediaService::class)->legacyFieldFor($model, $collection);
        $legacyPath = $field ? $model->getAttribute($field) : null;

        if (filled($legacyPath) && Storage::disk(config('commero.media.legacy_disk', 'public'))->exists($legacyPath)) {
            return Storage::disk(config('commero.media.legacy_disk', 'public'))->url($legacyPath);
        }

        return null;
    }
}
