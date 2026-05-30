<?php

namespace Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderStatusResource;
use Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages\Concerns\InteractsWithOrderStatusTranslations;
use Commero\Models\OrderStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrderStatus extends CreateRecord
{
    use InteractsWithOrderStatusTranslations;

    protected static string $resource = OrderStatusResource::class;

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
        return $this->prepareOrderStatusData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $orderStatus = new OrderStatus($data);
        $orderStatus->save();

        $this->syncTranslations($orderStatus, $translations);

        return $orderStatus;
    }

    protected function afterCreate(): void
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
