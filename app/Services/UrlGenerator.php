<?php

namespace App\Services;

class UrlGenerator
{
    protected function metacritic($movieObj): ?string
    {
        if (!property_exists($movieObj, 'Title')) {
            return null;
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', str_replace([' ', '--'], ['-', '-'], strtolower($movieObj->Title)));
        return 'https://www.metacritic.com/movie/' . $slug;
    }

    protected function rottenTomatoes($movieObj): string
    {
        // If OMDb already returned a direct URL, use it.
        if (isset($movieObj->tomatoURL)) {
            return $movieObj->tomatoURL;
        }

        if (!property_exists($movieObj, 'Title')) {
            return '#';
        }

        $string = strtolower($movieObj->Title);

        // Strip leading "the "
        if (str_starts_with($string, 'the ')) {
            $string = ltrim(substr($string, 4));
        }

        $string = str_replace('&', 'and', $string);
        $slug   = preg_replace('/[^a-z0-9\_]/', '', str_replace(' ', '_', $string));

        // Return the year-suffixed guess; browser handles any 404.
        return 'https://www.rottentomatoes.com/m/' . $slug . '_' . $movieObj->Year;
    }

    protected function imdb($movieObj): ?string
    {
        if (!property_exists($movieObj, 'imdbID')) {
            return null;
        }
        return 'https://www.imdb.com/title/' . $movieObj->imdbID;
    }

    public function linksArray($movieObj): array
    {
        return [
            'Internet Movie Database' => $this->imdb($movieObj),
            'Rotten Tomatoes'         => $this->rottenTomatoes($movieObj),
            'Metacritic'              => $this->metacritic($movieObj),
        ];
    }
}
