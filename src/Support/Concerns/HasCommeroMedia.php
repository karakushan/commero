<?php

namespace Commero\Support\Concerns;

use Commero\Jobs\GenerateMediaConversions;
use Commero\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasCommeroMedia
{
    use InteractsWithMedia;

    public function getMorphClass(): string
    {
        // Host applications commonly subclass Commero models. Keeping one
        // morph type makes media created through App\\Models\\* discoverable
        // by package commands and by the base package models.
        return 'Commero\\Models\\'.class_basename($this);
    }

    public static function bootHasCommeroMedia(): void
    {
        static::saved(function (Model $model): void {
            foreach ($model->commeroMediaCollections() as $collection => $field) {
                if (! $model->wasChanged($field) && $model->getFirstMedia($collection)) {
                    continue;
                }

                $media = app(MediaService::class)->importLegacy($model, $collection, $model->getAttribute($field));

                if ($media) {
                    GenerateMediaConversions::dispatch($media->getKey(), [], true)
                        ->onQueue(config('commero.media.queue.queue', 'media'))
                        ->afterCommit();
                }
            }
        });
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Collections register their own allowed sizes. This method is kept for
        // host applications that add a custom collection at runtime.
    }

    protected function registerCommeroConversions(array $names): void
    {
        foreach ($names as $name) {
            $definition = (array) config("commero.media.sizes.{$name}", []);
            $conversion = $this->addMediaConversion($name);
            $this->applyCommeroConversionDefinition($conversion, $definition);
        }
    }

    protected function addConfiguredMediaCollection(string $name): void
    {
        $collection = $this->addMediaCollection($name)
            ->useDisk(config('commero.media.original_disk', 'private'))
            ->storeConversionsOnDisk(config('commero.media.conversion_disk', 'public'));

        $collection->registerMediaConversions(function () use ($name): void {
            $this->registerCommeroConversions((array) config("commero.media.collections.{$name}", []));
        });
    }

    protected function applyCommeroConversionDefinition(Conversion $conversion, array $definition): void
    {
        $width = isset($definition['width']) ? (int) $definition['width'] : null;
        $height = isset($definition['height']) ? (int) $definition['height'] : null;

        if ($width !== null) {
            $conversion->width($width);
        } elseif ($height !== null) {
            $conversion->height($height);
        }

        $format = config('commero.media.webp.enabled', true)
            ? (string) ($definition['format'] ?? 'webp')
            : null;

        if ($format) {
            $conversion->format($format);
        }

        if (isset($definition['quality'])) {
            $conversion->quality((int) $definition['quality']);
        }
    }

    /**
     * @return array<string, string>
     */
    abstract public function commeroMediaCollections(): array;
}
