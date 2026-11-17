<?php

namespace Commero\Payments\Concerns;

use Commero\Models\Order;

trait BuildsInstallmentProducts
{
    /**
     * Order lines (unit price) when they add up to the order total, otherwise one line for the whole order.
     *
     * @return list<array{name: string, count: int, price: float}>
     */
    protected function installmentProducts(Order $order): array
    {
        $order->loadMissing('items');
        $total = round((float) $order->total_amount, 2);

        $lines = $order->items
            ->filter(fn ($item): bool => (int) $item->quantity > 0 && (float) $item->unit_price > 0)
            ->map(fn ($item): array => [
                'name' => mb_substr(trim((string) $item->product_name) ?: __('commero::checkout.payment.product'), 0, 250),
                'count' => (int) $item->quantity,
                'price' => round((float) $item->unit_price, 2),
            ])
            ->values();

        $sum = round($lines->sum(fn (array $line): float => $line['count'] * $line['price']), 2);

        if ($lines->isNotEmpty() && abs($sum - $total) < 0.01) {
            return $lines->all();
        }

        return [[
            'name' => __('commero::checkout.payment.order_line', ['number' => $order->number]),
            'count' => 1,
            'price' => $total,
        ]];
    }

    public function maxPartsFor(Order $order): int
    {
        $limit = (int) data_get($order->payment_payload, '_max_parts', 0);

        return $limit > 0 ? max($this->minParts(), min($this->maxParts(), $limit)) : $this->maxParts();
    }
}
