<?php

namespace Commero\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    public function onlyMissingConversions(Media $media, ConversionCollection $conversions): ConversionCollection
    {
        return $conversions->reject(function ($conversion) use ($media): bool {
            if (! $media->hasGeneratedConversion($conversion->getName())) {
                return false;
            }

            return Storage::disk($media->conversions_disk ?: $media->disk)
                ->exists($media->getPathRelativeToRoot($conversion->getName()));
        });
    }

    public function removeConversions(Media $media, ConversionCollection $conversions): void
    {
        $disk = Storage::disk($media->conversions_disk ?: $media->disk);
        $generatedConversions = (array) $media->generated_conversions;

        foreach ($conversions as $conversion) {
            $name = $conversion->getName();
            $path = $media->getPathRelativeToRoot($name);
            $disk->delete($path);

            // Remove previous files with the same conversion name even when
            // the output format changed (for example, old JPG -> new WebP).
            $conversionFile = basename($path);
            $marker = '-'.$name.'.';
            $prefix = Str::before($conversionFile, $marker);

            if ($prefix !== $conversionFile) {
                $directory = trim(dirname($path), '/').'/';

                foreach ($disk->files($directory) as $file) {
                    if (Str::startsWith(basename($file), $prefix.$marker)) {
                        $disk->delete($file);
                    }
                }
            }

            $generatedConversions[$name] = false;
        }

        $media->forceFill(['generated_conversions' => $generatedConversions])->save();
    }

    public function importLegacy(Model $model, string $collection, ?string $legacyPath = null): ?Media
    {
        if (! config('commero.media.legacy_compatibility.enabled', true)) {
            return $model->getFirstMedia($collection);
        }

        $legacyDisk = (string) config('commero.media.legacy_disk', 'public');
        $originalDisk = (string) config('commero.media.original_disk', 'private');
        $legacy = $legacyPath ? ltrim($legacyPath, '/') : null;
        $mediaTypes = array_values(array_unique([
            $model->getMorphClass(),
            get_class($model),
            'Commero\\Models\\'.class_basename($model),
        ]));
        $media = Media::query()
            ->whereIn('model_type', $mediaTypes)
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->orderBy('id')
            ->first() ?? $model->getFirstMedia($collection);

        if ($media && $legacy && Storage::disk($legacyDisk)->exists($legacy)) {
            $incomingChecksum = hash('sha256', Storage::disk($legacyDisk)->get($legacy));
            $knownChecksum = $media->getCustomProperty('legacy_checksum')
                ?: $this->mediaChecksum($media);

            if ($knownChecksum !== null && ! hash_equals($knownChecksum, $incomingChecksum)) {
                $media->delete();
                $media = null;
            }
        }

        if (! $media && $legacy && Storage::disk($legacyDisk)->exists($legacy)) {
            $media = $this->withoutAutomaticConversions(fn () => $model
                ->addMediaFromDisk($legacy, $legacyDisk)
                ->usingFileName(basename($legacy))
                ->toMediaCollection($collection, $originalDisk));

            $this->rememberChecksum($media, Storage::disk($legacyDisk)->path($legacy));
        }

        if (! $media) {
            return null;
        }

        $legacy ??= $this->legacyPath($model, $media);

        if (! Storage::disk($legacyDisk)->exists($legacy)) {
            $this->copyOriginalToLegacy($media, $legacy, $legacyDisk);
        }

        if ($this->legacyFieldFor($model, $collection) && $model->getAttribute($this->legacyFieldFor($model, $collection)) !== $legacy) {
            $model->withoutEvents(fn () => $model->forceFill([
                $this->legacyFieldFor($model, $collection) => $legacy,
            ])->save());
        }

        return $media->refresh();
    }

    public function legacyFieldFor(Model $model, string $collection): ?string
    {
        return $model->commeroMediaCollections()[$collection] ?? null;
    }

    public function legacyPath(Model $model, Media $media): string
    {
        $modelName = Str::kebab(class_basename($model));

        return "commero/legacy/{$modelName}/{$model->getKey()}/{$media->uuid}-{$media->file_name}";
    }

    private function copyOriginalToLegacy(Media $media, string $legacyPath, string $legacyDisk): void
    {
        $sourceDisk = Storage::disk($media->disk);
        $sourcePath = $media->getPathRelativeToRoot();
        $stream = $sourceDisk->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw new \RuntimeException("Cannot read media original [{$sourcePath}].");
        }

        try {
            Storage::disk($legacyDisk)->put($legacyPath, $stream);
        } finally {
            fclose($stream);
        }
    }

    private function rememberChecksum(Media $media, string $sourcePath): void
    {
        if (! is_file($sourcePath)) {
            return;
        }

        $media->setCustomProperty('legacy_checksum', hash_file('sha256', $sourcePath));
        $media->save();
    }

    private function mediaChecksum(Media $media): ?string
    {
        $disk = Storage::disk($media->disk);
        $stream = $disk->readStream($media->getPathRelativeToRoot());

        if (! is_resource($stream)) {
            return null;
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }

    private function withoutAutomaticConversions(callable $callback): mixed
    {
        $key = 'media-library.queue_conversions_by_default';
        $previous = config($key, true);
        config([$key => false]);

        try {
            return $callback();
        } finally {
            config([$key => $previous]);
        }
    }
}
