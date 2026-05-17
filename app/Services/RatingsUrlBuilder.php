<?php

namespace App\Services;

class RatingsUrlBuilder
{
    private function metacritic(?object $movieObj): ?string
    {
        if ($movieObj === null || !property_exists($movieObj, 'Title')) {
            return null;
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', str_replace([' ', '--'], ['-', '-'], strtolower($movieObj->Title)));
        return 'https://www.metacritic.com/movie/' . $slug;
    }

    private function rottenTomatoes(?object $movieObj): string
    {
        if ($movieObj === null || !property_exists($movieObj, 'Title')) {
            return '#';
        }

        if (isset($movieObj->tomatoURL)) {
            return $movieObj->tomatoURL;
        }

        $string = strtolower($movieObj->Title);

        if (str_starts_with($string, 'the ')) {
            $string = ltrim(substr($string, 4));
        }

        $string = str_replace('&', 'and', $string);
        $slug   = preg_replace('/[^a-z0-9\_]/', '', str_replace(' ', '_', $string));

        return 'https://www.rottentomatoes.com/m/' . $slug . '_' . $movieObj->Year;
    }

    private function imdb(?object $movieObj): ?string
    {
        if ($movieObj === null || !property_exists($movieObj, 'imdbID')) {
            return null;
        }
        return 'https://www.imdb.com/title/' . $movieObj->imdbID;
    }

    public function linksArray(?object $movieObj): array
    {
        return [
            'Internet Movie Database' => $this->imdb($movieObj),
            'Rotten Tomatoes'         => $this->rottenTomatoes($movieObj),
            'Metacritic'              => $this->metacritic($movieObj),
        ];
    }
}
