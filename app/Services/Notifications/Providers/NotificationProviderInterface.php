<?php


namespace App\Services\Notifications\Providers;

use App\Models\Notification;

interface NotificationProviderInterface
{
    public function send(Notification $notification): string;
}
