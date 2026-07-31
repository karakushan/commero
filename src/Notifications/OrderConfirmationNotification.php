<?php

namespace Commero\Notifications;

use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return OutboundMailStatus::isConfigured() ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('commero::app.order_notifications.customer_order_subject', ['number' => $this->order->number]))
            ->view(config('commero.notifications.order_confirmation_view'), [
                'title' => __('commero::app.order_notifications.customer_order_title'),
                'intro' => __('commero::app.order_notifications.customer_order_intro', ['number' => $this->order->number]),
                'order' => $this->order,
                'statusLabel' => $this->statusLabel($this->order->status),
                'adminUrl' => null,
            ]);
    }

    private function statusLabel(string $status): string
    {
        $statusModel = OrderStatus::query()->with('translations')->where('code', $status)->first();
        $requestedLocale = $this->order->locale ?: Locales::fallback();
        $emailLocale = Locales::emailLocale($requestedLocale);
        $statusName = $statusModel?->translations->firstWhere('locale', $requestedLocale)?->name
            ?? $statusModel?->translations->firstWhere('locale', $emailLocale)?->name;

        if (filled($statusName)) {
            return $statusName;
        }

        $key = 'commero::app.order_notifications.status_'.$status;
        $label = __($key, [], $emailLocale);

        if ($label !== $key) {
            return $label;
        }

        $rawName = $statusModel?->getRawOriginal('name');

        if (filled($rawName)) {
            return $rawName;
        }

        return $status;
    }
}
