<?php

namespace Commero\Notifications;

use Commero\Interfaces\Filament\Resources\MarketingLeadResource;
use Commero\Models\MarketingLead;
use Commero\Support\Mail\OutboundMailStatus;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketingLeadReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly MarketingLead $lead,
    ) {}

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
        $mail = (new MailMessage)
            ->subject(__('New marketing lead: :type', ['type' => $this->leadTypeLabel()]))
            ->greeting(__('New marketing lead'))
            ->line(__('Type: :type', ['type' => $this->leadTypeLabel()]))
            ->line(__('Submitted at: :date', ['date' => $this->lead->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i')]));

        if (filled($this->lead->subject)) {
            $mail->line(__('Subject: :subject', ['subject' => $this->lead->subject]));
        }

        if (filled($this->lead->name)) {
            $mail->line(__('Name: :name', ['name' => $this->lead->name]));
        }

        if (filled($this->lead->phone)) {
            $mail->line(__('Phone: :phone', ['phone' => $this->lead->phone]));
        }

        if (filled($this->lead->email)) {
            $mail->line(__('E-mail: :email', ['email' => $this->lead->email]));
            $mail->replyTo($this->lead->email, $this->lead->name ?: null);
        }

        if (filled($this->lead->message)) {
            $mail->line(__('Message: :message', ['message' => $this->lead->message]));
        }

        if (filled($this->lead->source_url)) {
            $mail->line(__('Page URL: :url', ['url' => $this->lead->source_url]));
        }

        $mail->action(
            __('Open lead in admin'),
            MarketingLeadResource::getUrl('view', ['record' => $this->lead])
        );

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('New marketing lead'),
            'body' => $this->leadSummary(),
            'lead_id' => $this->lead->id,
            'type' => $this->lead->type,
            'url' => MarketingLeadResource::getUrl('view', ['record' => $this->lead]),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title(__('New marketing lead'))
            ->body($this->leadSummary())
            ->actions([
                Action::make('view')
                    ->label(__('Open'))
                    ->url(MarketingLeadResource::getUrl('view', ['record' => $this->lead])),
            ]);
    }

    private function leadTypeLabel(): string
    {
        return __('commero::admin.marketing_lead.types.'.$this->lead->type);
    }

    private function leadSummary(): string
    {
        $parts = array_filter([
            $this->leadTypeLabel(),
            $this->lead->subject,
            $this->lead->name,
            $this->lead->phone,
            $this->lead->email,
        ]);

        return implode(' | ', $parts);
    }
}
