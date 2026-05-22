<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Services\RabbitMq\RabbitMqNotificationPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationBulkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_batch_and_notifications(): void
    {
        $this->createSubscribers();

        $this->mock(RabbitMqNotificationPublisher::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->times(3);
        });

        $response = $this
            ->withHeader('Idempotency-Key', 'test-key-api-001')
            ->postJson('/api/notifications/bulk', [
                'method' => 'email',
                'priority' => 'high',
                'message' => 'Ваш заказ передан в доставку.',
                'recipient_ids' => [1, 2, 3],
            ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('created', true)
            ->assertJsonPath('created_notifications', 3);

        $this->assertDatabaseCount('notification_batches', 1);
        $this->assertDatabaseCount('notifications', 3);

        $this->assertDatabaseHas('notification_batches', [
            'method' => 'email',
            'priority' => 'high',
            'message' => 'Ваш заказ передан в доставку.',
            'total_count' => 3,
        ]);

        $this->assertDatabaseHas('notifications', [
            'subscriber_id' => 1,
            'method' => 'email',
            'priority' => 'high',
            'status' => 'queued',
        ]);
    }

    public function test_duplicate(): void
    {
        $this->createSubscribers();
        $recipientIds = $this->createSubscribers();

        $this->mock(RabbitMqNotificationPublisher::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->times(3);
        });

        $payload = [
            'method' => 'email',
            'priority' => 'high',
            'message' => 'Ваш заказ передан в доставку.',
            'recipient_ids' => $recipientIds,
        ];

        $firstResponse = $this
            ->withHeader('Idempotency-Key', 'same-key-001')
            ->postJson('/api/notifications/bulk', $payload);

        $secondResponse = $this
            ->withHeader('Idempotency-Key', 'same-key-001')
            ->postJson('/api/notifications/bulk', $payload);

        $firstResponse
            ->assertAccepted()
            ->assertJsonPath('created', true)
            ->assertJsonPath('status', 'accepted');

        $secondResponse
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('status', 'already_accepted');

        $this->assertSame(
            $firstResponse->json('batch_id'),
            $secondResponse->json('batch_id')
        );

        $this->assertDatabaseCount('notification_batches', 1);
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_invalid_input_fails(): void
    {
        $response = $this->postJson('/api/notifications/bulk', [
            'method' => 'telegram',
            'priority' => 'middle',
            'message' => '',
            'recipient_ids' => [],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'method',
                'priority',
                'message',
                'recipient_ids',
            ]);
    }

    private function createSubscribers(): array
    {
        $subscriber1 = Subscriber::query()->create([
            'email' => 'user1@example.com',
            'phone' => '+79519876542',
        ]);

        $subscriber2 = Subscriber::query()->create([
            'email' => 'user2@example.com',
            'phone' => '+79519876543',
        ]);

        $subscriber3 = Subscriber::query()->create([
            'email' => 'fake@@fake.com',
            'phone' => '+79519876544',
        ]);

        return [
            $subscriber1->id,
            $subscriber2->id,
            $subscriber3->id,
        ];
    }
}
