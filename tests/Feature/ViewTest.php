<?php

namespace Tests\Feature;

use App\TMDB;
use App\Services\MovieService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function homepage_returns_200()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test */
    public function movie_page_returns_200()
    {
        $response = $this->get('/movie/769');
        $response->assertStatus(200);
    }

    /** @test */
    public function criteria_page_returns_200_and_shows_genres()
    {
        $genres = app(MovieService::class)->genres(app(TMDB::class));

        $response = $this->get('/criteria');

        $response->assertStatus(200)->assertSee($genres[0]->name);
    }
}
