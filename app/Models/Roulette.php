<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roulette extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'tags', 'tag_fingerprint',
        'featured_movie_ids', 'poster_paths', 'is_system', 'user_id', 'is_public',
    ];

    protected $casts = [
        'tags'               => 'array',
        'featured_movie_ids' => 'array',
        'poster_paths'       => 'array',
        'is_system'          => 'boolean',
        'is_public'          => 'boolean',
    ];

    public static function fingerprintFromTags(array $tags): string
    {
        $pairs = [];
        foreach ($tags as $type => $values) {
            foreach ((array) $values as $value) {
                $pairs[] = $type . ':' . $value;
            }
        }
        sort($pairs);
        return implode('|', $pairs);
    }
}