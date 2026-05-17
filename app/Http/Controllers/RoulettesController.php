<?php

namespace App\Http\Controllers;

use App\TMDB;
use App\Services\MovieService;

class RoulettesController extends Controller
{
    public function show()
    {
        return view('roulettes');
    }

    public function netflixHorror(MovieService $movieService, TMDB $tmdb)
    {
        $country  = $movieService->getUserCountry();
        $criteria = ['with_genres' => [27], 'with_watch_providers' => [8]];
        $criteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixHorror', $criteria, $country);
        $movies   = $this->getMovies($tmdb, $criteria, $country);

        $all_genres  = $movieService->genres($tmdb);
        $movie_genres = $movieService->movieGenresMap($movies['results'], $all_genres);

        return view('multiple', [
            'movies'       => $movies,
            'movie_genres' => $movie_genres,
            'tag'          => 'Netflix Horror Movies',
            'title'        => 'Netflix Horror — MoviePickr',
        ]);
    }

    public function netflixDoc(MovieService $movieService, TMDB $tmdb)
    {
        $country  = $movieService->getUserCountry();
        $criteria = ['with_genres' => [99], 'with_watch_providers' => [8]];
        $criteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixDoc', $criteria, $country);
        $movies   = $this->getMovies($tmdb, $criteria, $country);

        $all_genres  = $movieService->genres($tmdb);
        $movie_genres = $movieService->movieGenresMap($movies['results'], $all_genres);

        return view('multiple', [
            'movies'       => $movies,
            'movie_genres' => $movie_genres,
            'tag'          => 'Netflix Documentaries',
            'title'        => 'Netflix Documentaries — MoviePickr',
        ]);
    }

    public function netflixAnimeMovies(MovieService $movieService, TMDB $tmdb)
    {
        $country  = $movieService->getUserCountry();
        $criteria = ['with_genres' => [16], 'with_watch_providers' => [8], 'with_original_language' => 'ja'];
        $criteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixAnime', $criteria, $country);
        $movies   = $this->getMovies($tmdb, $criteria, $country);

        $all_genres  = $movieService->genres($tmdb);
        $movie_genres = $movieService->movieGenresMap($movies['results'], $all_genres);

        return view('multiple', [
            'movies'       => $movies,
            'movie_genres' => $movie_genres,
            'tag'          => 'Netflix Anime Movies',
            'title'        => 'Netflix Anime Movies — MoviePickr',
        ]);
    }

    private function getMovieCriteria(MovieService $movieService, TMDB $tmdb, string $type, array $criteria, string $country): array
    {
        if (session('roulette.type') !== $type) {
            session()->forget('roulette');
        }

        if (session()->has('roulette.total_pages')) {
            $criteria['page'] = $movieService->randomPage(session('roulette.total_pages'));
        } else {
            $all = $tmdb->discover($criteria, $country);
            session()->put('roulette', ['type' => $type, 'total_pages' => $all['total_pages']]);
            $criteria['page'] = $movieService->randomPage($all['total_pages']);
        }

        return $criteria;
    }

    private function getMovies(TMDB $tmdb, array $criteria, string $country): array
    {
        $movies = $tmdb->discover($criteria, $country);

        if (count($movies['results']) > 4) {
            shuffle($movies['results']);
            $movies['results'] = array_slice($movies['results'], 0, 4);
        }

        return $movies;
    }
}
