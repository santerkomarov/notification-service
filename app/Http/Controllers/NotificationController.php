<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkNotificationRequest;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use App\Services\Notifications\BulkNotificationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\BatchResource;

class NotificationController extends Controller
{
    public function createBulk(BulkNotificationRequest $request, BulkNotificationService $service): JsonResponse
    {
        $result = $service->createBatch(
            data: $request->validated(),
            idempotencyKey: $request->header('Idempotency-Key')
        );

        /** @var NotificationBatch $batch */
        $batch = $result['batch'];
        $created = (bool) $result['created'];

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $created ? 'accepted' : 'already_accepted',
            'created' => $created,
            'created_notifications' => $batch->total_count,
        ], $created ? Response::HTTP_ACCEPTED : Response::HTTP_OK);
    }

    public function subscriberNotifications(Subscriber $subscriber): JsonResponse
    {
        return response()->json([
            'subscriber_id' => $subscriber->id,
            'notifications' => $subscriber->latestNotifications(
                config('notification.pagination.per_page')
            ),
        ]);
    }
    public function batch(NotificationBatch $batch): BatchResource
    {
        return new BatchResource($batch);
    }
}
