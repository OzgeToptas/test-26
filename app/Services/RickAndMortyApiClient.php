<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class RickAndMortyApiClient
{
    public function get(string $endpoint, array $query = []): array
    {
        // Only allow known API endpoints.
        if (! preg_match(
            '~^(character|episode|location)(?:/[0-9,]+)?$~',
            $endpoint
        )) {
            throw new RuntimeException('Invalid API endpoint.');
        }

        $baseUrl = rtrim(
            config('services.rickandmorty.url'),
            '/'
        );

        $url = $baseUrl . '/' . $endpoint;

        // Stable cache keys regardless of query parameter order.
        ksort($query);

        $cacheKey = 'rickandmorty:' . hash(
            'sha256',
            $endpoint . '?' . http_build_query($query)
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

                    if ($response->status() === 404) {
                        throw new RuntimeException(
                            'Resource not found.',
                            404
                        );
                    }

                    if ($response->status() === 429) {
                        throw new RuntimeException(
                            'API rate limit exceeded.',
                            429
                        );
                    }

                    $response->throw();

                    $data = $response->json();

                    if (! is_array($data)) {
                        throw new RuntimeException(
                            'Invalid API response.',
                            502
                        );
                    }

                    return $data;
                } catch (ConnectionException $e) {
                    Log::warning('Rick and Morty API connection failed', [
                        'message' => $e->getMessage(),
                    ]);

                    throw new RuntimeException(
                        'Rick and Morty API is unavailable.',
                        503,
                        $e
                    );
                } catch (RequestException $e) {
                    Log::warning('Rick and Morty API request failed', [
                        'status' => $e->response->status(),
                    ]);

                    throw new RuntimeException(
                        'Rick and Morty API request failed.',
                        502,
                        $e
                    );
                }
            }
        );
    }
}