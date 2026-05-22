<?php

namespace App\Services\Notifications\Providers;

use App\Models\Notification;

class EmailProviderMock implements NotificationProviderInterface
{
    public function send(Notification $notification): string
    {
        $email = $notification->subscriber?->email;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ProviderException('Не корректный email.');
        }

        if ($this->shouldFailTemporarily($notification)) {
            throw new ProviderException('Email-провайдер временно недоступен.', true);
        }

        return 'email-mock-' . $notification->id;
    }

    private function shouldFailTemporarily(Notification $notification): bool
    {
        return str_contains(strtolower($notification->message), 'temporary_fail')
            && $notification->attempts === 1;
    }
}
