<?php

namespace Commero\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductBackInStockMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $productName,
        public readonly string $productUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Back in stock: :product', ['product' => $this->productName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-back-in-stock',
            with: [
                'productName' => $this->productName,
                'productUrl' => $this->productUrl,
            ],
        );
    }
}
