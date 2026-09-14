<?php

namespace Commero\Support\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\HasToolbarButtons;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Tiptap\Core\Extension;

class RichEditorImagePlugin implements HasToolbarButtons, RichContentPlugin
{
    public static function make(): static
    {
        return new static;
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [route('commero.filament.rich-editor.image-double-click')];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make(RichEditorImageAction::NAME)
                ->label(__('commero::admin.rich_editor.image.button'))
                ->action(
                    action: RichEditorImageAction::NAME,
                    arguments: '{ alt: $getEditor().getAttributes(\'image\')?.alt, title: $getEditor().getAttributes(\'image\')?.title, width: $getEditor().getAttributes(\'image\')?.width, height: $getEditor().getAttributes(\'image\')?.height, id: $getEditor().getAttributes(\'image\')?.id, src: $getEditor().getAttributes(\'image\')?.src }',
                )
                ->activeKey('image')
                ->icon('heroicon-o-photo'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            RichEditorImageAction::make(),
        ];
    }

    /**
     * @return array<string | array<string | array<string>>>
     */
    public function getEnabledToolbarButtons(): array
    {
        return [RichEditorImageAction::NAME];
    }

    /**
     * @return array<string>
     */
    public function getDisabledToolbarButtons(): array
    {
        return [];
    }
}
