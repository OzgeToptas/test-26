<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rick & Morty Encyclopedia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #101820;
            color: #f8fafc;
            font-family: system-ui, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px;
        }

        header { text-align: center; margin-bottom: 28px; }
        h1 { font-size: clamp(1.75rem, 4vw, 2.5rem); margin: 0 0 12px; }
        header p { margin: 0; color: #a9b8c9; line-height: 1.6; }

        .filters {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1.2fr) minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            padding: 16px;
            border: 1px solid #2d3d50;
            border-radius: 14px;
            background: #15202c;
        }

        .filters input, .filters select, .filters button, .filter-reset {
            min-width: 0;
            min-height: 44px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #42536a;
            font: inherit;
            font-size: 14px;
            line-height: 1.4;
        }

        .filters input, .filters select {
            width: 100%;
            background: #1c2939;
            color: #f1f5f9;
            color-scheme: dark;
        }

        .filters input::placeholder { color: #a9b8c9; opacity: 1; }
        .filters input:hover, .filters select:hover { border-color: #71849c; }

        .filters button, .filter-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .filters button {
            background: #a3cf59;
            border-color: #a3cf59;
            color: #14200c;
        }

        .filters button:hover { background: #b5df70; border-color: #b5df70; }
        .filter-reset { background: transparent; color: #dbe4ef; }
        .filter-reset:hover { background: #26374b; border-color: #71849c; }

        .filters input:focus-visible, .filters select:focus-visible,
        .filters button:focus-visible, .filter-reset:focus-visible {
            outline: 3px solid #b5df70;
            outline-offset: 3px;
        }

        @media (max-width: 900px) {
            .filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 480px) {
            .container { padding: 28px 16px; }
            .filters { grid-template-columns: minmax(0, 1fr); padding: 12px; }
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            transition: transform .2s;
        }

        .card:hover { transform: translateY(-5px); }

        .card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }

        .card-content { padding: 16px; }
        .card a { color: white; text-decoration: none; }
        .card a:hover { color: #a3e635; }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 35px;
        }

        .pagination a, .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            background: #1e293b;
            color: white;
            text-decoration: none;
        }

        .pagination .active {
            background: #a3e635;
            color: #111827;
        }

        .error {
            padding: 16px;
            background: #7f1d1d;
            border-radius: 8px;
        }

        .empty { text-align: center; padding: 40px; }
    </style>
</head>

<body>
<div class="container">

    <header>
        <h1>🛸 Rick & Morty Encyclopedia</h1>
        <p>Explore characters across the multiverse</p>
    </header>

    <main>
        <form method="GET" action="{{ route('characters.index') }}" class="filters">
            <input
                type="search"
                name="name"
                placeholder="Search characters..."
                aria-label="Character name"
                maxlength="100"
                value="{{ $filters['name'] ?? '' }}"
            >

            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                @foreach (['alive' => 'Alive', 'dead' => 'Dead', 'unknown' => 'Unknown'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input
                type="search"
                name="species"
                placeholder="Species..."
                aria-label="Species"
                maxlength="100"
                value="{{ $filters['species'] ?? '' }}"
            >

            <select name="gender" aria-label="Gender">
                <option value="">All genders</option>
                @foreach (['male' => 'Male', 'female' => 'Female', 'genderless' => 'Genderless', 'unknown' => 'Unknown'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['gender'] ?? '') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Search</button>
            <a href="{{ route('characters.index') }}" class="filter-reset">
                Reset
            </a>
        </form>

        @if ($errors->any())
            <div class="error" role="alert">
                Please check your search or filter values.
            </div>
        @endif

        @if ($error)
            <div class="error" role="alert">{{ $error }}</div>
        @elseif (count($characters) === 0)
            <div class="empty">
                <h2>No characters found</h2>
                <p>Try changing your search or filters.</p>
            </div>
        @else
            <p>{{ $pagination['total'] }} characters found</p>

            <div class="grid">
                @foreach ($characters as $character)
                    <article class="card">
                        <a href="{{ route('characters.show', $character['id']) }}">
                            <img
                                src="{{ $character['image'] }}"
                                alt="{{ $character['name'] }}"
                                loading="lazy"
                                width="300"
                                height="300"
                            >
                            <div class="card-content">
                                <h2>{{ $character['name'] }}</h2>
                                <p>{{ $character['species'] }}</p>
                                <p>{{ $character['status'] }} · {{ $character['gender'] }}</p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($pagination['pages'] > 1)
                <nav class="pagination" aria-label="Character pages">
                    @if ($pagination['current_page'] > 1)
                        <a href="{{ route('characters.index', array_merge($filters, ['page' => $pagination['current_page'] - 1])) }}">
                            ← Previous
                        </a>
                    @endif

                    <span class="active" aria-current="page">
                        Page {{ $pagination['current_page'] }} of {{ $pagination['pages'] }}
                    </span>

                    @if ($pagination['current_page'] < $pagination['pages'])
                        <a href="{{ route('characters.index', array_merge($filters, ['page' => $pagination['current_page'] + 1])) }}">
                            Next →
                        </a>
                    @endif
                </nav>
            @endif
        @endif
    </main>
</div>
</body>
</html>