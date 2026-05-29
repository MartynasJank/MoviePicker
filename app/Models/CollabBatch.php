<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollabBatch extends Model
{
    protected $primaryKey = 'token';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['token', 'movies', 'media_type', 'created_by', 'expires_at'];

    protected $casts = [
        'movies'     => 'array',
        'expires_at' => 'datetime',
    ];
}
