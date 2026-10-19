<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RickAndMortyApiException;

final readonly class CharacterService
{
    public function __construct(
        private RickAndMortyApiClient $api
    ) {}

    /**
     * Retrieve a paginated list of characters.
     *
     * Search, filtering and pagination are handled by
     * the Rick and Morty API rather than in PHP memory.
     */
    public function getCharacters(
        int $page = 1,
        array $filters = []
    ): array {
        $query = array_filter(
            [
                'page' => $page,
                'name' => $filters['name'] ?? null,
                'status' => $filters['status'] ?? null,
                'species' => $filters['species'] ?? null,
                'gender' => $filters['gender'] ?? null,
            ],
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        try {
            $response = $this->api->get('character', $query);
        } catch (RickAndMortyApiException $exception) {
            if ($exception->statusCode() !== 404) {
                throw $exception;
            }

            // The API returns 404 when no characters match the filters.
            return [
                'characters' => [],
                'pagination' => [
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => $page,
                ],
            ];
        }

        return [
            'characters' => array_map(
                static fn (array $character): array => [
                    'id' => $character['id'],
                    'name' => $character['name'],
                    'status' => $character['status'],
                    'species' => $character['species'],
                    'gender' => $character['gender'],
                    'image' => $character['image'],
                ],
                $response['results'] ?? []
            ),
            'pagination' => [
                'total' => $response['info']['count'] ?? 0,
                'pages' => $response['info']['pages'] ?? 0,
                'current_page' => $page,
            ],
        ];
    }

    /**
     * Retrieve a single character and its episodes.
     */
    public function getCharacter(int $id): array
    {
        $character = $this->api->get("character/{$id}");

        return [
            'id' => $character['id'],
            'name' => $character['name'],
            'status' => $character['status'],
            'species' => $character['species'],
            'gender' => $character['gender'],
            'type' => $character['type'] ?? '',
            'image' => $character['image'],
            'origin' => $character['origin']['name'] ?? 'Unknown',
            'location' => $character['location']['name'] ?? 'Unknown',
            'episodes' => $this->getEpisodes($character['episode'] ?? []),
        ];
    }

    /**
     * Fetch episode details using the API's bulk endpoint.
     *
     * Avoids making a separate HTTP request for each episode.
     */
    private function getEpisodes(array $episodeUrls): array
    {
        if (empty($episodeUrls)) {
            return [];
        }

        $episodeIds = [];

        foreach ($episodeUrls as $url) {
            if (preg_match('~/episode/([0-9]+)$~', $url, $matches)) {
                $episodeIds[] = (int) $matches[1];
            }
        }

        $episodeIds = array_values(array_unique($episodeIds));

        if (empty($episodeIds)) {
            return [];
        }

        $response = $this->api->get(
            'episode/'.implode(',', $episodeIds)
        );

        // The API returns an object for one episode and an array for many.
        $episodes = isset($response['id'])
            ? [$response]
            : $response;

        return array_map(
            static fn (array $episode): array => [
                'id' => $episode['id'],
                'name' => $episode['name'],
                'episode' => $episode['episode'],
                'air_date' => $episode['air_date'],
            ],
            $episodes
        );
    }
}
