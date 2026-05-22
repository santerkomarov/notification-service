<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    protected $fillable = [
        'email',
        'phone',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function latestNotifications(int $perPage)
    {
        return $this->notifications()
            ->latest()
            ->paginate($perPage);
    }
}
