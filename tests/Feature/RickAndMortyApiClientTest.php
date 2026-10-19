<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\RickAndMortyApiException;
use App\Services\RickAndMortyApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RickAndMortyApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'services.rickandmorty.url' => 'https://rickandmortyapi.com/api',
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_fetches_character_data(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/1' => Http::response([
                'id' => 1,
                'name' => 'Rick Sanchez',
            ], 200),
        ]);

        $result = app(RickAndMortyApiClient::class)
            ->get('character/1');

        $this->assertSame('Rick Sanchez', $result['name']);

        Http::assertSentCount(1);
    }

    public function test_it_caches_successful_responses(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/1' => Http::response([
                'id' => 1,
                'name' => 'Rick Sanchez',
            ], 200),
        ]);

        $client = app(RickAndMortyApiClient::class);

        $client->get('character/1');
        $client->get('character/1');

        // The second request should use the cache.
        Http::assertSentCount(1);
    }

    public function test_it_handles_missing_resources(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/999999' => Http::response([], 404),
        ]);

        try {
            app(RickAndMortyApiClient::class)
                ->get('character/999999');

            $this->fail('Expected an API exception.');
        } catch (RickAndMortyApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function test_it_handles_rate_limiting(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/1' => Http::response([], 429),
        ]);

        try {
            app(RickAndMortyApiClient::class)
                ->get('character/1');

            $this->fail('Expected a rate limit exception.');
        } catch (RickAndMortyApiException $exception) {
            $this->assertSame(429, $exception->statusCode());
        }
    }

    public function test_it_rejects_invalid_endpoints(): void
    {
        Http::fake();

        $this->expectException(\InvalidArgumentException::class);

        app(RickAndMortyApiClient::class)
            ->get('https://example.com/private');

        Http::assertNothingSent();
    }

    public function test_it_handles_connection_failures(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        try {
            app(RickAndMortyApiClient::class)->get('character/1');

            $this->fail('Expected an API exception.');
        } catch (RickAndMortyApiException $exception) {
            $this->assertSame(503, $exception->statusCode());
        }
    }

    public function test_it_rejects_invalid_api_responses(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/1' => Http::response('invalid json', 200),
        ]);

        try {
            app(RickAndMortyApiClient::class)->get('character/1');

            $this->fail('Expected an API exception.');
        } catch (RickAndMortyApiException $exception) {
            $this->assertSame(502, $exception->statusCode());
        }
    }
}
