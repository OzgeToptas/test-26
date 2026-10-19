<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CharacterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'services.rickandmorty.url' => 'https://rickandmortyapi.com/api',
        ]);

        // Prevent accidental requests to the real API.
        Http::preventStrayRequests();
    }

    /**
     * Mock a successful API response containing an empty character list.
     */
    private function fakeEmptyCharacterList(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character*' => Http::response([
                'info' => [
                    'count' => 0,
                    'pages' => 0,
                    'next' => null,
                    'prev' => null,
                ],
                'results' => [],
            ], 200),
        ]);
    }

    /**
     * The homepage should load successfully.
     */
    public function test_homepage_loads_successfully(): void
    {
        $this->fakeEmptyCharacterList();

        $this->get('/')
            ->assertOk()
            ->assertViewIs('characters.index');
    }

    /**
     * Negative page numbers must be rejected.
     */
    public function test_invalid_page_is_rejected(): void
    {
        $this->get('/?page=-1')
            ->assertSessionHasErrors(['page']);

        Http::assertNothingSent();
    }

    /**
     * Only supported character statuses are accepted.
     */
    public function test_invalid_status_is_rejected(): void
    {
        $this->get('/?status=invalid')
            ->assertSessionHasErrors(['status']);

        Http::assertNothingSent();
    }

    /**
     * Only supported genders are accepted.
     */
    public function test_invalid_gender_is_rejected(): void
    {
        $this->get('/?gender=invalid')
            ->assertSessionHasErrors(['gender']);

        Http::assertNothingSent();
    }

    /**
     * Search terms must not exceed 100 characters.
     */
    public function test_search_name_length_is_limited(): void
    {
        $this->get('/?name='.str_repeat('a', 101))
            ->assertSessionHasErrors(['name']);

        Http::assertNothingSent();
    }

    /**
     * Character IDs must contain only numeric characters.
     */
    public function test_non_numeric_character_id_returns_404(): void
    {
        $this->get('/characters/abc')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    /**
     * A missing character must return HTTP 404.
     */
    public function test_missing_character_returns_404(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/999999' => Http::response([
                'error' => 'Character not found',
            ], 404),
        ]);

        $this->get('/characters/999999')
            ->assertNotFound();

        Http::assertSent(function ($request) {
            return $request->url() ===
                'https://rickandmortyapi.com/api/character/999999';
        });
    }

    /**
     * An upstream API connection failure must return HTTP 503.
     */
    public function test_api_failure_returns_503(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $response = $this->get('/');

        $response->assertStatus(503);

        $response->assertSee(
            'Characters are temporarily unavailable.'
        );
    }

    /**
     * An upstream API rate limit must return HTTP 503.
     */
    public function test_rate_limit_returns_503(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character*' => Http::response([
                'error' => 'Rate limit exceeded',
            ], 429),
        ]);

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Characters are temporarily unavailable.');

        Http::assertSentCount(1);
    }

    public function test_malformed_upstream_response_shows_controlled_error(): void
    {
        Http::fake(['*' => Http::response(['unexpected' => 'data'])]);

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Characters are temporarily unavailable.');

        $this->get('/characters/1')->assertStatus(503);
    }
}
