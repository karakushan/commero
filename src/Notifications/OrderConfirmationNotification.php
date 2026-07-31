<?php

namespace Commero\Notifications;

use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Support\Mail\OutboundMailStatus;
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
        $statusName = OrderStatus::query()->where('code', $status)->first()?->name;

        if (filled($statusName)) {
            return $statusName;
        }

        $key = 'commero::admin.order.status.'.$status;
        $label = __($key);

        return $label === $key ? $status : $label;
    }
}
