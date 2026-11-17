<?php

namespace Commero\Notifications;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Commero\Models\Order;
use Commero\Support\Locales;
use Commero\Support\Mail\OutboundMailStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (OutboundMailStatus::isConfigured()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = Locales::emailLocale(config('commero.locales.default', config('app.locale')));

        return (new MailMessage)
            ->subject(__('commero::app.order_notifications.paid_order_subject', ['number' => $this->order->number], $locale))
            ->line($this->summary($locale))
            ->action(__('commero::app.order_notifications.open_order', [], $locale), OrderResource::getUrl('view', ['record' => $this->order]));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('commero::app.order_notifications.paid_order_title'),
            'body' => $this->summary(),
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'url' => OrderResource::getUrl('view', ['record' => $this->order]),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    private function summary(?string $locale = null): string
    {
        return __('commero::app.order_notifications.paid_order_summary', [
            'number' => $this->order->number,
            'total' => number_format((float) $this->order->total_amount, 2, '.', ' '),
            'method' => $this->order->payment_method_name,
        ], $locale);
    }
}
