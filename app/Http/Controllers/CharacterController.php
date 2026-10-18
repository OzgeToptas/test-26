<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CharacterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class CharacterController extends Controller
{
    public function __construct(
        private readonly CharacterService $characters
    ) {}

    public function index(Request $request): View
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
        } catch (\Throwable $exception) {
            Log::error('Character listing failed', [
                'exception' => $exception,
            ]);

            return view('characters.index', [
                'characters' => [],
                'pagination' => [
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => $page,
                ],
                'filters' => $filters,
                'error' => 'Characters are temporarily unavailable. Please try again.',
            ]);
        }
    }

    public function show(int $id): View
    {
        try {
            $character = $this->characters->getCharacter($id);

            return view('characters.show', [
                'character' => $character,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Character details failed', [
                'id' => $id,
                'exception' => $exception,
            ]);

            abort(503, 'Character details are temporarily unavailable.');
        }
    }
}