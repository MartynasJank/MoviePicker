<?php

namespace Database\Seeders;

use App\Models\Roulette;
use Illuminate\Database\Seeder;

class RouletteSeeder extends Seeder
{
    public function run(): void
    {
        $genreLabel = [
            'action'      => 'Action',      'adventure'   => 'Adventure',
            'animation'   => 'Animation',   'comedy'      => 'Comedy',
            'crime'       => 'Crime',       'documentary' => 'Documentary',
            'drama'       => 'Drama',       'family'      => 'Family',
            'fantasy'     => 'Fantasy',     'history'     => 'History',
            'horror'      => 'Horror',      'mystery'     => 'Mystery',
            'romance'     => 'Romance',     'sci-fi'      => 'Sci-Fi',
            'thriller'    => 'Thriller',    'war'         => 'War',
            'western'     => 'Western',
        ];

        $genreDesc = [
            'action'      => 'High-octane stunts, chases, and heroes saving the day.',
            'adventure'   => 'Epic journeys, daring quests, and bigger-than-life heroes.',
            'animation'   => 'Animated films for all ages — heartwarming, hilarious, and breathtaking.',
            'comedy'      => 'Guaranteed laughs — from dry wit to full-blown absurdity.',
            'crime'       => 'Heists, detectives, gangsters, and criminal masterminds.',
            'documentary' => 'Real stories that inform, inspire, and sometimes shock.',
            'drama'       => 'Powerful stories and complex human experiences.',
            'family'      => 'Wholesome fun and heartfelt stories for all ages.',
            'fantasy'     => 'Dragons, magic, prophecies, and otherworldly adventures.',
            'history'     => 'Dramatic stories drawn from the pages of history.',
            'horror'      => 'Spine-chilling scares, supernatural dread, and psychological horror.',
            'mystery'     => 'Whodunits, hidden clues, and satisfying reveals.',
            'romance'     => 'Love stories to warm your heart — or make it ache.',
            'sci-fi'      => 'Futuristic worlds, space exploration, and the limits of imagination.',
            'thriller'    => 'Edge-of-your-seat tension, twists, and pulse-pounding suspense.',
            'war'         => 'Stories of conflict, heroism, sacrifice, and the human cost of war.',
            'western'     => 'Gunslingers, outlaws, lawmen, and the wide-open frontier.',
        ];

        $slugGenre = fn(string $g) => str_replace('sci-fi', 'scifi', $g);

        // Genres per platform in popularity order for that platform
        $platforms = [
            'netflix' => [
                'label'  => 'Netflix',
                'genres' => ['drama', 'comedy', 'thriller', 'action', 'romance', 'horror', 'sci-fi', 'documentary', 'crime', 'mystery', 'fantasy', 'animation', 'adventure', 'family', 'history', 'war', 'western'],
            ],
            'prime' => [
                'label'  => 'Prime Video',
                'genres' => ['action', 'drama', 'thriller', 'comedy', 'horror', 'sci-fi', 'adventure', 'romance', 'documentary', 'crime', 'mystery', 'fantasy', 'family', 'history', 'animation', 'war', 'western'],
            ],
            'hbo' => [
                'label'  => 'HBO',
                'genres' => ['drama', 'thriller', 'crime', 'comedy', 'action', 'horror', 'sci-fi', 'romance', 'documentary', 'mystery', 'adventure', 'fantasy', 'history', 'family', 'war', 'animation', 'western'],
            ],
            'disney' => [
                'label'  => 'Disney+',
                'genres' => ['family', 'action', 'adventure', 'animation', 'fantasy', 'comedy', 'sci-fi', 'drama', 'romance', 'mystery', 'history', 'documentary', 'thriller', 'crime', 'war', 'horror', 'western'],
            ],
            'apple' => [
                'label'  => 'Apple TV+',
                'genres' => ['drama', 'thriller', 'sci-fi', 'action', 'comedy', 'romance', 'mystery', 'documentary', 'adventure', 'crime', 'fantasy', 'history', 'family', 'animation', 'war', 'horror', 'western'],
            ],
        ];

        // Generated slug → existing DB slug (preserves bookmarkable URLs)
        $slugOverrides = [
            'netflix-documentary' => 'netflix-docs',
            'prime-thriller'      => 'prime-thrillers',
        ];

        // Custom names for entries with existing branding
        $customNames = [
            'netflix-horror'  => 'Netflix Horror',
            'netflix-docs'    => 'Netflix Documentaries',
            'netflix-scifi'   => 'Netflix Sci-Fi',
            'prime-thrillers' => 'Prime Thrillers',
            'prime-comedy'    => 'Prime Comedy',
            'hbo-drama'       => 'HBO Drama',
            'disney-family'   => 'Disney+ Family',
        ];

        // Custom descriptions for entries with existing copy
        $customDescs = [
            'netflix-horror'  => 'Supernatural encounters, psychological thrillers, and international scares from Netflix\'s horror selection.',
            'netflix-docs'    => 'True crime, environmental issues, social justice, and historical events — told with compelling narratives.',
            'netflix-scifi'   => 'Space exploration, dystopian futures, and mind-bending concepts from Netflix\'s science fiction catalogue.',
            'prime-thrillers' => 'Edge-of-your-seat suspense, gut-punch twists, and slow-burn tension from Prime Video.',
            'prime-comedy'    => 'Laughs guaranteed — rom-coms, absurdist gems, and crowd-pleasers from Prime Video.',
            'hbo-drama'       => 'Prestige storytelling, complex characters, and Emmy-worthy performances from HBO.',
            'disney-family'   => 'Animated classics, live-action adventures, and fun for all ages from Disney+.',
        ];

        $roulettes = [];

        // ── Era: newest to oldest ─────────────────────────────────────────────
        $roulettes[] = ['name' => 'New Releases',     'slug' => 'new-releases',     'description' => 'Hot off the press — films from the last two years.',                                           'tags' => ['era' => ['recent']]];
        $roulettes[] = ['name' => 'The 2020s',        'slug' => '2020s-picks',      'description' => 'Contemporary cinema — from pandemic-era stories to today\'s global hits.',                    'tags' => ['era' => ['2020s']]];
        $roulettes[] = ['name' => 'The 2010s',        'slug' => '2010s-picks',      'description' => 'A defining decade of superhero epics, streaming originals, and award-winning indie hits.',    'tags' => ['era' => ['2010s']]];
        $roulettes[] = ['name' => 'The 2000s',        'slug' => '2000s-gems',       'description' => 'Overlooked and beloved films from the early 2000s you might have missed.',                   'tags' => ['era' => ['2000s']]];
        $roulettes[] = ['name' => 'The Nineties',     'slug' => '90s-nostalgia',    'description' => 'Blockbusters and indie gems from cinema\'s golden decade — grunge, CGI breakthroughs, and classics.', 'tags' => ['era' => ['1990s']]];
        $roulettes[] = ['name' => 'The Eighties',     'slug' => '80s-classics',     'description' => 'Iconic films that defined a decade — blockbuster action, cult horror, and coming-of-age gems.', 'tags' => ['era' => ['1980s']]];
        $roulettes[] = ['name' => 'The Seventies',    'slug' => '70s-picks',        'description' => 'Raw, gritty, and groundbreaking — the golden age of New Hollywood.',                         'tags' => ['era' => ['1970s']]];
        $roulettes[] = ['name' => 'The Sixties',      'slug' => '60s-picks',        'description' => 'Revolution on and off screen — the bold, rule-breaking cinema of the 1960s.',                'tags' => ['era' => ['1960s']]];
        $roulettes[] = ['name' => 'The Fifties',      'slug' => '50s-picks',        'description' => 'Post-war optimism, sci-fi paranoia, and enduring beauty — cinema of the 1950s.',              'tags' => ['era' => ['1950s']]];
        $roulettes[] = ['name' => 'Classic Hollywood','slug' => 'classic-hollywood','description' => 'Golden age masterpieces — timeless cinema from the earliest era of film.',                    'tags' => ['era' => ['pre-1950']]];

        // ── Platform × Genre (all 17 genres each) ────────────────────────────
        foreach ($platforms as $platformKey => $config) {
            foreach ($config['genres'] as $genre) {
                $generatedSlug = $platformKey . '-' . $slugGenre($genre);
                $slug = $slugOverrides[$generatedSlug] ?? $generatedSlug;
                $roulettes[] = [
                    'name'        => $customNames[$slug]  ?? ($config['label'] . ' ' . $genreLabel[$genre]),
                    'slug'        => $slug,
                    'description' => $customDescs[$slug]  ?? $genreDesc[$genre],
                    'tags'        => ['platform' => [$platformKey], 'genre' => [$genre]],
                ];
            }
        }

        // ── Special platform entries (unique tag combinations) ────────────────
        $roulettes[] = [
            'name'        => 'Netflix Anime Movies',
            'slug'        => 'netflix-anime',
            'description' => 'Action, fantasy, romance, sci-fi — a rich selection of anime films spanning classics and Netflix originals.',
            'tags'        => ['platform' => ['netflix'], 'genre' => ['animation'], 'language' => ['ja']],
        ];
        $roulettes[] = [
            'name'        => 'Apple TV+ Originals',
            'slug'        => 'apple-originals',
            'description' => 'Award-winning originals from Apple\'s growing library — from intimate dramas to epic sci-fi.',
            'tags'        => ['platform' => ['apple']],
        ];

        // ── World Cinema ──────────────────────────────────────────────────────
        $worldCinema = [
            ['name' => 'Korean Cinema',      'slug' => 'korean-cinema',      'lang' => 'ko', 'desc' => 'From revenge thrillers to romantic dramas — the best of Korean film, new wave and beyond.'],
            ['name' => 'Japanese Cinema',    'slug' => 'japanese-cinema',    'lang' => 'ja', 'desc' => 'Art house gems, animated masterpieces, and genre-defining classics from Japan.'],
            ['name' => 'French Cinema',      'slug' => 'french-cinema',      'lang' => 'fr', 'desc' => 'Romance, philosophy, and cinematic flair — from the French New Wave to modern auteurs.'],
            ['name' => 'Spanish Cinema',     'slug' => 'spanish-cinema',     'lang' => 'es', 'desc' => 'Passion, intensity, and dark humour — the best of Spanish-language film.'],
            ['name' => 'Italian Cinema',     'slug' => 'italian-cinema',     'lang' => 'it', 'desc' => 'From neorealism to Fellini and beyond — the enduring richness of Italian film.'],
            ['name' => 'Chinese Cinema',     'slug' => 'chinese-cinema',     'lang' => 'zh', 'desc' => 'Epic historical dramas, martial arts, and contemporary art house from China.'],
            ['name' => 'Bollywood',          'slug' => 'bollywood',          'lang' => 'hi', 'desc' => 'Colour, music, drama, and spectacle — the infectious energy of Hindi cinema.'],
            ['name' => 'German Cinema',      'slug' => 'german-cinema',      'lang' => 'de', 'desc' => 'From Expressionism to New German Cinema — powerful and distinctive storytelling.'],
            ['name' => 'Turkish Cinema',     'slug' => 'turkish-cinema',     'lang' => 'tr', 'desc' => 'A rising force in world cinema — gripping dramas and genre films from Turkey.'],
            ['name' => 'Portuguese Cinema',  'slug' => 'portuguese-cinema',  'lang' => 'pt', 'desc' => 'Brazilian and Portuguese film — from vibrant dramas to quiet, moving art house.'],
            ['name' => 'Lithuanian Cinema',  'slug' => 'lithuanian-cinema',  'lang' => 'lt', 'desc' => 'Intimate dramas, dark comedies, and quietly powerful stories from Lithuania.'],
        ];
        foreach ($worldCinema as $entry) {
            $roulettes[] = [
                'name'        => $entry['name'],
                'slug'        => $entry['slug'],
                'description' => $entry['desc'],
                'tags'        => ['language' => [$entry['lang']]],
            ];
        }

        // ── Anime (animation + Japanese language, sorted by popularity) ──────
        $animeGenres = ['action', 'fantasy', 'adventure', 'drama', 'sci-fi', 'comedy', 'romance', 'horror', 'thriller', 'mystery', 'crime', 'family', 'history', 'war', 'western', 'documentary'];
        $animeDescs  = [
            'action'      => 'High-energy battles, epic heroes, and relentless action — the best anime action films.',
            'fantasy'     => 'Dragons, magic, and otherworldly adventures — anime fantasy at its finest.',
            'adventure'   => 'Epic quests, unforgettable journeys, and daring heroes in animated form.',
            'drama'       => 'Emotionally powerful stories and complex characters — anime drama that stays with you.',
            'sci-fi'      => 'Mechs, dystopias, and mind-bending futures — anime science fiction.',
            'comedy'      => 'Hilarious, absurd, and heartwarming — the funniest anime films.',
            'romance'     => 'Tender love stories and bittersweet emotions — anime romance.',
            'horror'      => 'Supernatural dread, psychological scares, and dark atmospheres — anime horror.',
            'thriller'    => 'Suspense, twists, and pulse-pounding tension — anime thrillers.',
            'mystery'     => 'Hidden truths, clever detectives, and satisfying reveals — anime mystery.',
            'crime'       => 'Heists, gang wars, and criminal underworlds — anime crime films.',
            'family'      => 'Heartwarming stories for all ages — classic and modern anime family films.',
            'history'     => 'Samurai, feudal Japan, and historical epics — anime history.',
            'war'         => 'Conflict, sacrifice, and the human cost of battle — anime war films.',
            'western'     => 'Gunslingers and outlaws reimagined through anime — a rare and striking genre.',
            'documentary' => 'Behind-the-scenes, nature, and real-world stories told through animation.',
        ];

        // General anime entry (all animation, Japanese language)
        $roulettes[] = [
            'name'        => 'Anime Films',
            'slug'        => 'anime',
            'description' => 'The best of Japanese animation — from Studio Ghibli classics to modern hits across every genre.',
            'tags'        => ['genre' => ['animation'], 'language' => ['ja']],
        ];

        foreach ($animeGenres as $genre) {
            $roulettes[] = [
                'name'        => 'Anime ' . $genreLabel[$genre],
                'slug'        => 'anime-' . $slugGenre($genre),
                'description' => $animeDescs[$genre],
                'tags'        => ['genre' => ['animation', $genre], 'language' => ['ja']],
            ];
        }

        // ── Standalone Genres ─────────────────────────────────────────────────
        $standaloneGenres = ['action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 'drama', 'family', 'fantasy', 'history', 'horror', 'mystery', 'romance', 'sci-fi', 'thriller', 'war', 'western'];
        $standaloneNames  = ['crime' => 'Crime & Heist', 'romance' => 'Feel-Good Romance'];
        $standaloneSlugs  = ['crime' => 'crime-heist',   'romance' => 'feel-good-romance'];
        foreach ($standaloneGenres as $genre) {
            $roulettes[] = [
                'name'        => $standaloneNames[$genre] ?? $genreLabel[$genre] . ' Films',
                'slug'        => $standaloneSlugs[$genre] ?? 'genre-' . $slugGenre($genre),
                'description' => $genreDesc[$genre],
                'tags'        => ['genre' => [$genre]],
            ];
        }

        // ── Persist ───────────────────────────────────────────────────────────
        foreach ($roulettes as $data) {
            $data['tag_fingerprint'] = Roulette::fingerprintFromTags($data['tags']);
            $data['is_system']       = true;
            $data['is_public']       = true;

            Roulette::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}