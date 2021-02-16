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

    // GETTING STREAMING LINKS BRUV
    protected function sanitizedTitle($title)
    {
        $movieTitle = strtolower($title);
        $movieTitle = str_replace(' ', '-', $movieTitle);
        $movieTitle = preg_replace('/[^A-Za-z0-9\-]/', '', $movieTitle);
        $movieTitle = str_replace('--', '-', $movieTitle);
        $movieTitle = str_replace('·', '-', $movieTitle);
        return $movieTitle;
    }

    protected function getUserCountry(){
        $ip = \Request::ip();
        $data = \Location::get($ip);
        $country = $data->countryCode ?? 'LT';

        return $country;
    }

    protected function get_data($url) {
        $html = file_get_contents($url);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $div = $xpath->query('//div[@class="ott_provider"]');

        $content_stream = '';
        $content_rent = '';
        $content_buy = '';

        foreach ($div as $usefulDiv){
            if(strpos($usefulDiv->nodeValue, 'Stream')){
                $div_stream = $usefulDiv;
                $content_stream = $dom->saveXML($div_stream);
            }
            if(strpos($usefulDiv->nodeValue, 'Rent')){
                $div_rent = $usefulDiv;
                $content_rent = $dom->saveXML($div_rent);
            }
            if(strpos($usefulDiv->nodeValue, 'Buy')){
                $div_buy = $usefulDiv;
                $content_buy = $dom->saveXML($div_buy);
            }
        }

        if (strpos($content_stream, 'Stream') != false) {
            $content_all['stream'] = $content_stream;
        } else {
            $content_all['stream'] = null;
        }

        if (strpos($content_rent, 'Rent') != false) {
            $content_all['rent'] = $content_rent;
        } else {
            $content_all['rent'] = null;
        }

        if (strpos($content_buy, 'Buy') != false) {
            $content_all['buy'] = $content_buy;
        } else {
            $content_all['buy'] = null;
        }

        $return_links = [
            'stream' => null,
            'rent' => null,
            'buy' => null
        ];


        foreach ($content_all as $key => $content){
            preg_match_all ('/https:\/\/click.justwatch.com\/(.*?)"/s', $content, $streamingLinks);
            preg_match_all ('/\/t\/p\/original\/(.*?)"/s', $content, $streamingIcons);
            preg_match_all('/<span class="price">(.*?)<\/span>/', $content, $streamingPrice);
            $old_icon = '';
            for ($i = 0; $i < count($streamingLinks[0]); $i++){
                if($old_icon == 'https://www.themoviedb.org/'.rtrim($streamingIcons[0][$i], "\"")){
                    continue;
                }
                $old_icon = 'https://www.themoviedb.org/'.rtrim($streamingIcons[0][$i], "\"");
                $return_links[$key][$i]["URL"] = $streamingLinks[0][$i];
                $return_links[$key][$i]["icon"] = 'https://www.themoviedb.org/'.rtrim($streamingIcons[0][$i], "\"");
                if($streamingPrice[1] != []){
                    $return_links[$key][$i]["price"] = $streamingPrice[1][$i] ?? '';
                } else {
                    $return_links[$key][$i]["price"] = '';
                }
            }
        }
        return $return_links;
    }

    public function linksToStreams($title, $movie_id){
        $url = "https://www.themoviedb.org/movie/".$movie_id."-".$this->sanitizedTitle($title)."/watch?translate=false&locale=".$this->getUserCountry();
        return $this->get_data($url);
    }
}
