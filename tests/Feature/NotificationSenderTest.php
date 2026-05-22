<?php

namespace Tests\Feature;

use App\Enums\NotificationMethod;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use App\Services\Notifications\NotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_email_gets_delivered(): void
    {
        $subscriber = Subscriber::query()->create([
            'email' => 'user1@example.com',
            'phone' => '+79519876542',
        ]);

        $notification = $this->createNotification($subscriber);

        $outcome = app(NotificationSender::class)->send($notification->id);

        $notification->refresh();

        $this->assertSame(NotificationSender::SENT, $outcome);
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->provider_message_id);
        $this->assertNotNull($notification->sent_at);
        $this->assertNotNull($notification->delivered_at);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'provider' => 'email',
            'status' => 'delivered',
            'error_message' => null,
        ]);
    }

    public function test_invalid_email_gets_dropped(): void
    {
        $subscriber = Subscriber::query()->create([
            'email' => 'fake@@fake.com',
            'phone' => '+79519876544',
        ]);

        $notification = $this->createNotification($subscriber);

        $outcome = app(NotificationSender::class)->send($notification->id);

        $notification->refresh();

        $this->assertSame(NotificationSender::DROPPED, $outcome);
        $this->assertSame(NotificationStatus::Dropped, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame('Не корректный email.', $notification->error_message);
        $this->assertNotNull($notification->dropped_at);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'provider' => 'email',
            'status' => 'dropped',
            'error_message' => 'Не корректный email.',
        ]);
    }

    public function test_temporary_failure_retries_and_succeeds(): void
    {
        $subscriber = Subscriber::query()->create([
            'email' => 'user1@example.com',
            'phone' => '+79519876542',
        ]);

        $notification = $this->createNotification(
            subscriber: $subscriber,
            message: 'temporary_fail: Ваш заказ передан в доставку.'
        );

        $firstOutcome = app(NotificationSender::class)->send($notification->id);

        $notification->refresh();

        $this->assertSame(NotificationSender::RETRY, $firstOutcome);
        $this->assertSame(NotificationStatus::Queued, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame(
            'Email-провайдер временно недоступен.',
            $notification->error_message
        );

        $secondOutcome = app(NotificationSender::class)->send($notification->id);

        $notification->refresh();

        $this->assertSame(NotificationSender::SENT, $secondOutcome);
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(2, $notification->attempts);
        $this->assertNotNull($notification->delivered_at);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id' => $notification->id,
            'attempt_number' => 1,
            'provider' => 'email',
            'status' => 'temporary_failed',
            'error_message' => 'Email-провайдер временно недоступен.',
        ]);

        $this->assertDatabaseHas('delivery_attempts', [
            'notification_id' => $notification->id,
            'attempt_number' => 2,
            'provider' => 'email',
            'status' => 'delivered',
            'error_message' => null,
        ]);
    }

    private function createNotification(Subscriber $subscriber,string $message = 'Ваш заказ передан в доставку.'): Notification
     {
        $batch = NotificationBatch::query()->create([
            'idempotency_key' => null,
            'method' => NotificationMethod::Email->value,
            'priority' => NotificationPriority::High->value,
            'message' => $message,
            'total_count' => 1,
        ]);

        return Notification::query()->create([
            'batch_id' => $batch->id,
            'subscriber_id' => $subscriber->id,
            'method' => NotificationMethod::Email->value,
            'priority' => NotificationPriority::High->value,
            'message' => $message,
            'status' => NotificationStatus::Queued->value,
            'attempts' => 0,
        ]);
    }
}
