<?php


namespace App\Services\RabbitMq;

use App\Enums\NotificationPriority;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqNotificationPublisher
{
    public function __construct(private readonly RabbitMqConnectionFactory $connectionFactory)
    {
    }

    public function publish(string $notificationId, NotificationPriority $priority): void
    {
        $connection = $this->connectionFactory->make();
        $channel = $connection->channel();

        $this->declareQueues($channel);

        $queueName = $priority === NotificationPriority::High
            ? config('rabbitmq.queue_high')
            : config('rabbitmq.queue_low');

        $message = new AMQPMessage(
            json_encode([
                'notification_id' => $notificationId,
            ], JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $channel->basic_publish($message, '', $queueName);

        $channel->close();
        $connection->close();
    }

    public function declareQueues(AMQPChannel $channel): void
    {
        foreach ($this->queueNames() as $queueName) {
            $this->declareQueue($channel, $queueName);
        }
    }

    private function queueNames(): array
    {
        return [
            config('rabbitmq.queue_high'),
            config('rabbitmq.queue_low'),
        ];
    }

    private function declareQueue(AMQPChannel $channel, string $queueName): void
    {
        $channel->queue_declare(
            queue: $queueName,
            durable: true,
            auto_delete: false
        );
    }
}
