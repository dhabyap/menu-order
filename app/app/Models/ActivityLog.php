<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_email',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function scopeLatest($query, int $limit = 50)
    {
        return $query->latest()->limit($limit);
    }
}
