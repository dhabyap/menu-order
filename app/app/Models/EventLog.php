<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'event_logs';

    protected $fillable = [
        'event',
        'level',
        'status',
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'message',
        'context',
        'request_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
