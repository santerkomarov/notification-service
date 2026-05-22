<?php

namespace App\Console\Commands;

use App\Services\RabbitMq\RabbitMqNotificationConsumer;
use Illuminate\Console\Command;

/**
 * docker-compose.yml worker запускает:
 * command: php artisan notifications:consume --sleep=1
 */
class ConsumeNotificationsCommand extends Command
{
    protected $signature = 'notifications:consume {--once : Consume only one message} {--sleep=1 : Seconds to sleep when queues are empty}';

    protected $description = 'Consume notification messages from RabbitMQ';

    public function handle(RabbitMqNotificationConsumer $consumer): int
    {
        while (true) {
            $processed = $consumer->consumeNext();

            if ($this->option('once')) {
                return self::SUCCESS;
            }

            if (!$processed) {
                sleep((int) $this->option('sleep'));
            }
        }
    }
}
