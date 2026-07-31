<?php

namespace Commero\Services;

use Commero\Models\Order;
use Commero\Models\User;
use Commero\Notifications\OrderConfirmationNotification;
use Commero\Notifications\OrderReceivedNotification;
use Commero\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class OrderNotificationService
{
    public function notifyAboutNewOrder(Order $order): void
    {
        $recipients = User::query()
            ->permission(OrderReceivedNotification::permissionName())
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::locale(config('commero.locales.default', config('app.locale')))
                ->send($recipients, new OrderReceivedNotification($order->loadMissing('items')));
        } catch (Throwable $exception) {
            Log::error('Order notification failed.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function notifyCustomerAboutNewOrder(Order $order): void
    {
        $customerEmail = $this->customerEmail($order);

        if (! filled($customerEmail)) {
            return;
        }

        try {
            Notification::locale($order->locale ?: config('commero.locales.fallback', config('app.fallback_locale')))
                ->route('mail', $customerEmail)
                ->notify(new OrderConfirmationNotification($this->loadOrderForEmail($order)));
        } catch (Throwable $exception) {
            Log::error('Order confirmation notification failed.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'customer_email' => $customerEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function notifyCustomerAboutStatusChange(Order $order, string $previousStatus): void
    {
        $customerEmail = $this->customerEmail($order);

        if (! filled($customerEmail)) {
            return;
        }

        try {
            Notification::locale($order->locale ?: config('commero.locales.fallback', config('app.fallback_locale')))
                ->route('mail', $customerEmail)
                ->notify(new OrderStatusChangedNotification($this->loadOrderForEmail($order), $previousStatus));
        } catch (Throwable $exception) {
            Log::error('Order status notification failed.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'customer_email' => $customerEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function customerEmail(Order $order): ?string
    {
        return filled($order->customer_email)
            ? $order->customer_email
            : $order->user?->email;
    }

    private function loadOrderForEmail(Order $order): Order
    {
        return $order->loadMissing('items.product.primaryImage');
    }
}
