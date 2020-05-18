<?php

namespace Tests\Feature;

use App\TMDB;
use App\Click;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_search_movie_by_id()
    {
        $response = $this->get('/movie/769');

        $response->assertStatus(200);
    }

    /** @test */
    public function trending_gets_passed_to_homepage(){
        $response = $this->get('/');

        $response->assertStatus(200);
    }


    /** @test */
    public function genres_get_passed_to_view(){
        $tmdb = new TMDB;
        $genres = $tmdb->genres();

        $response = $this->get('/criteria');

        $response->assertStatus(200)->assertSee($genres[0]->name);
    }

    /** @test */
    public function test_if_clicks_get_added_to_db(){
        $click = factory(Click::class)->create();
        $this->assertDatabaseHas('clicks', $click->getAttributes());
    }

}
