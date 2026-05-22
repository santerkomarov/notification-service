<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'notification_user'),
    'password' => env('RABBITMQ_PASSWORD', 'notification_pass'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'queue_high' => env('RABBITMQ_QUEUE_HIGH', 'notifications.high'),
    'queue_low' => env('RABBITMQ_QUEUE_LOW', 'notifications.low'),

    'max_attempts' => env('NOTIFICATION_MAX_ATTEMPTS', 3),
];
