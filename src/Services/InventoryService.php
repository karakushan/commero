<?php

namespace Commero\Services;

use Commero\Models\Order;
use Commero\Models\Product;
use Commero\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Reserve the requested quantities while the surrounding order transaction is open.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function reserve(array $items, string $errorKey = 'cart'): void
    {
        $quantities = collect($items)
            ->filter(fn (array $item): bool => (int) ($item['quantity'] ?? 0) > 0)
            ->groupBy(fn (array $item): int => (int) ($item['variant_id'] ?? 0))
            ->map(fn (Collection $items): int => $items->sum(fn (array $item): int => (int) $item['quantity']))
            ->reject(fn (int $quantity, int $variantId): bool => $variantId < 1 || $quantity < 1);

        if ($quantities->isEmpty()) {
            return;
        }

        $variants = ProductVariant::query()
            ->with('product:id,type,status,stock_status')
            ->whereKey($quantities->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant instanceof ProductVariant
                || ! $variant->product instanceof Product
                || $variant->product->status !== 'published'
                || (! $variant->product->isAlwaysInStock()
                    && (! in_array($variant->status, ['in_stock', 'active'], true)
                        || $variant->stock_qty < $quantity))) {
                throw ValidationException::withMessages([
                    $errorKey => __('This product variant is currently unavailable.'),
                ]);
            }
        }

        foreach ($quantities as $variantId => $quantity) {
            /** @var ProductVariant $variant */
            $variant = $variants->get($variantId);

            if ($variant->product->isAlwaysInStock()) {
                continue;
            }

            $remainingQuantity = $variant->stock_qty - $quantity;

            $variant->forceFill([
                'stock_qty' => $remainingQuantity,
                'status' => $remainingQuantity === 0 ? 'out_of_stock' : $variant->status,
            ])->save();

            if ($remainingQuantity === 0 && $variant->product->type === 'simple') {
                $variant->product->forceFill(['stock_status' => 'out_of_stock'])->save();
            }
        }
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder || $lockedOrder->inventory_released_at !== null) {
                return;
            }

            $quantities = $lockedOrder->items()
                ->select(['variant_id', 'quantity'])
                ->whereNotNull('variant_id')
                ->get()
                ->groupBy('variant_id')
                ->map(fn (Collection $items): int => $items->sum('quantity'));

            $variants = ProductVariant::query()
                ->with('product:id,type,stock_status')
                ->whereKey($quantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if (! $variant instanceof ProductVariant) {
                    continue;
                }

                if ($variant->product->isAlwaysInStock()) {
                    continue;
                }

                $variant->forceFill([
                    'stock_qty' => $variant->stock_qty + $quantity,
                    'status' => $variant->status === 'out_of_stock' ? 'in_stock' : $variant->status,
                ])->save();

                if ($variant->product?->type === 'simple' && $variant->product->stock_status === 'out_of_stock') {
                    $variant->product->forceFill(['stock_status' => 'in_stock'])->save();
                }
            }

            $lockedOrder->forceFill(['inventory_released_at' => now()])->saveQuietly();
        });
    }
}
