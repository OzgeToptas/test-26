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
                'status' => 'Alive',
                'species' => 'Human',
                'gender' => 'Male',
                'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
                'origin' => ['name' => 'Earth'],
                'location' => ['name' => 'Earth'],
                'episode' => [],
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
                'status' => 'Alive',
                'species' => 'Human',
                'gender' => 'Male',
                'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
                'origin' => ['name' => 'Earth'],
                'location' => ['name' => 'Earth'],
                'episode' => [],
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

    public function test_malformed_responses_are_rejected_and_not_cached(): void
    {
        $cases = [
            ['character', ['unexpected' => 'data']],
            ['character', ['info' => ['count' => '1', 'pages' => 1], 'results' => []]],
            ['character', ['info' => ['count' => 1, 'pages' => 1], 'results' => 'invalid']],
            ['character', ['info' => ['count' => 1, 'pages' => 1], 'results' => [null]]],
            ['character/1', ['id' => 1, 'name' => 'Rick']],
            ['episode/1', ['id' => 1, 'name' => 'Pilot', 'episode' => [], 'air_date' => 'today']],
            ['episode/1,2', [['id' => 1]]],
        ];

        foreach ($cases as [$endpoint, $payload]) {
            Cache::flush();
            Http::fake(['*' => Http::response($payload)]);

            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    app(RickAndMortyApiClient::class)->get($endpoint);
                    $this->fail('Expected malformed response to be rejected.');
                } catch (RickAndMortyApiException $exception) {
                    $this->assertSame(502, $exception->statusCode());
                }
            }

            Http::assertSentCount(2);
        }
    }

    public function test_single_episode_response_is_accepted(): void
    {
        $episode = ['id' => 1, 'name' => 'Pilot', 'episode' => 'S01E01', 'air_date' => 'December 2, 2013'];
        Http::fake(['*' => Http::response($episode)]);

        $this->assertSame($episode, app(RickAndMortyApiClient::class)->get('episode/1'));
    }

    public function test_character_nested_fields_are_validated(): void
    {
        $character = [
            'id' => 1, 'name' => 'Rick', 'status' => 'Alive', 'species' => 'Human',
            'gender' => 'Male', 'image' => 'https://example.com/rick.jpg',
            'origin' => ['name' => 'Earth'], 'location' => ['name' => 'Earth'], 'episode' => [],
        ];

        foreach ([['origin', 'invalid'], ['location', ['name' => []]], ['episode', [123]],
            ['episode', 'invalid'], ['image', 'javascript:alert(1)'], ['type', []]] as [$field, $value]) {
            Http::fake(['*' => Http::response(array_replace($character, [$field => $value]))]);

            try {
                app(RickAndMortyApiClient::class)->get('character/1');
                $this->fail('Expected invalid character field to be rejected.');
            } catch (RickAndMortyApiException $exception) {
                $this->assertSame(502, $exception->statusCode());
            }
        }
    }

    public function test_upstream_server_errors_are_not_cached(): void
    {
        Http::fakeSequence()->push([], 500)->push([], 503);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                app(RickAndMortyApiClient::class)->get('character/1');
                $this->fail('Expected an upstream error.');
            } catch (RickAndMortyApiException $exception) {
                $this->assertSame(502, $exception->statusCode());
            }
        }

        Http::assertSentCount(2);
    }
}
