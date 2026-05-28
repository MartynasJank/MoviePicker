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

    private const TV_GENRE_IDS = [
        'action'      => 10759,  // Action & Adventure
        'adventure'   => 10759,  // Action & Adventure (same as action for TV)
        'animation'   => 16,
        'comedy'      => 35,
        'crime'       => 80,
        'documentary' => 99,
        'drama'       => 18,
        'family'      => 10751,
        'fantasy'     => 10765,  // Sci-Fi & Fantasy
        'history'     => 36,
        'horror'      => 27,
        'kids'        => 10762,
        'mystery'     => 9648,
        'reality'     => 10764,
        'romance'     => 10749,
        'sci-fi'      => 10765,  // Sci-Fi & Fantasy (same as fantasy for TV)
        'thriller'    => 80,     // Crime is the closest native TV genre for thrillers
        'war'         => 10768,  // War & Politics
        'western'     => 37,
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

    public function normalizeTags(array $raw): array
    {
        $tags = [];
        if (!empty($raw['platform']))      $tags['platform']      = [$raw['platform']];
        if (!empty($raw['genre']))         $tags['genre']         = array_values((array) $raw['genre']);
        if (!empty($raw['without_genre'])) $tags['without_genre'] = array_values((array) $raw['without_genre']);
        if (!empty($raw['era']))           $tags['era']           = [$raw['era']];
        if (!empty($raw['country']))       $tags['country']       = [$raw['country']];
        if (!empty($raw['language']))      $tags['language']      = [$raw['language']];
        if (isset($raw['year_from']) && $raw['year_from'] !== '') $tags['year_from'] = (int) $raw['year_from'];
        if (isset($raw['year_to'])   && $raw['year_to']   !== '') $tags['year_to']   = (int) $raw['year_to'];
        if (!empty($raw['with_cast']))  $tags['with_cast']  = array_values(array_map('strval', (array) $raw['with_cast']));
        if (!empty($raw['with_crew']))  $tags['with_crew']  = array_values(array_map('strval', (array) $raw['with_crew']));
        if (isset($raw['vote_average_gte']) && $raw['vote_average_gte'] !== '') $tags['vote_average_gte'] = (float) $raw['vote_average_gte'];
        if (isset($raw['vote_average_lte']) && $raw['vote_average_lte'] !== '') $tags['vote_average_lte'] = (float) $raw['vote_average_lte'];
        if (isset($raw['vote_count_gte'])   && $raw['vote_count_gte']   !== '') $tags['vote_count_gte']   = (int)   $raw['vote_count_gte'];
        return $tags;
    }

    public function toCriteriaMovie(array $tags): array
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

        if (!empty($tags['without_genre'])) {
            $ids = array_values(array_filter(
                array_map(fn($g) => self::GENRE_IDS[$g] ?? null, (array) $tags['without_genre'])
            ));
            if ($ids) {
                $criteria['without_genres'] = $ids;
            }
        }

        if (!empty($tags['country'])) {
            $criteria['with_origin_country'] = $tags['country'][0];
        }
        if (!empty($tags['language'])) {
            $criteria['with_original_language'] = $tags['language'][0];
        }

        // year_from/year_to take priority; fall back to legacy era slugs
        if (!empty($tags['year_from']) || !empty($tags['year_to'])) {
            if (!empty($tags['year_from'])) {
                $criteria['primary_release_date.gte'] = (int) $tags['year_from'];
            }
            if (!empty($tags['year_to'])) {
                $criteria['primary_release_date.lte'] = (int) $tags['year_to'];
            }
        } elseif (!empty($tags['era'])) {
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

        if (!empty($tags['with_cast']))        $criteria['with_cast']        = (array) $tags['with_cast'];
        if (!empty($tags['with_crew']))        $criteria['with_crew']        = (array) $tags['with_crew'];
        if (isset($tags['vote_average_gte']) && $tags['vote_average_gte'] !== '') $criteria['vote_average.gte'] = $tags['vote_average_gte'];
        if (isset($tags['vote_average_lte']) && $tags['vote_average_lte'] !== '') $criteria['vote_average.lte'] = $tags['vote_average_lte'];
        if (isset($tags['vote_count_gte'])   && $tags['vote_count_gte']   !== '') $criteria['vote_count.gte']   = $tags['vote_count_gte'];

        return $criteria;
    }

    public function toCriteriaTv(array $tags): array
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
            $ids = array_values(array_unique(array_filter(
                array_map(fn($g) => self::TV_GENRE_IDS[$g] ?? null, (array) $tags['genre'])
            )));
            if ($ids) {
                $criteria['with_genres'] = $ids;
            }
        }

        if (!empty($tags['without_genre'])) {
            $ids = array_values(array_unique(array_filter(
                array_map(fn($g) => self::TV_GENRE_IDS[$g] ?? null, (array) $tags['without_genre'])
            )));
            if ($ids) {
                $criteria['without_genres'] = $ids;
            }
        }

        if (!empty($tags['country'])) {
            $criteria['with_origin_country'] = $tags['country'][0];
        } elseif (!empty($tags['language'])) {
            $legacyMap = ['ja'=>'JP','ko'=>'KR','fr'=>'FR','es'=>'ES','it'=>'IT','zh'=>'CN','hi'=>'IN','de'=>'DE','tr'=>'TR','pt'=>'PT','lt'=>'LT'];
            $code = $legacyMap[$tags['language'][0]] ?? null;
            if ($code) $criteria['with_origin_country'] = $code;
        }

        // year_from/year_to take priority; fall back to legacy era slugs
        if (!empty($tags['year_from']) || !empty($tags['year_to'])) {
            if (!empty($tags['year_from'])) {
                $criteria['first_air_date.gte'] = (int) $tags['year_from'];
            }
            if (!empty($tags['year_to'])) {
                $criteria['first_air_date.lte'] = (int) $tags['year_to'];
            }
        } elseif (!empty($tags['era'])) {
            $era   = (array) $tags['era'];
            $range = $era[0] === 'recent'
                ? ['gte' => (int) date('Y') - 2]
                : (self::ERA_RANGES[$era[0]] ?? null);

            if ($range) {
                if (isset($range['gte'])) {
                    $criteria['first_air_date.gte'] = $range['gte'];
                }
                if (isset($range['lte'])) {
                    $criteria['first_air_date.lte'] = $range['lte'];
                }
            }
        }

        if (!empty($tags['with_cast']))        $criteria['with_cast']        = (array) $tags['with_cast'];
        if (!empty($tags['with_crew']))        $criteria['with_crew']        = (array) $tags['with_crew'];
        if (isset($tags['vote_average_gte']) && $tags['vote_average_gte'] !== '') $criteria['vote_average.gte'] = $tags['vote_average_gte'];
        if (isset($tags['vote_average_lte']) && $tags['vote_average_lte'] !== '') $criteria['vote_average.lte'] = $tags['vote_average_lte'];
        if (isset($tags['vote_count_gte'])   && $tags['vote_count_gte']   !== '') $criteria['vote_count.gte']   = $tags['vote_count_gte'];

        return $criteria;
    }

    /**
     * Reverse-map session criteria (userInput / tvInput) back to roulette tag format.
     * Used by UserRouletteController::fromCriteria() when saving a batch's criteria.
     */
    public function criteriaToTags(array $criteria, string $mediaType = 'movie'): array
    {
        $tags = [];

        // Platform — take first recognised provider ID
        if (!empty($criteria['with_watch_providers'])) {
            $flipped = array_flip(self::PLATFORM_IDS);
            foreach ((array) $criteria['with_watch_providers'] as $id) {
                if ($slug = ($flipped[(int) $id] ?? null)) {
                    $tags['platform'] = [$slug];
                    break;
                }
            }
        }

        // Genre include / exclude
        $genreMap     = $mediaType === 'tv' ? self::TV_GENRE_IDS : self::GENRE_IDS;
        $flippedGenre = array_flip($genreMap);

        if (!empty($criteria['with_genres'])) {
            $slugs = [];
            foreach ((array) $criteria['with_genres'] as $id) {
                if ($slug = ($flippedGenre[(int) $id] ?? null)) $slugs[] = $slug;
            }
            if ($slugs) $tags['genre'] = array_values(array_unique($slugs));
        }

        if (!empty($criteria['without_genres'])) {
            $slugs = [];
            foreach ((array) $criteria['without_genres'] as $id) {
                if ($slug = ($flippedGenre[(int) $id] ?? null)) $slugs[] = $slug;
            }
            if ($slugs) $tags['without_genre'] = array_values(array_unique($slugs));
        }

        // Language / Country
        if (!empty($criteria['with_original_language'])) {
            $tags['language'] = [$criteria['with_original_language']];
        }
        if (!empty($criteria['with_origin_country'])) {
            $tags['country'] = [$criteria['with_origin_country']];
        }

        // Year range (session keys use underscore notation)
        $gteKey = $mediaType === 'tv' ? 'first_air_date_gte' : 'primary_release_date_gte';
        $lteKey = $mediaType === 'tv' ? 'first_air_date_lte' : 'primary_release_date_lte';
        if (!empty($criteria[$gteKey])) $tags['year_from'] = (int) $criteria[$gteKey];
        if (!empty($criteria[$lteKey])) $tags['year_to']   = (int) $criteria[$lteKey];

        // Score / quality
        if (isset($criteria['vote_average_gte']) && $criteria['vote_average_gte'] !== '') {
            $tags['vote_average_gte'] = (float) $criteria['vote_average_gte'];
        }
        if (isset($criteria['vote_average_lte']) && $criteria['vote_average_lte'] !== '') {
            $tags['vote_average_lte'] = (float) $criteria['vote_average_lte'];
        }
        if (isset($criteria['vote_count_gte']) && $criteria['vote_count_gte'] !== '') {
            $tags['vote_count_gte'] = (int) $criteria['vote_count_gte'];
        }

        // People
        if (!empty($criteria['with_cast'])) {
            $cast = is_array($criteria['with_cast'])
                ? $criteria['with_cast']
                : explode(',', (string) $criteria['with_cast']);
            $cast = array_values(array_map('strval', array_filter($cast)));
            if ($cast) $tags['with_cast'] = $cast;
        }
        if (!empty($criteria['with_crew'])) {
            $crew = is_array($criteria['with_crew'])
                ? $criteria['with_crew']
                : explode(',', (string) $criteria['with_crew']);
            $crew = array_values(array_map('strval', array_filter($crew)));
            if ($crew) $tags['with_crew'] = $crew;
        }

        return $tags;
    }
}