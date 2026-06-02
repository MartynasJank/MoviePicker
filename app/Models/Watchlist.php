<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    protected $table = 'watchlist';

    protected $fillable = [
        'user_id',
        'tmdb_id',
        'title',
        'poster_path',
        'year',
        'genres',
        'vote_average',
        'type',
        'status',
        'source',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
