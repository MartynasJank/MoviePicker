<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmdbRequestLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'endpoint',
        'route',
        'cached',
        'status_code',
        'response_time_ms',
        'user_id',
        'visitor_hash',
    ];

    protected $casts = [
        'cached' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
