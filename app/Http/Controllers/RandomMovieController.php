<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\CheckFormData;

class RandomMovieController extends Controller
{
    public function show(CheckFormData $request, MovieService $movieService, TmdbClient $tmdb)
    {
        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url('/movie'));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        if ($request->input('movie_search')) {
            session()->forget('userInput');
            return redirect(url('/movie/' . $request->input('movie_search')));
        }

        $country      = $movieService->getUserCountry();
        $movieCriteria = $this->movieCriteria($request);
        $movieCriteria['page'] = $this->resolvePage($request, $movieService, $tmdb, $movieCriteria, $country);

        $results     = $tmdb->discover($movieCriteria, $country);
        $randomMovie = $movieService->randomMovie($results['results']);

        return redirect()->route('movie', [$randomMovie['id']]);
    }

    public function multiple(CheckFormData $request, MovieService $movieService, TmdbClient $tmdb)
    {
        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url('/multiple'));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        $country      = $movieService->getUserCountry();
        $movieCriteria = $this->movieCriteria($request);
        $movieCriteria['page'] = $this->resolvePage($request, $movieService, $tmdb, $movieCriteria, $country);

        $movies = $tmdb->discover($movieCriteria, $country);

        if (count($movies['results']) > 4) {
            shuffle($movies['results']);
            $movies['results'] = array_slice($movies['results'], 0, 4);
        }

        $all_genres   = $movieService->genres($tmdb);
        $movie_genres = $movieService->movieGenresMap($movies['results'], $all_genres);
        $user_input   = session('userInput');
        $providersArray = $movieService->buildProvidersArray($tmdb);
        $tag          = 'Movies picked for you';

        return view('multiple', compact('movies', 'user_input', 'all_genres', 'movie_genres', 'providersArray', 'tag'));
    }

    protected function movieCriteria(CheckFormData $request): array
    {
        $movieCriteria = $request->except(['_token', 'flexdatalist-with_cast', 'flexdatalist-with_crew', 'i', 'total_pages']);

        if (session('userInput') === null) {
            $request->session()->put('userInput', $movieCriteria);
        }

        if ($movieCriteria != session('userInput')) {
            $movieCriteria = session('userInput');
        }

        return $movieCriteria;
    }

    private function resolvePage(CheckFormData $request, MovieService $movieService, TmdbClient $tmdb, array $criteria, string $country): int
    {
        if (empty($criteria)) {
            return $movieService->randomPage(500);
        }

        if (isset($criteria['total_pages'])) {
            return $movieService->randomPage($criteria['total_pages']);
        }

        $all = $tmdb->discover($criteria, $country);
        $request->session()->put('userInput.total_pages', $all['total_pages']);
        return $movieService->randomPage($all['total_pages']);
    }
}
