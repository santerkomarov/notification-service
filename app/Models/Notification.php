<?php

namespace App\Models;

use App\Enums\NotificationMethod;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'batch_id',
        'subscriber_id',
        'method',
        'priority',
        'message',
        'status',
        'provider_message_id',
        'error_message',
        'attempts',
        'sent_at',
        'delivered_at',
        'dropped_at',
    ];

    protected $casts = [
        'method'   => NotificationMethod::class,
        'priority' => NotificationPriority::class,
        'status'  => NotificationStatus::class,
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'dropped_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    public static function findForUpdate(string $id): ?self
    {
        return self::query()
            ->with('subscriber')
            ->lockForUpdate()
            ->find($id);
    }

    public function markAsDelivered(string $providerMessageId): void
    {
        $now = now();
        $this->update([
            'status' => NotificationStatus::Delivered,
            'provider_message_id' => $providerMessageId,
            'error_message' => null,
            'sent_at' => $now,
            'delivered_at' => $now,
        ]);
    }

    public function markAsDropped(string $errorMessage): void
    {
        $this->update([
            'status' => NotificationStatus::Dropped,
            'error_message' => $errorMessage,
            'dropped_at' => now(),
        ]);
    }

    public function updateErrorMessage(string $errorMessage): void
    {
        $this->update(['error_message' => $errorMessage]);
    }

    public function incrementAttempts(): int
    {
        $this->attempts++;
        $this->save();

        return $this->attempts;
    }
}
