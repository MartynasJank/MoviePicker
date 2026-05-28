<?php

namespace App\Services;

class RatingsUrlBuilder
{
    private function metacritic(?object $omdbObj, string $type = 'movie'): ?string
    {
        if ($omdbObj === null || !property_exists($omdbObj, 'Title')) {
            return null;
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', str_replace([' ', '--'], ['-', '-'], strtolower($omdbObj->Title)));
        $section = $type === 'tv' ? 'tv' : 'movie';
        return 'https://www.metacritic.com/' . $section . '/' . $slug;
    }

    private function rottenTomatoes(?object $omdbObj, string $type = 'movie'): string
    {
        if ($omdbObj === null || !property_exists($omdbObj, 'Title')) {
            return '#';
        }

        if (isset($omdbObj->tomatoURL)) {
            return $omdbObj->tomatoURL;
        }

        $string = strtolower($omdbObj->Title);

        if (str_starts_with($string, 'the ')) {
            $string = ltrim(substr($string, 4));
        }

        $string = str_replace('&', 'and', $string);

        if ($type === 'tv') {
            $slug = preg_replace('/[^a-z0-9\_]/', '', str_replace(' ', '_', $string));
            return 'https://www.rottentomatoes.com/tv/' . $slug;
        }

        $slug = preg_replace('/[^a-z0-9\_]/', '', str_replace(' ', '_', $string));
        return 'https://www.rottentomatoes.com/m/' . $slug . '_' . $omdbObj->Year;
    }

    private function imdb(?object $omdbObj): ?string
    {
        if ($omdbObj === null || !property_exists($omdbObj, 'imdbID')) {
            return null;
        }
        return 'https://www.imdb.com/title/' . $omdbObj->imdbID;
    }

    public function links(?object $omdbObj, string $type = 'movie'): array
    {
        return [
            'Internet Movie Database' => $this->imdb($omdbObj),
            'Rotten Tomatoes'         => $this->rottenTomatoes($omdbObj, $type),
            'Metacritic'              => $this->metacritic($omdbObj, $type),
        ];
    }
}
