<?php

namespace Commero\Services;

use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Models\PaymentMethod;
use Commero\Models\Product;
use Commero\Models\ProductVariant;
use Commero\Models\ShippingMethod;
use Commero\Models\User;
use Commero\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPlacementService
{
    public function placeCheckoutOrder(
        array $validated,
        array $cart,
        ?User $user,
        ShippingMethod $shippingMethod,
        PaymentMethod $paymentMethod,
    ): Order {
        return $this->createOrder(
            attributes: [
                'user_id' => $user?->id,
                'customer_name' => trim($validated['firstName'].' '.$validated['lastName']),
                'customer_phone' => Phone::normalize($validated['phone']),
                'customer_email' => trim($validated['email']),
                'has_other_recipient' => (bool) ($validated['hasOtherRecipient'] ?? false),
                'recipient_first_name' => ($validated['hasOtherRecipient'] ?? false) ? (($validated['shippingFirstName'] ?? null) ?: null) : null,
                'recipient_last_name' => ($validated['hasOtherRecipient'] ?? false) ? (($validated['shippingLastName'] ?? null) ?: null) : null,
                'recipient_phone' => ($validated['hasOtherRecipient'] ?? false) ? Phone::normalize(($validated['shippingPhone'] ?? null) ?: null) : null,
                'recipient_email' => ($validated['hasOtherRecipient'] ?? false) ? (($validated['shippingEmail'] ?? null) ?: null) : null,
                'comment' => ($validated['comment'] ?? null) ?: null,
                'total_amount' => (float) ($cart['total_numeric'] ?? 0),
                'payment_method_code' => $paymentMethod->code,
                'payment_method_name' => $paymentMethod->name,
                'shipping_method_code' => $shippingMethod->code,
                'shipping_method_name' => $shippingMethod->name,
                'delivery_city_ref' => ($validated['deliveryCityRef'] ?? null) ?: null,
                'delivery_city_name' => ($validated['deliveryCityName'] ?? null) ?: null,
                'delivery_warehouse_ref' => ($validated['deliveryWarehouseRef'] ?? null) ?: null,
                'delivery_warehouse_name' => ($validated['deliveryWarehouseName'] ?? null) ?: null,
                'delivery_street' => ($validated['deliveryStreet'] ?? null) ?: null,
                'delivery_house' => ($validated['deliveryHouse'] ?? null) ?: null,
                'delivery_apartment' => ($validated['deliveryApartment'] ?? null) ?: null,
                'is_quick_order' => false,
            ],
            items: collect($cart['items'] ?? [])
                ->map(fn (array $item): array => [
                    'product_id' => (int) data_get($item, 'product.id'),
                    'variant_id' => (int) data_get($item, 'variant.id'),
                    'product_name' => (string) data_get($item, 'product.name', ''),
                    'product_sku' => (string) data_get($item, 'product.sku', ''),
                    'variant_name' => (string) data_get($item, 'variant.name', ''),
                    'variant_sku' => (string) data_get($item, 'variant.sku', ''),
                    'variant_attributes' => data_get($item, 'variant.attributes', []),
                    'unit_price' => (float) data_get($item, 'unit_price_numeric', 0),
                    'old_price' => data_get($item, 'old_price_numeric'),
                    'quantity' => (int) data_get($item, 'quantity', 0),
                ])
                ->all(),
            user: $user,
            userProfileUpdates: [
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'phone' => Phone::normalize($validated['phone']),
                'email' => trim($validated['email']),
                'delivery_shipping_method_id' => $shippingMethod->id,
                'delivery_city_ref' => ($validated['deliveryCityRef'] ?? null) ?: null,
                'delivery_city_name' => ($validated['deliveryCityName'] ?? null) ?: null,
                'delivery_warehouse_ref' => ($validated['deliveryWarehouseRef'] ?? null) ?: null,
                'delivery_warehouse_name' => ($validated['deliveryWarehouseName'] ?? null) ?: null,
                'delivery_street' => ($validated['deliveryStreet'] ?? null) ?: null,
                'delivery_house' => ($validated['deliveryHouse'] ?? null) ?: null,
                'delivery_apartment' => ($validated['deliveryApartment'] ?? null) ?: null,
            ],
        );
    }

    public function placeQuickOrder(Product $product, int $quantity, string $phone): Order
    {
        if ($product->status !== 'published' || $product->effectiveStockStatus() !== 'in_stock') {
            throw ValidationException::withMessages([
                'phone' => __('Product is unavailable for quick order.'),
            ]);
        }

        $variant = $product->variants()
            ->whereIn('status', ['in_stock', 'active'])
            ->orderBy('sort')
            ->orderBy('id')
            ->first();

        if (! $variant instanceof ProductVariant) {
            throw ValidationException::withMessages([
                'phone' => __('Product variant was not found.'),
            ]);
        }

        $resolvedQuantity = max(1, $quantity);
        $shippingMethod = $this->defaultShippingMethod();
        $paymentMethod = $this->defaultPaymentMethod();

        return $this->createOrder(
            attributes: [
                'user_id' => auth()->id(),
                'customer_name' => __('Quick order customer'),
                'customer_phone' => Phone::normalize($phone),
                'customer_email' => auth()->user()?->email,
                'has_other_recipient' => false,
                'recipient_first_name' => null,
                'recipient_last_name' => null,
                'recipient_phone' => null,
                'recipient_email' => null,
                'comment' => null,
                'total_amount' => round(((float) $variant->price) * $resolvedQuantity, 2),
                'payment_method_code' => $paymentMethod?->code,
                'payment_method_name' => $paymentMethod?->name,
                'shipping_method_code' => $shippingMethod?->code,
                'shipping_method_name' => $shippingMethod?->name,
                'delivery_city_ref' => null,
                'delivery_city_name' => null,
                'delivery_warehouse_ref' => null,
                'delivery_warehouse_name' => null,
                'delivery_street' => null,
                'delivery_house' => null,
                'delivery_apartment' => null,
                'is_quick_order' => true,
            ],
            items: [[
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'product_name' => $product->translation(app()->getLocale())?->name ?? $product->sku ?? __('Product'),
                'product_sku' => $product->sku ?? '',
                'variant_name' => $variant->name ?: __('Default'),
                'variant_sku' => $variant->sku ?? '',
                'variant_attributes' => $this->variantAttributesSnapshot($variant),
                'unit_price' => (float) $variant->price,
                'old_price' => $variant->old_price,
                'quantity' => $resolvedQuantity,
            ]],
            user: auth()->user(),
        );
    }

    public function defaultShippingMethod(): ?ShippingMethod
    {
        return ShippingMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->first();
    }

    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->first();
    }

    private function createOrder(array $attributes, array $items, ?User $user = null, array $userProfileUpdates = []): Order
    {
        return DB::transaction(function () use ($attributes, $items, $user, $userProfileUpdates): Order {
            $order = Order::query()->create(array_merge([
                'number' => $this->generateOrderNumber(),
                'status' => OrderStatus::query()->where('is_default_for_new_order', true)->value('code') ?? 'new',
            ], $attributes));

            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($productId < 1 || $quantity < 1) {
                    continue;
                }

                $order->items()->create([
                    'product_id' => $productId,
                    'variant_id' => ($item['variant_id'] ?? 0) ?: null,
                    'product_name' => $item['product_name'] ?? null,
                    'product_sku' => $item['product_sku'] ?? null,
                    'variant_name' => $item['variant_name'] ?? null,
                    'variant_sku' => $item['variant_sku'] ?? null,
                    'variant_attributes' => $item['variant_attributes'] ?? [],
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'old_price' => $item['old_price'] !== null ? (float) $item['old_price'] : null,
                    'quantity' => $quantity,
                ]);
            }

            if ($user && $userProfileUpdates !== []) {
                $user->forceFill($userProfileUpdates)->save();
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'SH-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    private function variantAttributesSnapshot(ProductVariant $variant): array
    {
        $variant->loadMissing([
            'attributeValues.attribute.translations',
            'attributeValues.option.translations',
        ]);

        return $variant->attributeValues
            ->map(function ($value): ?array {
                $attributeName = $value->attribute?->translation(app()->getLocale())?->name;
                $optionLabel = $value->option?->translation(app()->getLocale())?->label ?? $value->option?->value;

                if (! filled($attributeName) || ! filled($optionLabel)) {
                    return null;
                }

                return [
                    'attribute' => $attributeName,
                    'value' => $optionLabel,
                    'label' => $attributeName.': '.$optionLabel,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
