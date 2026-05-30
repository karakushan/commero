<?php

namespace Commero\Support\Filament\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class VideoEmbedBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'video-embed';
    }

    public static function getLabel(): string
    {
        return __('admin.resources.post.editor.video_embed.label');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading(__('admin.resources.post.editor.video_embed.modal_heading'))
            ->schema([
                TextInput::make('url')
                    ->label(__('admin.resources.post.editor.video_embed.url'))
                    ->placeholder('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
                    ->helperText(__('admin.resources.post.editor.video_embed.helper_text'))
                    ->required()
                    ->url()
                    ->rule(static fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! is_string($value) || ! static::getEmbedData($value)) {
                            $fail(__('admin.resources.post.editor.video_embed.validation'));
                        }
                    }),
                TextInput::make('width')
                    ->label(__('admin.resources.post.editor.video_embed.width'))
                    ->helperText(__('admin.resources.post.editor.video_embed.width_helper_text'))
                    ->numeric()
                    ->integer()
                    ->minValue(200)
                    ->maxValue(1600)
                    ->suffix('px'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $embedData = static::getEmbedData((string) ($config['url'] ?? ''));

        if (! $embedData) {
            return static::getLabel();
        }

        return __('admin.resources.post.editor.video_embed.preview_label', [
            'provider' => $embedData['provider_label'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): ?string
    {
        return static::buildEmbedHtml($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): ?string
    {
        return static::buildEmbedHtml($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function buildEmbedHtml(array $config): ?string
    {
        $embedData = static::getEmbedData((string) ($config['url'] ?? ''));

        if (! $embedData) {
            return null;
        }

        $src = e($embedData['embed_url']);
        $width = static::sanitizeWidth($config['width'] ?? null);
        $wrapperWidthStyle = $width !== null ? "max-width: {$width}px; " : '';
        $title = e(__('admin.resources.post.editor.video_embed.iframe_title', [
            'provider' => $embedData['provider_label'],
        ]));

        return <<<HTML
<div style="width: 100%; {$wrapperWidthStyle}margin: 24px 0;">
    <div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px;">
        <iframe
            src="{$src}"
            title="{$title}"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"
        ></iframe>
    </div>
</div>
HTML;
    }

    protected static function sanitizeWidth(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $width = (int) $value;

        if ($width < 200 || $width > 1600) {
            return null;
        }

        return $width;
    }

    /**
     * @return array{provider: string, provider_label: string, embed_url: string}|null
     */
    protected static function getEmbedData(string $url): ?array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            $videoId = $query['v'] ?? null;

            if (($videoId === null) && str_starts_with($path, 'embed/')) {
                $videoId = basename($path);
            }

            if (($videoId === null) && str_starts_with($path, 'shorts/')) {
                $videoId = basename($path);
            }

            return static::buildYoutubeData($videoId);
        }

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            return static::buildYoutubeData(basename($path));
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true)) {
            return static::buildVimeoData(basename($path));
        }

        if (in_array($host, ['player.vimeo.com', 'www.player.vimeo.com'], true) && str_starts_with($path, 'video/')) {
            return static::buildVimeoData(basename($path));
        }

        return null;
    }

    /**
     * @return array{provider: string, provider_label: string, embed_url: string}|null
     */
    protected static function buildYoutubeData(mixed $videoId): ?array
    {
        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId)) {
            return null;
        }

        return [
            'provider' => 'youtube',
            'provider_label' => 'YouTube',
            'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
        ];
    }

    /**
     * @return array{provider: string, provider_label: string, embed_url: string}|null
     */
    protected static function buildVimeoData(mixed $videoId): ?array
    {
        if (! is_string($videoId) || ! preg_match('/^\d+$/', $videoId)) {
            return null;
        }

        return [
            'provider' => 'vimeo',
            'provider_label' => 'Vimeo',
            'embed_url' => 'https://player.vimeo.com/video/' . $videoId,
        ];
    }
}
