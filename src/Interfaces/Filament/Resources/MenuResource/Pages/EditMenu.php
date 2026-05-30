<?php

namespace Commero\Interfaces\Filament\Resources\MenuResource\Pages;

use Commero\Interfaces\Filament\Resources\MenuResource;
use Commero\Interfaces\Filament\Resources\MenuResource\Pages\Concerns\InteractsWithMenuTranslations;
use Commero\Models\Menu;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMenu extends EditRecord
{
    use InteractsWithMenuTranslations;

    protected static string $resource = MenuResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Menu $record */
        $record = $this->getRecord()->load('items.translations');

        $data = [
            ...$data,
            ...$this->getActiveLocaleContextState(),
        ];
        $data['items'] = $this->getItemsFormState($record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Menu $record */
        $record = $this->getRecord();

        return $this->prepareMenuDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Menu $record */
        $items = $data['items'] ?? [];
        unset($data['items'], $data['active_locale_context']);

        $record->update($data);
        $this->syncItems($record, $items);

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
}
