<?php

namespace Commero\Notifications;

use Commero\Interfaces\Filament\Resources\MarketingLeadResource;
use Commero\Models\MarketingLead;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Permissions;
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
            ->subject(__('commero::app.order_notifications.new_lead_subject', ['type' => $this->leadTypeLabel()]))
            ->view(config('commero.notifications.marketing_lead_received_view'), [
                'title' => __('commero::app.order_notifications.new_lead_title'),
                'leadType' => $this->leadTypeLabel(),
                'lead' => $this->lead,
                'fields' => $this->fields(),
                'adminUrl' => MarketingLeadResource::getUrl('view', ['record' => $this->lead]),
            ]);

        if (filled($this->lead->email)) {
            $mail->replyTo($this->lead->email, $this->lead->name ?: null);
        }

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('commero::app.order_notifications.new_lead_title'),
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
            ->title(__('commero::app.order_notifications.new_lead_title'))
            ->body($this->leadSummary())
            ->actions([
                Action::make('view')
                    ->label(__('Open'))
                    ->url(MarketingLeadResource::getUrl('view', ['record' => $this->lead])),
            ]);
    }

    public static function permissionName(): string
    {
        return Permissions::RECEIVE_MARKETING_LEAD_NOTIFICATIONS;
    }

    /** @return array<string, string|null> */
    private function fields(): array
    {
        return [
            __('commero::admin.marketing_lead.subject') => $this->lead->subject,
            __('commero::admin.marketing_lead.name') => $this->lead->name,
            __('commero::admin.common.phone') => $this->lead->phone,
            __('commero::admin.common.email') => $this->lead->email,
            __('commero::admin.common.message') => $this->lead->message,
            __('commero::admin.marketing_lead.source_url') => $this->lead->source_url,
            __('commero::admin.common.created_at') => $this->lead->created_at?->format('d.m.Y H:i'),
        ];
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
