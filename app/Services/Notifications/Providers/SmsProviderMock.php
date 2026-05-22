<?php

namespace App\Services\Notifications\Providers;

use App\Models\Notification;

class SmsProviderMock implements NotificationProviderInterface
{
    public function send(Notification $notification): string
    {
        $phone = $notification->subscriber?->phone;

        if ($phone === null || !preg_match('/^\+\d{10,15}$/', $phone)) {
            throw new ProviderException('Не корректный номер телефона.');
        }

        if ($this->shouldFailTemporarily($notification)) {
            throw new ProviderException('SMS-провайдер временно недоступен..', true);
        }

        return 'sms-mock-' . $notification->id;
    }

    private function shouldFailTemporarily(Notification $notification): bool
    {
        return str_contains(strtolower($notification->message), 'temporary_fail')
            && $notification->attempts === 1;
    }
}
