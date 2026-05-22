<?php


namespace App\Services\Notifications;

use App\Enums\NotificationStatus;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use App\Services\Notifications\Providers\NotificationProviderFactory;
use App\Services\Notifications\Providers\ProviderException;
use Illuminate\Support\Facades\DB;

class NotificationSender
{
    public const SENT = 'sent';
    public const DROPPED = 'dropped';
    public const RETRY = 'retry';     // Временная ошибка, возвращается в очередь
    public const SKIPPED = 'skipped'; // Нельзя отправить, удаляется из очереди

    public function __construct(private readonly NotificationProviderFactory $providerFactory)
    {
    }

    public function send(string $notificationId): string
    {
        return DB::transaction(function () use ($notificationId) {
            $notification = Notification::findForUpdate($notificationId);

            if ($notification === null || $notification->status !== NotificationStatus::Queued) {
                return self::SKIPPED;
            }

            $attemptNumber = $notification->incrementAttempts();

            $providerName = $notification->method->value;
            $provider = $this->providerFactory->for($notification->method);

            try {
                $providerMessageId = $provider->send($notification);

                $this->createDeliveryAttempt(
                    notification: $notification,
                    attemptNumber: $attemptNumber,
                    providerName: $providerName,
                    status: NotificationStatus::Delivered->value
                );

                $notification->markAsDelivered($providerMessageId);

                return self::SENT;
            } catch (ProviderException $exception) {
                $this->createDeliveryAttempt(
                    notification: $notification,
                    attemptNumber: $attemptNumber,
                    providerName: $providerName,
                    status: $exception->isTemporary()
                        ? 'temporary_failed'
                        : NotificationStatus::Dropped->value,
                    errorMessage: $exception->getMessage()
                );

                if ($exception->isTemporary() && $attemptNumber < (int)config('rabbitmq.max_attempts')) {
                    $notification->updateErrorMessage($exception->getMessage());

                    return self::RETRY;
                }

                $notification->markAsDropped($exception->getMessage());
                return self::DROPPED;
            }
        });
    }

    private function createDeliveryAttempt(
        Notification $notification,
        int          $attemptNumber,
        string       $providerName,
        string       $status,
        ?string      $errorMessage = null
    ): void
    {
        DeliveryAttempt::query()->create([
            'notification_id' => $notification->id,
            'attempt_number' => $attemptNumber,
            'provider' => $providerName,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
