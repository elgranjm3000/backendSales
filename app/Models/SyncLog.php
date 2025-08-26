<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'entity_type', 'action', 'data', 'synced_at'
    ];

    protected $casts = [
        'data' => 'array',
        'synced_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}