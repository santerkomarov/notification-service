<?php

namespace Tests\Feature;

use App\Enums\NotificationMethod;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use App\Services\RabbitMq\RabbitMqConnectionFactory;
use App\Services\RabbitMq\RabbitMqNotificationConsumer;
use App\Services\RabbitMq\RabbitMqNotificationPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RabbitMqNotificationConsumerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'rabbitmq.queue_high' => 'test.notifications.high',
            'rabbitmq.queue_low' => 'test.notifications.low',
        ]);

        $this->clearQueues();
    }

    protected function tearDown(): void
    {
        $this->deleteTestQueues();

        parent::tearDown();
    }

    public function test_worker_handles_message(): void
    {
        $subscriber = Subscriber::query()->create([
            'email' => 'user1@example.com',
            'phone' => '+79519876542',
        ]);

        $notification = $this->createNotification($subscriber);

        app(RabbitMqNotificationPublisher::class)->publish(
            notificationId: $notification->id,
            priority: NotificationPriority::High
        );

        $processed = app(RabbitMqNotificationConsumer::class)->consumeNext();

        $notification->refresh();

        $this->assertTrue($processed);
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->provider_message_id);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'provider' => 'email',
            'status' => 'delivered',
        ]);
    }

    public function test_high_priority_first(): void
    {
        $lowSubscriber = Subscriber::query()->create([
            'email' => 'low@example.com',
            'phone' => '+79519876542',
        ]);

        $highSubscriber = Subscriber::query()->create([
            'email' => 'high@example.com',
            'phone' => '+79519876543',
        ]);

        $lowNotification = $this->createNotification(
            subscriber: $lowSubscriber,
            priority: NotificationPriority::Low
        );

        $highNotification = $this->createNotification(
            subscriber: $highSubscriber,
            priority: NotificationPriority::High
        );

        app(RabbitMqNotificationPublisher::class)->publish(
            notificationId: $lowNotification->id,
            priority: NotificationPriority::Low
        );

        app(RabbitMqNotificationPublisher::class)->publish(
            notificationId: $highNotification->id,
            priority: NotificationPriority::High
        );

        app(RabbitMqNotificationConsumer::class)->consumeNext();

        $this->assertSame(
            NotificationStatus::Queued,
            $lowNotification->refresh()->status
        );

        $this->assertSame(
            NotificationStatus::Delivered,
            $highNotification->refresh()->status
        );
    }

    private function createNotification(
        Subscriber $subscriber,
        NotificationPriority $priority = NotificationPriority::High,
    ): Notification {
        $batch = NotificationBatch::query()->create([
            'idempotency_key' => null,
            'method' => NotificationMethod::Email->value,
            'priority' => $priority->value,
            'message' => 'Ваш заказ передан в доставку.',
            'total_count' => 1,
        ]);

        return Notification::query()->create([
            'batch_id' => $batch->id,
            'subscriber_id' => $subscriber->id,
            'method' => NotificationMethod::Email->value,
            'priority' => $priority->value,
            'message' => 'Ваш заказ передан в доставку.',
            'status' => NotificationStatus::Queued->value,
            'attempts' => 0,
        ]);
    }

    private function clearQueues(): void
    {
        $connection = app(RabbitMqConnectionFactory::class)->make();
        $channel = $connection->channel();

        app(RabbitMqNotificationPublisher::class)->declareQueues($channel);

        $channel->queue_purge(config('rabbitmq.queue_high'));
        $channel->queue_purge(config('rabbitmq.queue_low'));

        $channel->close();
        $connection->close();
    }

    private function deleteTestQueues(): void
    {
        $connection = app(RabbitMqConnectionFactory::class)->make();
        $channel = $connection->channel();

        $channel->queue_delete(config('rabbitmq.queue_high'));
        $channel->queue_delete(config('rabbitmq.queue_low'));

        $channel->close();
        $connection->close();
    }
}
