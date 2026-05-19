<?php

namespace App\Services;

class RouletteTagMapper
{
    private const PLATFORM_IDS = [
        'netflix' => 8,
        'prime'   => 119,
        'hbo'     => 1899,
        'disney'  => 337,
        'apple'   => 350,
    ];

    private const GENRE_IDS = [
        'action'      => 28,
        'adventure'   => 12,
        'animation'   => 16,
        'comedy'      => 35,
        'crime'       => 80,
        'documentary' => 99,
        'drama'       => 18,
        'family'      => 10751,
        'fantasy'     => 14,
        'history'     => 36,
        'horror'      => 27,
        'mystery'     => 9648,
        'romance'     => 10749,
        'sci-fi'      => 878,
        'thriller'    => 53,
        'war'         => 10752,
        'western'     => 37,
    ];

    private const ERA_RANGES = [
        'pre-1950' => ['lte' => 1949],
        '1950s'    => ['gte' => 1950, 'lte' => 1959],
        '1960s'    => ['gte' => 1960, 'lte' => 1969],
        '1970s'    => ['gte' => 1970, 'lte' => 1979],
        '1980s'    => ['gte' => 1980, 'lte' => 1989],
        '1990s'    => ['gte' => 1990, 'lte' => 1999],
        '2000s'    => ['gte' => 2000, 'lte' => 2009],
        '2010s'    => ['gte' => 2010, 'lte' => 2019],
        '2020s'    => ['gte' => 2020],
    ];

    public function toCriteria(array $tags): array
    {
        $criteria = [];

        if (!empty($tags['platform'])) {
            $ids = array_values(array_filter(
                array_map(fn($p) => self::PLATFORM_IDS[$p] ?? null, (array) $tags['platform'])
            ));
            if ($ids) {
                $criteria['with_watch_providers'] = $ids;
            }
        }

        if (!empty($tags['genre'])) {
            $ids = array_values(array_filter(
                array_map(fn($g) => self::GENRE_IDS[$g] ?? null, (array) $tags['genre'])
            ));
            if ($ids) {
                $criteria['with_genres'] = $ids;
            }
        }

        if (!empty($tags['language'])) {
            $criteria['with_original_language'] = $tags['language'][0];
        }

        if (!empty($tags['era'])) {
            $era   = (array) $tags['era'];
            $range = $era[0] === 'recent'
                ? ['gte' => (int) date('Y') - 2]
                : (self::ERA_RANGES[$era[0]] ?? null);

            if ($range) {
                if (isset($range['gte'])) {
                    $criteria['primary_release_date.gte'] = $range['gte'];
                }
                if (isset($range['lte'])) {
                    $criteria['primary_release_date.lte'] = $range['lte'];
                }
            }
        }

        return $criteria;
    }
}