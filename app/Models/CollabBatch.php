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
        'votes','restore_votes','graveyard','ready','refresh_votes','participants','criteria',
    ];

    protected $casts = [
        'movies'        => 'array',
        'votes'         => 'array',
        'restore_votes' => 'array',
        'graveyard'     => 'array',
        'ready'         => 'array',
        'refresh_votes' => 'array',
        'participants'  => 'array',
        'criteria'      => 'array',
        'expires_at'    => 'datetime',
    ];

    public function getVotesAttribute($value): array         { return $value ? json_decode($value, true) : []; }
    public function getRestoreVotesAttribute($value): array  { return $value ? json_decode($value, true) : []; }
    public function getGraveyardAttribute($value): array     { return $value ? json_decode($value, true) : []; }
    public function getReadyAttribute($value): array         { return $value ? json_decode($value, true) : []; }
    public function getRefreshVotesAttribute($value): array  { return $value ? json_decode($value, true) : []; }
    public function getParticipantsAttribute($value): array  { return $value ? json_decode($value, true) : []; }
}
