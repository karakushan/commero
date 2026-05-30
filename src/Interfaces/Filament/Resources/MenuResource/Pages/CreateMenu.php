<?php

namespace Commero\Interfaces\Filament\Resources\MenuResource\Pages;

use Commero\Interfaces\Filament\Resources\MenuResource;
use Commero\Interfaces\Filament\Resources\MenuResource\Pages\Concerns\InteractsWithMenuTranslations;
use Commero\Models\Menu;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMenu extends CreateRecord
{
    use InteractsWithMenuTranslations;

    protected static string $resource = MenuResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'items' => $this->getItemsFormState(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareMenuData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items'], $data['active_locale_context']);

        $menu = new Menu($data);
        $menu->save();

        $this->syncItems($menu, $items);

        return $menu->refresh();
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
