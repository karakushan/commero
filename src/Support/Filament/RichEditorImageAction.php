<?php

namespace Commero\Support\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Livewire\Component;

class RichEditorImageAction
{
    public const NAME = 'insertImage';

    public static function make(): Action
    {
        return Action::make(static::NAME)
            ->label(__('commero::admin.rich_editor.image.button'))
            ->modalHeading(__('commero::admin.rich_editor.image.modal_heading'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => [
                'title' => static::normalizeText($arguments['title'] ?? null),
                'alt' => static::normalizeText($arguments['alt'] ?? null),
                'width' => static::normalizeDimension($arguments['width'] ?? null),
                'height' => static::normalizeDimension($arguments['height'] ?? null),
            ])
            ->schema(fn (array $arguments, RichEditor $component): array => [
                FileUpload::make('file')
                    ->label(filled($arguments['src'] ?? null)
                        ? __('commero::admin.rich_editor.image.file_existing')
                        : __('commero::admin.rich_editor.image.file_new'))
                    ->acceptedFileTypes($component->getFileAttachmentsAcceptedFileTypes())
                    ->maxSize($component->getFileAttachmentsMaxSize())
                    ->storeFiles(false)
                    ->required(blank($arguments['src'] ?? null))
                    ->hiddenLabel(blank($arguments['src'] ?? null)),
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('commero::admin.rich_editor.image.title'))
                            ->maxLength(255),
                        TextInput::make('alt')
                            ->label(__('commero::admin.rich_editor.image.alt'))
                            ->maxLength(1000),
                        TextInput::make('width')
                            ->label(__('commero::admin.rich_editor.image.width'))
                            ->inputMode('decimal')
                            ->rules([
                                'nullable',
                                'regex:/^\\d+(?:\\.\\d+)?(?:%|px|em|rem|vw|vh)?$/i',
                            ])
                            ->helperText(__('commero::admin.rich_editor.image.dimension_hint')),
                        TextInput::make('height')
                            ->label(__('commero::admin.rich_editor.image.height'))
                            ->inputMode('decimal')
                            ->rules([
                                'nullable',
                                'regex:/^\\d+(?:\\.\\d+)?(?:%|px|em|rem|vw|vh)?$/i',
                            ])
                            ->helperText(__('commero::admin.rich_editor.image.dimension_hint')),
                    ])
                    ->columnSpanFull(),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component, Component $livewire): void {
                $id = $arguments['id'] ?? null;
                $src = $arguments['src'] ?? null;

                if ($data['file'] ?? null) {
                    $id = (string) Str::orderedUuid();
                    data_set($livewire, "componentFileAttachments.{$component->getStatePath()}.{$id}", $data['file']);
                    $src = $component->getUploadedFileAttachmentTemporaryUrl($data['file']);
                }

                if (blank($src)) {
                    return;
                }

                $attributes = [
                    'alt' => static::normalizeText($data['alt'] ?? null),
                    'title' => static::normalizeText($data['title'] ?? null),
                    'width' => static::normalizeDimension($data['width'] ?? null),
                    'height' => static::normalizeDimension($data['height'] ?? null),
                    'id' => $id,
                    'src' => $src,
                ];
                $editorSelection = $arguments['editorSelection'] ?? null;

                if (filled($arguments['src'] ?? null)) {
                    if (($editorSelection['type'] ?? null) !== 'node') {
                        $editorSelection['type'] = 'node';
                        $editorSelection['anchor'] = max(0, ((int) ($editorSelection['anchor'] ?? 0)) - 1);
                        unset($editorSelection['head']);
                    }

                    $component->runCommands(
                        [
                            EditorCommand::make('updateAttributes', arguments: [
                                'image',
                                $attributes,
                            ]),
                        ],
                        editorSelection: $editorSelection,
                    );

                    return;
                }

                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'image',
                            'attrs' => $attributes,
                        ]]),
                    ],
                    editorSelection: $editorSelection,
                );
            });
    }

    private static function normalizeText(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : null;

        return filled($value) ? $value : null;
    }

    private static function normalizeDimension(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : null;

        if (blank($value)) {
            return null;
        }

        return preg_match('/^\\d+(?:\\.\\d+)?(?:%|px|em|rem|vw|vh)?$/i', $value)
            ? $value
            : null;
    }
}
