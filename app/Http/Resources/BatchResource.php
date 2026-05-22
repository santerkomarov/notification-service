<?php

namespace App\Http\Resources;

use App\Models\NotificationBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var NotificationBatch $batch */
        $batch = $this->resource;

        return [
            'batch_id' => $batch->id,
            'method' => $batch->method->value,
            'priority' => $batch->priority->value,
            'message' => $batch->message,
            'total_count' => $batch->total_count,
            'statuses' => $batch->statusCounts(),
            'created_at' => $batch->created_at,
        ];
    }
}
