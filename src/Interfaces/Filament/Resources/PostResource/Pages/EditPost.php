<?php

namespace Commero\Interfaces\Filament\Resources\PostResource\Pages;

use Commero\Interfaces\Filament\Resources\PostResource;
use Commero\Models\Post;
use Commero\Interfaces\Filament\Resources\PostResource\Pages\Concerns\InteractsWithPostTranslations;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPost extends EditRecord
{
    use InteractsWithPostTranslations;

    protected static string $resource = PostResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPost')
                ->label(__('commero::admin.post.actions.view_on_site'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getFrontendPostUrl())
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Post $record */
        $record = $this->getRecord()->load('translations');

        $data = [
            ...$data,
            ...$this->getActiveLocaleContextState(),
        ];
        $data['translations'] = $this->getTranslationsFormState($record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Post $record */
        $record = $this->getRecord();

        return $this->preparePostDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Post $record */
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);

        return $record->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }

    private function getFrontendPostUrl(): string
    {
        /** @var Post $post */
        $post = $this->getRecord();

        return PostResource::getFrontendPostUrl($post, $this->resolveActiveLocale()) ?? route('blog.index');
    }
}
