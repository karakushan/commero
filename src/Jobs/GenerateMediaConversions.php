<?php

namespace Commero\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Commero\Services\MediaService;

class GenerateMediaConversions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param array<int, string> $conversions
     */
    public function __construct(
        public int|string $mediaId,
        public array $conversions = [],
        public bool $onlyMissing = true,
    ) {}

    public function handle(FileManipulator $fileManipulator, MediaService $mediaService): void
    {
        $media = Media::query()->find($this->mediaId);

        if (! $media) {
            return;
        }

        $collection = ConversionCollection::createForMedia($media)
            ->filter(fn ($conversion): bool => $this->conversions === [] || in_array($conversion->getName(), $this->conversions, true));

        if ($this->onlyMissing) {
            $collection = $mediaService->onlyMissingConversions($media, $collection);
        }

        if (! $this->onlyMissing) {
            $mediaService->removeConversions($media, $collection);
        }

        $fileManipulator->performConversions($collection, $media);
    }
}
