<?php

namespace App\Services\Notifications\Providers;

use App\Enums\NotificationMethod;

class NotificationProviderFactory
{
    public function for(NotificationMethod $method): NotificationProviderInterface
    {
        return match ($method) {
            NotificationMethod::Email => app(EmailProviderMock::class),
            NotificationMethod::Sms => app(SmsProviderMock::class),
        };
    }
}
