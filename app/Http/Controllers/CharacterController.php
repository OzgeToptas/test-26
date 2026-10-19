<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\RickAndMortyApiException;
use App\Services\CharacterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class CharacterController extends Controller
{
    public function __construct(
        private readonly CharacterService $characters
    ) {}

    /**
     * Display a paginated list of characters.
     *
     * Supports searching and filtering.
     */
    public function index(Request $request): View|Response
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:alive,dead,unknown'],
            'species' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female,genderless,unknown'],
        ]);

        $page = (int) ($filters['page'] ?? 1);

        try {
            $data = $this->characters->getCharacters(
                $page,
                $filters
            );

            return view('characters.index', [
                ...$data,
                'filters' => $filters,
                'error' => null,
            ]);

        } catch (RickAndMortyApiException $exception) {
            Log::error('Character listing failed', [
                'status' => $exception->statusCode(),
                'message' => $exception->getMessage(),
            ]);

            // Return HTTP 503 when the external API is unavailable.
            return response()->view('characters.index', [
                'characters' => [],
                'pagination' => [
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => $page,
                ],
                'filters' => $filters,
                'error' => 'Characters are temporarily unavailable. Please try again.',
            ], 503);
        }
    }

    /**
     * Display an individual character and their episodes.
     */
    public function show(int $id): View
    {
        try {
            $character = $this->characters->getCharacter($id);

            return view('characters.show', [
                'character' => $character,
            ]);

        } catch (RickAndMortyApiException $exception) {

            // Return HTTP 404 when the character does not exist.
            if ($exception->statusCode() === 404) {
                abort(404, 'Character not found.');
            }

            Log::error('Character details failed', [
                'id' => $id,
                'status' => $exception->statusCode(),
                'message' => $exception->getMessage(),
            ]);

            // Return HTTP 503 for external API failures.
            abort(
                503,
                'Character details are temporarily unavailable.'
            );
        }
    }
}
