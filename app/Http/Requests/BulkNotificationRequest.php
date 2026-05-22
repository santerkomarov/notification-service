<?php

namespace App\Http\Requests;

use App\Enums\NotificationMethod;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BulkNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', new Enum(NotificationMethod::class)],
            'priority' => ['required', new Enum(NotificationPriority::class)],
            'message' => ['required', 'string', 'max:1000'],

            'recipient_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'recipient_ids.*' => ['required', 'integer', 'distinct', 'exists:subscribers,id'],
        ];
    }
}
