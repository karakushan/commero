<?php

namespace Commero\Notifications;

use Commero\Interfaces\Filament\Resources\ProductReviewResource;
use Commero\Models\ProductReview;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Permissions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductReviewReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ProductReview $review) {}

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
        return (new MailMessage)
            ->subject(__('commero::app.order_notifications.review_subject'))
            ->view(config('commero.notifications.product_review_received_view'), [
                'title' => __('commero::app.order_notifications.review_title'),
                'intro' => __('commero::app.order_notifications.review_intro'),
                'review' => $this->review->loadMissing('product.translations'),
                'productName' => $this->productName(),
                'adminUrl' => ProductReviewResource::getUrl('view', ['record' => $this->review]),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('commero::app.order_notifications.review_title'),
            'body' => $this->summary(),
            'review_id' => $this->review->id,
            'product_id' => $this->review->product_id,
            'url' => ProductReviewResource::getUrl('view', ['record' => $this->review]),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title(__('commero::app.order_notifications.review_title'))
            ->body($this->summary())
            ->actions([
                Action::make('view')
                    ->label(__('commero::app.order_notifications.open_review'))
                    ->url(ProductReviewResource::getUrl('view', ['record' => $this->review])),
            ]);
    }

    public static function permissionName(): string
    {
        return Permissions::RECEIVE_PRODUCT_REVIEW_NOTIFICATIONS;
    }

    private function productName(): string
    {
        $product = $this->review->product;
        $locale = $this->review->locale ?: config('commero.locales.default', config('app.locale'));

        return $product?->translation($locale)?->name
            ?? $product?->sku
            ?? '-';
    }

    private function summary(): string
    {
        return __('commero::app.order_notifications.review_summary', [
            'product' => $this->productName(),
            'author' => $this->review->display_name,
            'rating' => $this->review->rating,
        ]);
    }
}
