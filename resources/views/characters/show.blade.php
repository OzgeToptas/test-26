<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $character['name'] }} | Rick & Morty Encyclopedia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #101820;
            color: #f8fafc;
            font-family: system-ui, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #a3e635;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }

        .profile {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 35px;
            background: #1e293b;
            border-radius: 16px;
            padding: 25px;
        }

        .profile-image {
            width: 100%;
            border-radius: 12px;
        }

        h1 { font-size: 2.5rem; margin-top: 0; }
        h2 { margin-top: 40px; }

        .status {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            background: #334155;
            margin-bottom: 20px;
        }

        .status.alive { color: #a3e635; }
        .status.dead { color: #fca5a5; }

        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .label {
            display: block;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .value { font-size: 1.1rem; }

        .episodes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .episode {
            padding: 18px;
            background: #1e293b;
            border-radius: 10px;
            border: 1px solid #334155;
        }

        .episode-code {
            color: #a3e635;
            font-weight: bold;
        }

        .episode h3 { margin: 10px 0; }

        .air-date {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        @media (max-width: 650px) {
            .profile { grid-template-columns: 1fr; }
            .profile-image { max-width: 350px; }
            .details { grid-template-columns: 1fr; }
            h1 { font-size: 2rem; }
        }
    </style>
</head>

<body>
<main class="container">

    <a href="{{ route('characters.index') }}" class="back-link">
        ← Back to characters
    </a>

    <section class="profile">
        <div>
            <img
                src="{{ $character['image'] }}"
                alt="{{ $character['name'] }}"
                class="profile-image"
                width="300"
                height="300"
            >
        </div>

        <div>
            <h1>{{ $character['name'] }}</h1>

            <span class="status {{ strtolower($character['status']) }}">
                ● {{ $character['status'] }}
            </span>

            <div class="details">
                <div>
                    <span class="label">Species</span>
                    <span class="value">{{ $character['species'] }}</span>
                </div>

                <div>
                    <span class="label">Gender</span>
                    <span class="value">{{ $character['gender'] }}</span>
                </div>

                <div>
                    <span class="label">Origin</span>
                    <span class="value">{{ $character['origin'] }}</span>
                </div>

                <div>
                    <span class="label">Last known location</span>
                    <span class="value">{{ $character['location'] }}</span>
                </div>

                @if (!empty($character['type']))
                    <div>
                        <span class="label">Type</span>
                        <span class="value">{{ $character['type'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section aria-labelledby="episodes-heading">
        <h2 id="episodes-heading">
            Episodes ({{ count($character['episodes']) }})
        </h2>

        @if (empty($character['episodes']))
            <p>No episode information available.</p>
        @else
            <div class="episodes">
                @foreach ($character['episodes'] as $episode)
                    <article class="episode">
                        <span class="episode-code">
                            {{ $episode['episode'] }}
                        </span>

                        <h3>{{ $episode['name'] }}</h3>

                        <span class="air-date">
                            {{ $episode['air_date'] }}
                        </span>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

</main>
</body>
</html>