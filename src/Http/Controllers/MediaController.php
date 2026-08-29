<?php

namespace Commero\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController
{
    public function original(string $media): StreamedResponse
    {
        $record = Media::query()->where('uuid', $media)->firstOrFail();
        $allowed = array_keys((array) config('commero.media.collections', []));

        abort_unless(in_array($record->collection_name, $allowed, true), 404);

        $disk = Storage::disk($record->disk);
        $path = $record->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $record->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
