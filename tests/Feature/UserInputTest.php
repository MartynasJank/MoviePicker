<?php

use Illuminate\Testing\Fluent\AssertableJson;

it('returns null when no session exists', function () {
    $this->getJson('/userinput')
        ->assertOk()
        ->assertExactJson([]);
});

it('returns movie session by default', function () {
    session(['userInput' => ['with_genres' => [28], 'vote_count_gte' => 50]]);

    $this->getJson('/userinput')
        ->assertOk()
        ->assertJsonFragment(['vote_count_gte' => 50]);
});

it('returns tv session when type=tv is passed', function () {
    session([
        'userInput' => ['vote_count_gte' => 50],
        'tvInput'   => ['vote_count_gte' => 99, 'with_cast' => [123]],
    ]);

    $this->getJson('/userinput?type=tv')
        ->assertOk()
        ->assertJsonFragment(['vote_count_gte' => 99])
        ->assertJsonFragment(['with_cast' => [123]]);
});

it('does not return tv session when type is omitted', function () {
    session([
        'userInput' => ['vote_count_gte' => 50],
        'tvInput'   => ['vote_count_gte' => 99],
    ]);

    $this->getJson('/userinput')
        ->assertOk()
        ->assertJsonFragment(['vote_count_gte' => 50]);
});
