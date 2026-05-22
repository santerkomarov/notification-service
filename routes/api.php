<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/notifications/bulk', [NotificationController::class, 'createBulk']);

Route::get('/subscribers/{subscriber}/notifications', [
    NotificationController::class,
    'subscriberNotifications',
]);

Route::get('/notification-batches/{batch}', [
    NotificationController::class,
    'batch',
]);
