<?php

namespace App\Services;

class UrlGenerator{

    // Gueses Metacritic URL
    protected function metacritic($movieObj)
    {
        if (!property_exists($movieObj, 'Title')) {
            return null;
        }

        $movieTitle = strtolower($movieObj->Title);
        $movieTitle = str_replace(' ', '-', $movieTitle);
        $movieTitle = preg_replace('/[^A-Za-z0-9\-]/', '', $movieTitle);
        $movieTitle = str_replace('--', '-', $movieTitle);
        return 'https://www.metacritic.com/movie/'.$movieTitle;
    }

    // Guesses Rotten Tomatoes URL
    protected function rottenTomatoes($movieObj)
    {
        $url = '';

        if (isset($movieObj->tomatoURL)) {
            $url = $movieObj->tomatoURL;
            return $url;
        }

        if (!property_exists($movieObj, 'Title')) {
            return null;
        }

        $string = strtolower($movieObj->Title);
        $stringArray = explode(' ', $string);
        if ($stringArray[0] == "the") {
            $string = trim(strstr($string, " "));
        }
        if (in_array ('&', $stringArray)) {
            $string = str_replace('&', 'and', $string);
        }
        $string = str_replace(' ', '_', $string);
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string);
        $url = "https://www.rottentomatoes.com/m/".$string.'_'.$movieObj->Year;
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($httpCode == 404) {
            $url = "https://www.rottentomatoes.com/m/".$string;
            $handle = curl_init($url);
            curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
            $response = curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            if ($httpCode == 404) {
                return '#';
            }
        }
        return $url;
    }

    // Gets IMDB URL
    protected function Imdb($movieObj)
    {
        if (!property_exists($movieObj, 'imdbID')) {
            return null;
        }
        return 'https://www.imdb.com/title/'.$movieObj->imdbID;
    }

    // Returns all 3 links to a movie
    public function linksArray($movieObj)
    {
        $linksToMovies = [
            'Internet Movie Database' => $this->Imdb($movieObj),
            'Rotten Tomatoes' => $this->rottenTomatoes($movieObj),
            'Metacritic' => $this->metacritic($movieObj),
        ];

        return $linksToMovies;
    }
}
