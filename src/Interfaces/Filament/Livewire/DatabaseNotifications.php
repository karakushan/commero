<?php

namespace Commero\Interfaces\Filament\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        parent::removeNotification($id);
    }

    #[On('markedNotificationAsRead')]
    public function markNotificationAsRead(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        parent::markNotificationAsRead($id);
    }

    #[On('markedNotificationAsUnread')]
    public function markNotificationAsUnread(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        parent::markNotificationAsUnread($id);
    }
}
