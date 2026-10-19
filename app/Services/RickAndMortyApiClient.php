<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RickAndMortyApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class RickAndMortyApiClient
{
    public function get(string $endpoint, array $query = []): array
    {
        // Only allow known API endpoints.
        if (! preg_match(
            '~^(character|episode|location)(?:/[0-9]+(?:,[0-9]+)*)?$~',
            $endpoint
        )) {
            throw new InvalidArgumentException('Invalid API endpoint.');
        }

        $baseUrl = rtrim(
            config('services.rickandmorty.url'),
            '/'
        );

        $url = $baseUrl.'/'.$endpoint;

        // Generate stable cache keys regardless of query parameter order.
        ksort($query);

        $cacheKey = 'rickandmorty:'.hash(
            'sha256',
            $endpoint.'?'.http_build_query($query)
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($url, $query, $endpoint): array {
                try {
                    $response = Http::acceptJson()
                        ->connectTimeout(3)
                        ->timeout(8)
                        ->get($url, $query);

                    // Resource not found.
                    if ($response->status() === 404) {
                        throw new RickAndMortyApiException(
                            'Resource not found.',
                            404
                        );
                    }

                    // API rate limit reached.
                    if ($response->status() === 429) {
                        Log::warning('Rick and Morty API rate limit reached');

                        throw new RickAndMortyApiException(
                            'API rate limit exceeded.',
                            429
                        );
                    }

                    // Handle all other unsuccessful HTTP responses.
                    $response->throw();

                    $data = $response->json();

                    // Validate the JSON response structure.
                    if (! is_array($data) || ! $this->hasValidShape($endpoint, $data)) {
                        throw new RickAndMortyApiException(
                            'Invalid API response.',
                            502
                        );
                    }

                    return $data;

                } catch (ConnectionException $e) {
                    Log::warning('Rick and Morty API connection failed', [
                        'message' => $e->getMessage(),
                    ]);

                    throw new RickAndMortyApiException(
                        'Rick and Morty API is unavailable.',
                        503
                    );

                } catch (RequestException $e) {
                    Log::warning('Rick and Morty API request failed', [
                        'status' => $e->response->status(),
                    ]);

                    throw new RickAndMortyApiException(
                        'Rick and Morty API request failed.',
                        502
                    );
                }
            }
        );
    }

    /** Validate consumed fields before returning or caching upstream data. */
    private function hasValidShape(string $endpoint, array $data): bool
    {
        [$resource] = explode('/', $endpoint);
        $isCollection = ! str_contains($endpoint, '/');
        $isBulk = str_contains($endpoint, ',');

        if ($isCollection) {
            if (! isset($data['info'], $data['results'])
                || ! is_array($data['info'])
                || ! is_array($data['results'])
                || ! array_is_list($data['results'])) {
                return false;
            }

            foreach (['count', 'pages'] as $field) {
                if (! isset($data['info'][$field])
                    || ! is_int($data['info'][$field])
                    || $data['info'][$field] < 0) {
                    return false;
                }
            }

            $items = $data['results'];
        } elseif ($isBulk) {
            if (! array_is_list($data) || $data === []) {
                return false;
            }

            $items = $data;
        } else {
            $items = [$data];
        }

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['id'])
                || ! is_int($item['id']) || $item['id'] < 1) {
                return false;
            }

            $fields = match ($resource) {
                'character' => ['name', 'status', 'species', 'gender', 'image'],
                'episode' => ['name', 'episode', 'air_date'],
                'location' => ['name'],
            };

            foreach ($fields as $field) {
                if (! isset($item[$field]) || ! is_string($item[$field])) {
                    return false;
                }
            }

            if ($resource === 'character') {
                if (! filter_var($item['image'], FILTER_VALIDATE_URL)
                    || ! in_array(parse_url($item['image'], PHP_URL_SCHEME), ['https', 'http'], true)) {
                    return false;
                }

                if (! $isCollection) {
                    foreach (['origin', 'location'] as $field) {
                        if (! isset($item[$field]) || ! is_array($item[$field])
                            || ! isset($item[$field]['name']) || ! is_string($item[$field]['name'])) {
                            return false;
                        }
                    }

                    if (array_key_exists('type', $item) && ! is_string($item['type'])) {
                        return false;
                    }

                    if (! isset($item['episode']) || ! is_array($item['episode'])
                        || ! array_is_list($item['episode'])) {
                        return false;
                    }

                    foreach ($item['episode'] as $url) {
                        if (! is_string($url)
                            || ! preg_match('~^https?://[^/]+/api/episode/[1-9][0-9]*$~', $url)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
}
