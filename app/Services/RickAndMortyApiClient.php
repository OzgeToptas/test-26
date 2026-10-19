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
            function () use ($url, $query): array {
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
                    if (! is_array($data)) {
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
}
