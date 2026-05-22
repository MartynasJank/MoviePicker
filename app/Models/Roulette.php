<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Roulette extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'tags', 'tag_fingerprint',
        'featured_movie_ids', 'poster_paths', 'is_system', 'user_id', 'is_public', 'sort_order', 'row', 'media_type',
    ];

    protected $casts = [
        'tags'               => 'array',
        'featured_movie_ids' => 'array',
        'poster_paths'       => 'array',
        'is_system'          => 'boolean',
        'is_public'          => 'boolean',
    ];

    public function groupName(): string
    {
        if (!empty($this->row)) return $this->row;

        $tags = $this->tags ?? [];
        $platforms = ['netflix' => 'Netflix', 'prime' => 'Prime Video', 'hbo' => 'HBO', 'disney' => 'Disney+', 'apple' => 'Apple TV+'];

        if (!empty($tags['platform'])) {
            return $platforms[$tags['platform'][0]] ?? 'Other';
        }
        if (!empty($tags['era'])) return 'By Decade';
        if (!empty($tags['language']) && in_array('ja', (array) $tags['language'])
            && !empty($tags['genre']) && in_array('animation', (array) $tags['genre'])) {
            return 'Anime';
        }
        if (!empty($tags['language'])) return 'World Cinema';
        return 'By Genre';
    }

    public static function generateSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while (
            static::where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

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