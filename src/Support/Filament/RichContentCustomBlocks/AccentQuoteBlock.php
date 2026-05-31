<?php

namespace Commero\Support\Filament\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class AccentQuoteBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'accent-quote';
    }

    public static function getLabel(): string
    {
        return __('commero::admin.resources.post.editor.accent_quote.label');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading(__('commero::admin.resources.post.editor.accent_quote.modal_heading'))
            ->schema([
                TextInput::make('accent_text')
                    ->label(__('commero::admin.resources.post.editor.accent_quote.accent_text'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('body_text')
                    ->label(__('commero::admin.resources.post.editor.accent_quote.body_text'))
                    ->required()
                    ->rows(4),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $accentText = trim((string) ($config['accent_text'] ?? ''));

        if ($accentText === '') {
            return static::getLabel();
        }

        return __('commero::admin.resources.post.editor.accent_quote.preview_label', [
            'text' => mb_strimwidth($accentText, 0, 60, '...'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): ?string
    {
        return static::buildHtml($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): ?string
    {
        return static::buildHtml($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function buildHtml(array $config): ?string
    {
        $accentText = trim((string) ($config['accent_text'] ?? ''));
        $bodyText = trim((string) ($config['body_text'] ?? ''));

        if (($accentText === '') && ($bodyText === '')) {
            return null;
        }

        $accentHtml = $accentText !== ''
            ? '<p class="post-accent-quote__accent">' . e($accentText) . '</p>'
            : '';

        $bodyHtml = $bodyText !== ''
            ? '<p class="post-accent-quote__body">' . nl2br(e($bodyText)) . '</p>'
            : '';

        return '<div class="post-accent-quote">' . $accentHtml . $bodyHtml . '</div>';
    }
}
