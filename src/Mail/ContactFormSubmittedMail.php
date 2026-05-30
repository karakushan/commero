<?php

namespace Commero\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmittedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $messageText,
        public readonly string $pageUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Contact request from :name', ['name' => $this->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-submitted',
            with: [
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'messageText' => $this->messageText,
                'pageUrl' => $this->pageUrl,
            ],
        );
    }
}
