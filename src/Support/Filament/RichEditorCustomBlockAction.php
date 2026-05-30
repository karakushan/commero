<?php

namespace Commero\Support\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\Actions\CustomBlockAction;
use Filament\Forms\Components\RichEditor\EditorCommand;

class RichEditorCustomBlockAction
{
    public static function make(): Action
    {
        return Action::make(CustomBlockAction::NAME)
            ->fillForm(fn (array $arguments): ?array => $arguments['config'] ?? null)
            ->modalHeading(function (array $arguments, RichEditor $component): ?string {
                $block = $component->getCustomBlock($arguments['id']);

                if (blank($block)) {
                    return null;
                }

                return $block::getLabel();
            })
            ->modalSubmitActionLabel(fn (array $arguments): ?string => match ($arguments['mode']) {
                'insert' => __('filament-forms::components.rich_editor.actions.custom_block.modal.actions.insert.label'),
                'edit' => __('filament-forms::components.rich_editor.actions.custom_block.modal.actions.save.label'),
                default => null,
            })
            ->bootUsing(function (Action $action, array $arguments, RichEditor $component) {
                $block = $component->getCustomBlock($arguments['id']);

                if (blank($block)) {
                    return;
                }

                return $block::configureEditorAction($action);
            })
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $block = $component->getCustomBlock($arguments['id']);

                if (blank($block)) {
                    return;
                }

                $customBlockContent = [
                    'type' => 'customBlock',
                    'attrs' => [
                        'config' => $data,
                        'id' => $arguments['id'],
                        'label' => $block::getPreviewLabel($data),
                        'preview' => base64_encode($block::toPreviewHtml($data)),
                    ],
                ];

                $content = (($arguments['mode'] ?? null) === 'insert')
                    ? [
                        $customBlockContent,
                        ['type' => 'paragraph'],
                    ]
                    : $customBlockContent;

                if (filled($arguments['dragPosition'] ?? null)) {
                    $component->runCommands([
                        EditorCommand::make('insertContentAt', [
                            $arguments['dragPosition'],
                            $content,
                        ]),
                    ]);

                    return;
                }

                if (
                    (($arguments['editorSelection']['type'] ?? null) === 'node') &&
                    (($arguments['mode'] ?? null) === 'insert')
                ) {
                    $component->runCommands([
                        EditorCommand::make('insertContentAt', [
                            ($arguments['editorSelection']['anchor'] ?? -1) + 1,
                            $content,
                        ]),
                    ]);

                    return;
                }

                if (
                    (($arguments['mode'] ?? null) === 'edit') &&
                    (($arguments['editorSelection']['type'] ?? null) !== 'node')
                ) {
                    $arguments['editorSelection']['type'] = 'node';
                    $arguments['editorSelection']['anchor']--;

                    unset($arguments['editorSelection']['head']);
                }

                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', [
                            $content,
                        ]),
                    ],
                    editorSelection: $arguments['editorSelection'] ?? null,
                );
            });
    }
}
