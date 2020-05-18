<?php

namespace App\Services;

use App\Services\Tmdb;

class MovieService
{
    // Gets random page from total pages
    public function randomPage($totalPages)
    {
        return rand(1, $totalPages);
    }

    public function randomMovie($movieArray)
    {
        $key = array_rand($movieArray);
        return $movieArray[$key];
    }

    public function genresString($movieObj)
    {
        if ($movieObj->genres === []) {
            return 'No Info';
        }

        foreach ($movieObj->genres as $genre) {
            $genresArray[] = $genre->name;
        }

        return implode(', ', $genresArray);
    }

    public function getTrailer($array)
    {
        $maxSize = [];
        $trailer = null;
        $allowed = true;

        if (!empty($array)) {
            foreach ($array as $key => $video) {
                if ($video->type == 'Trailer') {
                    $maxSize[$key]['size'] = $video->size;
                    $maxSize[$key]['key'] = $video->key;
                }
            }
            $maxSize = $this->sortAssocArrayByValue($maxSize, 'size');
            foreach ($maxSize as $video) {
                $json = file_get_contents('https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id='.$video['key'].'&part=status&key='.config('api.YOUTUBE'));
                $status = json_decode($json);

                // Checks if video is region restricted and then checks if user is in country where video is allowed to watch
                if (isset($status->items[0]->contentDetails->regionRestriction->allowed)) {
                    $ip = \Request::ip();
                    $data = \Location::get($ip);

                    $allowedCountries = $status->items[0]->contentDetails->regionRestriction->allowed;
                    $country = $data->countryCode ?? 'LT';
                    $allowed = in_array($country, $allowedCountries);
                }

                if ($status->pageInfo->totalResults > 0 && $allowed) {
                    $trailer = $video['key'];
                    break;
                }
            }
        }
        return $trailer;
    }

    protected function sortAssocArrayByValue($array, $value)
    {
        $newArray = array_column($array, $value);
        array_multisort($newArray, SORT_DESC, $array);
        return $array;
    }
}
