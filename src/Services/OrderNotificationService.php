<?php

namespace Commero\Services;

use Commero\Models\Order;
use Commero\Models\User;
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

    public function notifyCustomerAboutStatusChange(Order $order, string $previousStatus): void
    {
        if (! filled($order->customer_email)) {
            return;
        }

        try {
            Notification::locale($order->locale ?: config('commero.locales.fallback', config('app.fallback_locale')))
                ->route('mail', $order->customer_email)
                ->notify(new OrderStatusChangedNotification($order->loadMissing('items'), $previousStatus));
        } catch (Throwable $exception) {
            Log::error('Order status notification failed.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'customer_email' => $order->customer_email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
