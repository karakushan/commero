<?php

namespace Commero\Commands;

use Commero\Jobs\GenerateMediaConversions;
use Commero\Models\Category;
use Commero\Models\Post;
use Commero\Models\ProductImage;
use Commero\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Throwable;

class GenerateMediaCommand extends Command
{
    protected $signature = 'commero:media:generate
        {--model=all : all, product-image, category or post}
        {--id=* : Limit to one or more model IDs}
        {--only-missing : Generate only conversions that do not exist}
        {--force : Regenerate all configured conversions}
        {--sync : Generate in this process instead of dispatching jobs}';

    protected $description = 'Import legacy images and generate configured Commero media conversions.';

    public function handle(MediaService $mediaService, FileManipulator $fileManipulator): int
    {
        $model = (string) $this->option('model');
        $allowed = ['all', 'product-image', 'category', 'post'];

        if (! in_array($model, $allowed, true)) {
            $this->error('The --model option must be all, product-image, category or post.');

            return self::INVALID;
        }

        $onlyMissing = ! (bool) $this->option('force');
        $processed = $queued = $generated = $skipped = $failed = 0;

        foreach ($this->targets($model) as $target) {
            [$record, $collection] = $target;
            $processed++;

            try {
                $media = $mediaService->importLegacy(
                    $record,
                    $collection,
                    $record->getAttribute($mediaService->legacyFieldFor($record, $collection)),
                );

                if (! $media) {
                    $skipped++;
                    continue;
                }

                $conversionNames = (array) config("commero.media.collections.{$collection}", []);
                $conversions = ConversionCollection::createForMedia($media)
                    ->filter(fn ($conversion): bool => in_array($conversion->getName(), $conversionNames, true));

                if ($onlyMissing) {
                    $conversions = $mediaService->onlyMissingConversions($media, $conversions);
                }

                if ($conversions->isEmpty()) {
                    $skipped++;
                    continue;
                }

                if ($this->option('sync')) {
                    if (! $onlyMissing) {
                        $mediaService->removeConversions($media, $conversions);
                    }

                    $fileManipulator->performConversions($conversions, $media);
                    $generated++;
                } else {
                    GenerateMediaConversions::dispatch($media->getKey(), $conversionNames, $onlyMissing)
                        ->onQueue(config('commero.media.queue.queue', 'media'));
                    $queued++;
                }
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->warn(sprintf('%s #%s [%s]: %s', class_basename($record), $record->getKey(), $collection, $exception->getMessage()));
            }
        }

        $this->line("Processed: {$processed}; queued: {$queued}; generated: {$generated}; skipped: {$skipped}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return iterable<array{0: Model, 1: string}>
     */
    private function targets(string $model): iterable
    {
        $definitions = match ($model) {
            'product-image' => [[ProductImage::class, ['product_gallery']]],
            'category' => [[Category::class, ['category_thumbnail', 'category_icon']]],
            'post' => [[Post::class, ['post_thumbnail']]],
            default => [
                [ProductImage::class, ['product_gallery']],
                [Category::class, ['category_thumbnail', 'category_icon']],
                [Post::class, ['post_thumbnail']],
            ],
        };

        $ids = array_values(array_filter(array_map('strval', (array) $this->option('id'))));

        foreach ($definitions as [$class, $collections]) {
            $query = $class::query();

            if ($ids !== []) {
                $query->whereKey($ids);
            }

            foreach ($query->cursor() as $record) {
                foreach ($collections as $collection) {
                    yield [$record, $collection];
                }
            }
        }
    }
}
