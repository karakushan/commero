<?php

namespace Commero\Livewire\Account;

use Commero\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class AddressesForm extends Component
{
    public User $user;

    public string $deliveryCityRef = '';

    public string $deliveryCityName = '';

    public string $deliveryWarehouseRef = '';

    public string $deliveryWarehouseName = '';

    public string $deliveryStreet = '';

    public string $deliveryHouse = '';

    public string $deliveryApartment = '';

    public bool $saved = false;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->deliveryCityRef = (string) ($user->delivery_city_ref ?? '');
        $this->deliveryCityName = (string) ($user->delivery_city_name ?? '');
        $this->deliveryWarehouseRef = (string) ($user->delivery_warehouse_ref ?? '');
        $this->deliveryWarehouseName = (string) ($user->delivery_warehouse_name ?? '');
        $this->deliveryStreet = (string) ($user->delivery_street ?? '');
        $this->deliveryHouse = (string) ($user->delivery_house ?? '');
        $this->deliveryApartment = (string) ($user->delivery_apartment ?? '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'deliveryCityRef' => ['required', 'string', 'max:255'],
            'deliveryCityName' => ['required', 'string', 'max:255'],
            'deliveryWarehouseRef' => ['nullable', 'string', 'max:255'],
            'deliveryWarehouseName' => ['nullable', 'string', 'max:255'],
            'deliveryStreet' => ['nullable', 'string', 'max:255'],
            'deliveryHouse' => ['nullable', 'string', 'max:255'],
            'deliveryApartment' => ['nullable', 'string', 'max:255'],
        ], [], [
            'deliveryCityRef' => __('Delivery city'),
            'deliveryCityName' => __('Delivery city'),
            'deliveryWarehouseRef' => __('Nova Poshta branch'),
            'deliveryWarehouseName' => __('Nova Poshta branch'),
            'deliveryStreet' => __('Street'),
            'deliveryHouse' => __('House'),
            'deliveryApartment' => __('Apartment'),
        ]);

        $this->user->forceFill([
            'delivery_city_ref' => trim($validated['deliveryCityRef']),
            'delivery_city_name' => trim($validated['deliveryCityName']),
            'delivery_warehouse_ref' => trim((string) ($validated['deliveryWarehouseRef'] ?? '')) ?: null,
            'delivery_warehouse_name' => trim((string) ($validated['deliveryWarehouseName'] ?? '')) ?: null,
            'delivery_street' => trim((string) ($validated['deliveryStreet'] ?? '')) ?: null,
            'delivery_house' => trim((string) ($validated['deliveryHouse'] ?? '')) ?: null,
            'delivery_apartment' => trim((string) ($validated['deliveryApartment'] ?? '')) ?: null,
        ])->save();

        $this->user->refresh();
        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.account.addresses-form');
    }
}
