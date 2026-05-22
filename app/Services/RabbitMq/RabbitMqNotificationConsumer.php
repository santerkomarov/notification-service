<?php

namespace App\Services\RabbitMq;

use App\Services\Notifications\NotificationSender;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Throwable;

class RabbitMqNotificationConsumer
{
    public function __construct(
        private readonly RabbitMqConnectionFactory $connectionFactory,
        private readonly RabbitMqNotificationPublisher $publisher,
        private readonly NotificationSender $sender
    ) {
    }

    /**
     * Обработать сообщение из очереди
     */
    public function consumeNext(): bool
    {
        $connection = $this->connectionFactory->make();
        $channel = $connection->channel();

        try {
            $this->publisher->declareQueues($channel);

            foreach ($this->queueNamesByPriority() as $queueName) {
                $message = $channel->basic_get($queueName);

                if ($message instanceof AMQPMessage) {
                    return $this->processMessage($message, $channel);
                }
            }

            return false;
        } finally {
            $this->closeConnection($channel, $connection);
        }
    }

    private function processMessage(AMQPMessage $message, AMQPChannel $channel): bool
    {
        try {
            $notificationId = $this->extractNotificationId($message);

            if ($notificationId === null) {
                $this->acknowledge($message, $channel);
                return true;
            }

            $outcome = $this->sender->send($notificationId);

            if ($outcome === NotificationSender::RETRY) {
                $this->reject($message, $channel, requeue: true);
            } else {
                $this->acknowledge($message, $channel);
            }

            return true;
        } catch (Throwable $e) {
            report($e);
            $this->reject($message, $channel, requeue: true);
            return true;
        }
    }

    private function extractNotificationId(AMQPMessage $message): ?string
    {
        try {
            $payload = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);
            $notificationId = $payload['notification_id'] ?? null;

            return is_string($notificationId) && $notificationId !== '' ? $notificationId : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function acknowledge(AMQPMessage $message, AMQPChannel $channel): void
    {
        $channel->basic_ack($message->getDeliveryTag());
    }

     private function reject(AMQPMessage $message, AMQPChannel $channel, bool $requeue = false): void
    {
        $channel->basic_nack(delivery_tag: $message->getDeliveryTag(),requeue: $requeue);
    }

    private function closeConnection(AMQPChannel $channel, AbstractConnection $connection): void
    {
        try {
            $channel->close();
            $connection->close();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function queueNamesByPriority(): array
    {
        return [
            config('rabbitmq.queue_high'),
            config('rabbitmq.queue_low'),
        ];
    }
}
