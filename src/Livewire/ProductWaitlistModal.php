<?php

namespace Commero\Livewire;

use Commero\Models\Product;
use Commero\Services\MarketingLeadService;
use Commero\Services\ProductWaitlistService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class ProductWaitlistModal extends Component
{
    public bool $isOpen = false;

    public string $state = 'form';

    public ?int $productId = null;

    public string $productName = '';

    public string $email = '';

    protected ProductWaitlistService $waitlistService;

    protected MarketingLeadService $marketingLeadService;

    public function boot(ProductWaitlistService $waitlistService, MarketingLeadService $marketingLeadService): void
    {
        $this->waitlistService = $waitlistService;
        $this->marketingLeadService = $marketingLeadService;
    }

    public function open(int $productId, string $productName = '', string $prefilledEmail = ''): void
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->email = $prefilledEmail !== '' ? $prefilledEmail : (Auth::user()?->email ?? '');
        $this->state = 'form';
        $this->isOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function close(): void
    {
        $this->reset(['isOpen', 'productId', 'productName']);
        $this->state = 'form';
        $this->email = '';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    /**
     * @throws ValidationException
     */
    public function subscribe(): void
    {
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'email' => ['required', 'email'],
        ], [], [
            'productId' => __('Product'),
            'email' => __('Email'),
        ]);

        $product = Product::query()->findOrFail($validated['productId']);

        $this->waitlistService->subscribe(
            $product,
            $validated['email'],
            app()->getLocale(),
            Auth::user(),
        );

        $this->marketingLeadService->create([
            'type' => 'product_waitlist',
            'subject' => $product->translation(app()->getLocale())?->name ?? $product->sku,
            'email' => $validated['email'],
            'product_id' => $product->id,
            'locale' => app()->getLocale(),
            'form_data' => [
                'product_id' => $product->id,
                'product_name' => $product->translation(app()->getLocale())?->name ?? $product->sku,
                'email' => $validated['email'],
            ],
        ]);

        $this->state = 'success';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.product-waitlist-modal');
    }
}
