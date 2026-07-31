<?php

namespace Commero\Services;

use Commero\Models\ProductReview;
use Commero\Models\User;
use Commero\Notifications\ProductReviewReceivedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReviewNotificationService
{
    public function notifyAboutNewReview(ProductReview $review): void
    {
        $recipients = User::query()
            ->permission(ProductReviewReceivedNotification::permissionName())
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::locale(config('commero.locales.default', config('app.locale')))
                ->send($recipients, new ProductReviewReceivedNotification($review->loadMissing('product.translations')));
        } catch (Throwable $exception) {
            Log::error('Product review notification failed.', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
