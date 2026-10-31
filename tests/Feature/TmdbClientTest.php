<?php

use App\Services\TmdbClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

it('uses the configured bearer token and bounded network timeouts', function () {
    config(['api.tmdb_token' => 'configured-token']);
    $requests = [];
    $handler = function (RequestInterface $request, array $options) use (&$requests) {
        $requests[] = [$request, $options];
        return Create::promiseFor(new Response(200, [], '{"id":123}'));
    };
    $client = new TmdbClient(new Client(['handler' => $handler]));

    expect($client->movie(123)->id)->toBe(123);

    expect($requests)->toHaveCount(1);
    expect($requests[0][0]->getHeaderLine('Authorization'))->toBe('Bearer configured-token');
    expect($requests[0][1])->toMatchArray(['timeout' => 10, 'connect_timeout' => 3]);
});

it('uses the configured API key when no bearer token is supplied', function () {
    config(['api.tmdb_token' => null, 'api.TMDB' => 'configured-key']);
    $requests = [];
    $handler = function (RequestInterface $request) use (&$requests) {
        $requests[] = $request;
        return Create::promiseFor(new Response(200, [], '{"id":123}'));
    };

    expect((new TmdbClient(new Client(['handler' => $handler])))->movie(123)->id)->toBe(123);

    parse_str($requests[0]->getUri()->getQuery(), $query);
    expect($query['api_key'])->toBe('configured-key');
    expect($query['append_to_response'])->toContain('credits');
    expect($requests[0]->hasHeader('Authorization'))->toBeFalse();
});

it('propagates connection failures without caching a failed result', function () {
    $requests = 0;
    $handler = function (RequestInterface $request) use (&$requests) {
        $requests++;
        if ($requests === 1) {
            return Create::rejectionFor(new ConnectException('Timed out', $request));
        }
        return Create::promiseFor(new Response(200, [], '{"id":123}'));
    };
    $client = new TmdbClient(new Client(['handler' => $handler]));

    expect(fn () => $client->movie(123))->toThrow(ConnectException::class);
    expect($client->movie(123)->id)->toBe(123);
    expect($requests)->toBe(2);
});

it('authenticates streaming provider requests with the configured token', function () {
    config(['api.tmdb_token' => 'configured-token', 'api.TMDB' => null]);
    $requests = [];
    $handler = function (RequestInterface $request, array $options) use (&$requests) {
        $requests[] = [$request, $options];
        return Create::promiseFor(new Response(200, [], '{"results":[]}'));
    };
    $client = new TmdbClient(new Client(['handler' => $handler]));

    expect($client->watchProviders('LT')->results)->toBe([]);

    expect($requests[0][0]->getHeaderLine('Authorization'))->toBe('Bearer configured-token');
    parse_str($requests[0][0]->getUri()->getQuery(), $query);
    expect($query['watch_region'])->toBe('LT');
    expect($requests[0][1])->toMatchArray(['timeout' => 10, 'connect_timeout' => 3]);
});
