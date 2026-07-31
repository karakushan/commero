<?php

namespace Commero\Notifications;

use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return OutboundMailStatus::isConfigured() ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('commero::app.order_notifications.status_subject', ['number' => $this->order->number]))
            ->view(config('commero.notifications.order_status_changed_view'), [
                'title' => __('commero::app.order_notifications.status_title'),
                'intro' => __('commero::app.order_notifications.status_intro', ['number' => $this->order->number]),
                'order' => $this->order,
                'statusLabel' => $this->statusLabel($this->order->status),
                'previousStatusLabel' => $this->statusLabel($this->previousStatus),
                'adminUrl' => null,
            ]);
    }

    private function statusLabel(string $status): string
    {
        $statusModel = OrderStatus::query()->with('translations')->where('code', $status)->first();
        $requestedLocale = $this->order->locale ?: Locales::fallback();
        $statusName = $statusModel?->translations->firstWhere('locale', $requestedLocale)?->name
            ?? $statusModel?->translations->firstWhere('locale', Locales::emailLocale($requestedLocale))?->name
            ?? $statusModel?->getRawOriginal('name');

        if (filled($statusName)) {
            return $statusName;
        }

        $key = 'commero::admin.order.status.'.$status;
        $label = __($key);

        return $label === $key ? $status : $label;
    }
}
