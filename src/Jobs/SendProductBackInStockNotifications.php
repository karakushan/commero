<?php

namespace Commero\Jobs;

use Commero\Mail\ProductBackInStockMail;
use Commero\Models\Product;
use Commero\Models\ProductWaitlistSubscription;
use Commero\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendProductBackInStockNotifications implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $productId,
    ) {}

    public function handle(): void
    {
        $product = Product::query()
            ->with('translations')
            ->find($this->productId);

        if (! $product || ($product->stock_status ?? 'out_of_stock') !== 'in_stock') {
            return;
        }

        ProductWaitlistSubscription::query()
            ->where('product_id', $product->id)
            ->whereNull('notified_at')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($product): void {
                foreach ($subscriptions as $subscription) {
                    $claimed = ProductWaitlistSubscription::query()
                        ->whereKey($subscription->id)
                        ->whereNull('notified_at')
                        ->update([
                            'notified_at' => now(),
                            'updated_at' => now(),
                        ]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    $locale = Locales::resolve($subscription->locale);
                    $productName = $this->resolveProductName($product, $locale);
                    $productUrl = $this->resolveProductUrl($product, $locale);

                    try {
                        Mail::to($subscription->email)
                            ->locale($locale)
                            ->send(new ProductBackInStockMail($productName, $productUrl));
                    } catch (\Throwable $exception) {
                        ProductWaitlistSubscription::query()
                            ->whereKey($subscription->id)
                            ->update([
                                'notified_at' => null,
                                'updated_at' => now(),
                            ]);

                        throw $exception;
                    }
                }
            });
    }

    private function resolveProductName(Product $product, string $locale): string
    {
        $translation = $product->translation($locale)
            ?? $product->translation(Locales::default());

        return $translation?->name ?? $product->sku;
    }

    private function resolveProductUrl(Product $product, string $locale): string
    {
        $localizedTranslation = $product->translation($locale);

        if (filled($localizedTranslation?->slug)) {
            return Locales::isDefault($locale)
                ? route('product.show', ['slug' => $localizedTranslation->slug])
                : route('localized.product.show', ['locale' => $locale, 'slug' => $localizedTranslation->slug]);
        }

        $defaultLocale = Locales::default();
        $defaultTranslation = $product->translation($defaultLocale);

        if (filled($defaultTranslation?->slug)) {
            return route('product.show', ['slug' => $defaultTranslation->slug]);
        }

        return Locales::path('/catalog', $locale);
    }
}
