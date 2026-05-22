<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationMethod;
use App\Enums\NotificationPriority;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'idempotency_key',
        'method',
        'priority',
        'message',
        'total_count',
    ];

    protected $casts = [
        'method' => NotificationMethod::class,
        'priority' => NotificationPriority::class,
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }

    public function statusCounts(): array
    {
        $counts = $this->notifications()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            NotificationStatus::Queued->value => (int) ($counts[NotificationStatus::Queued->value] ?? 0),
            NotificationStatus::Sent->value => (int) ($counts[NotificationStatus::Sent->value] ?? 0),
            NotificationStatus::Delivered->value => (int) ($counts[NotificationStatus::Delivered->value] ?? 0),
            NotificationStatus::Dropped->value => (int) ($counts[NotificationStatus::Dropped->value] ?? 0),
        ];
    }

    public static function findByIdempotencyKey(?string $idempotencyKey): ?self
    {
        if (empty($idempotencyKey)) {
            return null;
        }

        return self::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public static function createFromBulkRequest(array $data, ?string $idempotencyKey): self
    {
        return self::query()->create([
            'idempotency_key' => $idempotencyKey,
            'method' => $data['method'],
            'priority' => $data['priority'],
            'message' => $data['message'],
            'total_count' => count($data['recipient_ids']),
        ]);
    }
}
