<?php

use App\Services\OmdbClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['api.OMDB' => 'test-key', 'api.omdb_enabled' => true]);
});

it('skips optional OMDb requests when disabled or unconfigured', function (bool $enabled, ?string $key, ?string $imdbId) {
    config(['api.omdb_enabled' => $enabled, 'api.OMDB' => $key]);
    Http::preventStrayRequests();

    expect(app(OmdbClient::class)->movie($imdbId))->toBeNull();
    expect(app(OmdbClient::class)->tv($imdbId))->toBeNull();

    Http::assertNothingSent();
})->with([
    'disabled' => [false, 'test-key', 'tt1234567'],
    'missing key' => [true, null, 'tt1234567'],
    'blank key' => [true, '  ', 'tt1234567'],
    'legacy placeholder' => [true, 'No Key', 'tt1234567'],
    'missing IMDb ID' => [true, 'test-key', null],
]);

it('returns no ratings when OMDb returns an error or malformed data', function (int $status, string $body, string $type) {
    Http::preventStrayRequests();
    Http::fake(['private.omdbapi.com/*' => Http::response($body, $status)]);

    expect(app(OmdbClient::class)->{$type}('tt1234567'))->toBeNull();

    Http::assertSentCount(1);
})->with([
    'invalid key' => [401, '{"Response":"False","Error":"Invalid API key!"}'],
    'quota exhausted' => [200, '{"Response":"False","Error":"Request limit reached!"}'],
    'server error' => [503, 'Unavailable'],
    'invalid JSON' => [200, '<html>Error</html>'],
    'unexpected array' => [200, '[]'],
    'incomplete response' => [200, '{}'],
])->with(['movie', 'tv']);

it('returns no ratings when OMDb cannot connect or times out', function (string $type) {
    Http::preventStrayRequests();
    Http::fake(['private.omdbapi.com/*' => Http::failedConnection()]);

    expect(app(OmdbClient::class)->{$type}('tt1234567'))->toBeNull();

    Http::assertSentCount(1);
})->with(['movie', 'tv']);

it('keeps successful OMDb ratings available and cached', function (string $type) {
    Http::preventStrayRequests();
    Http::fake(['private.omdbapi.com/*' => Http::response([
        'Response' => 'True',
        'Title' => 'Test title',
        'Ratings' => [['Source' => 'Rotten Tomatoes', 'Value' => '90%']],
    ])]);

    $client = app(OmdbClient::class);
    expect($client->{$type}('tt1234567')->Ratings[0]->Value)->toBe('90%');
    expect($client->{$type}('tt1234567')->Title)->toBe('Test title');

    Http::assertSentCount(1);
})->with(['movie', 'tv']);
