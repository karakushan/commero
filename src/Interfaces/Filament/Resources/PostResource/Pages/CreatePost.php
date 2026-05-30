<?php

namespace Commero\Interfaces\Filament\Resources\PostResource\Pages;

use Commero\Interfaces\Filament\Resources\PostResource;
use Commero\Interfaces\Filament\Resources\PostResource\Pages\Concerns\InteractsWithPostTranslations;
use Commero\Models\Post;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends CreateRecord
{
    use InteractsWithPostTranslations;

    protected static string $resource = PostResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->preparePostData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['active_locale_context']);

        $post = new Post($data);
        $post->save();

        $this->syncTranslations($post, $translations);

        return $post->refresh();
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
}
