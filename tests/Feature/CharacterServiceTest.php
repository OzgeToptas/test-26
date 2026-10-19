<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CharacterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CharacterServiceTest extends TestCase
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

    public function test_it_lists_characters_with_pagination(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character*' => Http::response([
                'info' => [
                    'count' => 826,
                    'pages' => 42,
                ],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Rick Sanchez',
                        'status' => 'Alive',
                        'species' => 'Human',
                        'gender' => 'Male',
                        'image' => 'https://example.com/rick.jpg',
                    ],
                ],
            ]),
        ]);

        $result = app(CharacterService::class)
            ->getCharacters(2);

        $this->assertSame(826, $result['pagination']['total']);
        $this->assertSame(42, $result['pagination']['pages']);
        $this->assertSame(2, $result['pagination']['current_page']);
        $this->assertSame('Rick Sanchez', $result['characters'][0]['name']);

        Http::assertSent(
            fn ($request) => $request->url() ===
                'https://rickandmortyapi.com/api/character?page=2'
        );
    }

    public function test_it_passes_search_and_filters_to_api(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character*' => Http::response([
                'info' => [
                    'count' => 1,
                    'pages' => 1,
                ],
                'results' => [],
            ]),
        ]);

        app(CharacterService::class)->getCharacters(1, [
            'name' => 'rick',
            'status' => 'alive',
        ]);

        Http::assertSent(function ($request) {
            return $request['name'] === 'rick'
                && $request['status'] === 'alive'
                && (int) $request['page'] === 1;
        });
    }

    public function test_it_handles_empty_search_results(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character*' => Http::response(['error' => 'There is nothing here'], 404),
        ]);

        $result = app(CharacterService::class)
            ->getCharacters(1, ['name' => 'nonexistent']);

        $this->assertSame([], $result['characters']);
        $this->assertSame(0, $result['pagination']['total']);
    }

    public function test_it_fetches_character_with_episodes(): void
    {
        Http::fake([
            'rickandmortyapi.com/api/character/1' => Http::response([
                'id' => 1,
                'name' => 'Rick Sanchez',
                'status' => 'Alive',
                'species' => 'Human',
                'gender' => 'Male',
                'type' => '',
                'image' => 'https://example.com/rick.jpg',
                'origin' => ['name' => 'Earth (C-137)'],
                'location' => ['name' => 'Citadel of Ricks'],
                'episode' => [
                    'https://rickandmortyapi.com/api/episode/1',
                    'https://rickandmortyapi.com/api/episode/2',
                ],
            ]),

            'rickandmortyapi.com/api/episode/1,2' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Pilot',
                    'episode' => 'S01E01',
                    'air_date' => 'December 2, 2013',
                ],
                [
                    'id' => 2,
                    'name' => 'Lawnmower Dog',
                    'episode' => 'S01E02',
                    'air_date' => 'December 9, 2013',
                ],
            ]),
        ]);

        $character = app(CharacterService::class)
            ->getCharacter(1);

        $this->assertSame('Rick Sanchez', $character['name']);
        $this->assertSame('Earth (C-137)', $character['origin']);
        $this->assertCount(2, $character['episodes']);
        $this->assertSame('Pilot', $character['episodes'][0]['name']);

        // One request for the character, one for all episodes.
        Http::assertSentCount(2);
    }
}
