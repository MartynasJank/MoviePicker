<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'visitor_hash',
        'user_id',
        'bot',
        'route',
        'referrer',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
