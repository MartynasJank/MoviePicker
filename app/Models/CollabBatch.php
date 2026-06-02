<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollabBatch extends Model
{
    protected $primaryKey  = 'token';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'token','movies','media_type','created_by','expires_at',
        'votes','restore_votes','graveyard','ready','refresh_votes','try_again_votes','participants','criteria',
    ];

    protected $casts = [
        'movies'        => 'array',
        'votes'         => 'array',
        'restore_votes' => 'array',
        'graveyard'     => 'array',
        'ready'         => 'array',
        'refresh_votes'   => 'array',
        'try_again_votes' => 'array',
        'participants'  => 'array',
        'criteria'      => 'array',
        'expires_at'    => 'datetime',
    ];
}
