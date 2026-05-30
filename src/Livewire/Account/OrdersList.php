<?php

namespace Commero\Livewire\Account;

use Commero\Models\OrderStatus;
use Commero\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersList extends Component
{
    use WithPagination;

    private const PER_PAGE = 8;

    public User $user;

    public array $orderStatuses = [];

    protected function queryString(): array
    {
        return [
            'page' => ['except' => 1],
        ];
    }

    public function mount(User $user, array $orderStatuses = []): void
    {
        $this->user = $user;
        $this->orderStatuses = $orderStatuses !== []
            ? $orderStatuses
            : OrderStatus::query()
                ->withTranslationsFor(app()->getLocale())
                ->get(['id', 'code', 'name', 'color', 'text_color', 'icon'])
                ->mapWithKeys(fn (OrderStatus $status): array => [
                    $status->code => [
                        'name' => $status->name,
                        'color' => $status->color,
                        'text_color' => $status->text_color,
                        'icon' => $status->icon,
                    ],
                ])
                ->all();
    }

    public function paginationView(): string
    {
        return 'livewire.pagination.account-orders';
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.account.orders-list', [
            'orders' => $this->user->orders()
                ->latest()
                ->with([
                    'items.product' => fn ($productQuery) => $productQuery
                        ->withTranslationsFor($locale)
                        ->with(['primaryImage', 'variants']),
                ])
                ->paginate(self::PER_PAGE),
        ]);
    }
}
