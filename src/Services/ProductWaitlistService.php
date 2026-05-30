<?php

namespace Commero\Services;

use Commero\Models\Product;
use Commero\Models\ProductWaitlistSubscription;
use Commero\Models\User;
use Commero\Support\Locales;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductWaitlistService
{
    public function subscribe(Product $product, string $email, ?string $locale = null, ?User $user = null): ProductWaitlistSubscription
    {
        $validated = Validator::make([
            'email' => mb_strtolower(trim($email)),
        ], [
            'email' => ['required', 'email'],
        ], [], [
            'email' => __('Email'),
        ])->validate();

        if ($product->effectiveStockStatus() === 'in_stock') {
            throw ValidationException::withMessages([
                'email' => __('This product is already in stock.'),
            ]);
        }

        $subscription = ProductWaitlistSubscription::query()->firstOrNew([
            'product_id' => $product->id,
            'email' => $validated['email'],
        ]);

        $subscription->fill([
            'user_id' => $user?->id ?? $subscription->user_id,
            'locale' => Locales::resolve($locale),
            'notified_at' => null,
        ]);
        $subscription->save();

        return $subscription;
    }
}
