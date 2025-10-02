<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_id',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->last_activity && $this->last_activity->addHours(24)->isPast()) {
            return false;
        }

        return true;
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->where(function ($q) {
            $q->whereNull('last_activity')
              ->orWhere('last_activity', '>', now()->subHours(24));
        });
    }
}