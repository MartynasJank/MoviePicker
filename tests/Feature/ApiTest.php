<?php

namespace Tests\Feature;

use App\OMDB;
use App\TMDB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_tmdb_api_movie(){
        $tmdb = new TMDB;
        $movie = $tmdb->movie(769, false);

        $response = $this->get('/movie/769');
        $response->assertStatus(200)->assertSee($movie->original_title);
    }

    /** @test */
    public function test_tmdb_api_discover(){
        $response = $this->get('/movie');
        $response->assertStatus(302);
    }

    /** @test */
    public function test_omdb_api(){
        $omdb = new OMDB;
        $movie = $omdb->movie('tt0099685');

        $response = $this->get('/movie/769');
        $response->assertStatus(200)->assertSee($movie->Ratings[0]->Value);
    }
}
