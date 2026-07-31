<?php

namespace Commero\Notifications;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Locales;
use Commero\Support\Permissions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReceivedNotification extends Notification
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
        $emailLocale = Locales::emailLocale(config('commero.locales.default', config('app.locale')));

        return (new MailMessage)
            ->subject(__('commero::app.order_notifications.new_order_subject', ['number' => $this->order->number], $emailLocale))
            ->view(config('commero.notifications.order_received_view'), [
                'emailLocale' => $emailLocale,
                'settingsLocale' => config('commero.locales.default', config('app.locale')),
                'title' => __('commero::app.order_notifications.new_order_title', [], $emailLocale),
                'intro' => __('commero::app.order_notifications.new_order_intro', [], $emailLocale),
                'order' => $this->order,
                'statusLabel' => $this->statusLabel($this->order->status),
                'adminUrl' => OrderResource::getUrl('view', ['record' => $this->order]),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('commero::app.order_notifications.new_order_title'),
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

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title(__('commero::app.order_notifications.new_order_title'))
            ->body($this->summary())
            ->actions([
                Action::make('view')
                    ->label(__('commero::app.order_notifications.open_order'))
                    ->url(OrderResource::getUrl('view', ['record' => $this->order])),
            ]);
    }

    public static function permissionName(): string
    {
        return Permissions::RECEIVE_ORDER_NOTIFICATIONS;
    }

    private function summary(): string
    {
        return __('commero::app.order_notifications.summary', [
            'number' => $this->order->number,
            'customer' => $this->order->customer_name,
            'total' => number_format((float) $this->order->total_amount, 2, '.', ' '),
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
