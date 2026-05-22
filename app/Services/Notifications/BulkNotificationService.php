<?php

namespace App\Services\Notifications;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Services\RabbitMq\RabbitMqNotificationPublisher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkNotificationService
{
    public function __construct(private readonly RabbitMqNotificationPublisher $publisher)
    {
    }

    /**
     * Создать пакетную рассылку
     */
    public function createBatch(array $data, ?string $idempotencyKey): array
    {
        $result = DB::transaction(function () use ($data, $idempotencyKey) {
            $existingBatch = NotificationBatch::findByIdempotencyKey($idempotencyKey);

            if ($existingBatch) {
                return $this->existingBatchResult($existingBatch);
            }

            $batch = NotificationBatch::createFromBulkRequest($data, $idempotencyKey);
            $notificationIds = $this->createNotifications($batch, $data);

            return $this->newBatchResult($batch, $notificationIds);
        });

        if ($result['created']) {
            $this->publishToQueue($result['notification_ids'], $data['priority']);
        }

        return $result;
    }

    private function createNotifications(NotificationBatch $batch, array $data): array
    {
        $now = now();
        $notificationIds = [];
        $rows = [];

        foreach ($data['recipient_ids'] as $recipientId) {
            $notificationId = (string) Str::uuid();
            $notificationIds[] = $notificationId;

            $rows[] = [
                'id' => $notificationId,
                'batch_id' => $batch->id,
                'subscriber_id' => $recipientId,
                'method' => $data['method'],
                'priority' => $data['priority'],
                'message' => $data['message'],
                'status' => NotificationStatus::Queued->value,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Notification::query()->insert($rows);

        return $notificationIds;
    }

    private function publishToQueue(array $notificationIds, string $priority): void
    {
        $priorityEnum = NotificationPriority::from($priority);

        foreach ($notificationIds as $notificationId) {
            $this->publisher->publish($notificationId, $priorityEnum);
        }
    }

    private function newBatchResult(NotificationBatch $batch, array $notificationIds): array
    {
        return [
            'batch' => $batch,
            'created' => true,
            'notification_ids' => $notificationIds,
        ];
    }

    private function existingBatchResult(NotificationBatch $batch): array
    {
        return [
            'batch' => $batch,
            'created' => false,
            'notification_ids' => [],
        ];
    }
}
