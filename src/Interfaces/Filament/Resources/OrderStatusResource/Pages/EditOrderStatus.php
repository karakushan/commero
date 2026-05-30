<?php

namespace Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderStatusResource;
use Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages\Concerns\InteractsWithOrderStatusTranslations;
use Commero\Models\OrderStatus;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrderStatus extends EditRecord
{
    use InteractsWithOrderStatusTranslations;

    protected static string $resource = OrderStatusResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var OrderStatus $record */
        $record = $this->getRecord()->load('translations');

        return [
            ...$data,
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState($record),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var OrderStatus $record */
        $record = $this->getRecord();

        return $this->prepareOrderStatusDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var OrderStatus $record */
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);

        return $record->refresh();
    }

    protected function afterSave(): void
    {
        if ($this->record->is_default_for_new_order) {
            OrderStatus::query()
                ->where('id', '!=', $this->record->id)
                ->where('is_default_for_new_order', true)
                ->update(['is_default_for_new_order' => false]);
        }
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
