<?php

namespace App\Enums;

enum NotificationMethod: string
{
    case Email = 'email';
    case Sms = 'sms';
}
