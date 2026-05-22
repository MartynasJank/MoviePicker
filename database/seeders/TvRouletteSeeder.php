<?php

namespace Database\Seeders;

use App\Models\Roulette;
use Illuminate\Database\Seeder;

class TvRouletteSeeder extends Seeder
{
    public function run(): void
    {
        $genreLabel = [
            'action'      => 'Action & Adventure',
            'adventure'   => 'Adventure',
            'animation'   => 'Animation',
            'comedy'      => 'Comedy',
            'crime'       => 'Crime',
            'documentary' => 'Documentary',
            'drama'       => 'Drama',
            'family'      => 'Family',
            'fantasy'     => 'Fantasy',
            'history'     => 'History',
            'horror'      => 'Horror',
            'mystery'     => 'Mystery',
            'romance'     => 'Romance',
            'sci-fi'      => 'Sci-Fi & Fantasy',
            'thriller'    => 'Thriller',
            'war'         => 'War & Politics',
            'western'     => 'Western',
        ];

        $genreDesc = [
            'action'      => 'High-stakes missions, epic battles, and daring adventures across TV\'s best action shows.',
            'adventure'   => 'Epic quests, survival challenges, and daring journeys — the best adventure TV series.',
            'animation'   => 'Animated series for all ages — bold, inventive, and bursting with personality.',
            'comedy'      => 'Brilliant sitcoms, mockumentaries, and comedies guaranteed to make you laugh.',
            'crime'       => 'Detectives, heists, gangsters, and procedurals — the best crime TV has to offer.',
            'documentary' => 'Real stories told with compelling narratives — nature, true crime, and social issues.',
            'drama'       => 'Prestige storytelling, complex characters, and binge-worthy dramatic series.',
            'family'      => 'Wholesome series for all ages — heartfelt, fun, and perfect for family viewing.',
            'fantasy'     => 'Dragons, magic, prophecies, and otherworldly adventures — TV fantasy at its finest.',
            'history'     => 'Dramatic stories drawn from history — empires, wars, and the people who shaped the world.',
            'horror'      => 'Supernatural dread, psychological horror, and spine-chilling TV series.',
            'mystery'     => 'Whodunits, conspiracies, hidden clues, and satisfying reveals.',
            'romance'     => 'Love stories that warm your heart — from slow burns to grand gestures.',
            'sci-fi'      => 'Futuristic worlds, space exploration, alternate realities, and the boundaries of science.',
            'thriller'    => 'Pulse-pounding suspense, gut-punch twists, and edge-of-your-seat tension.',
            'war'         => 'Stories of conflict, politics, sacrifice, and the human cost of war.',
            'western'     => 'The frontier, gunslingers, lawmen, and outlaws — TV westerns at their finest.',
        ];

        $slugGenre = static fn(string $g) => str_replace('sci-fi', 'scifi', $g);

        // All 17 genres per platform, ordered by platform popularity (mirrors movie seeder)
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

        $slugOverrides = [
            'tv-netflix-documentary' => 'tv-netflix-docs',
            'tv-prime-thriller'      => 'tv-prime-thrillers',
        ];

        $customNames = [
            'tv-netflix-drama'    => 'Netflix Drama',
            'tv-netflix-crime'    => 'Netflix Crime',
            'tv-netflix-horror'   => 'Netflix Horror',
            'tv-netflix-docs'     => 'Netflix Documentaries',
            'tv-netflix-scifi'    => 'Netflix Sci-Fi & Fantasy',
            'tv-hbo-drama'        => 'HBO Drama',
            'tv-hbo-crime'        => 'HBO Crime',
            'tv-disney-family'    => 'Disney+ Family',
            'tv-apple-drama'      => 'Apple TV+ Drama',
        ];

        $customDescs = [
            'tv-netflix-drama'    => 'Award-winning Netflix originals and prestige dramas from around the world.',
            'tv-netflix-crime'    => 'True crime, police procedurals, and gripping crime dramas from Netflix.',
            'tv-netflix-horror'   => 'Supernatural scares, psychological horror, and international genre shows from Netflix.',
            'tv-netflix-docs'     => 'Acclaimed documentaries on crime, nature, culture, and social issues from Netflix.',
            'tv-netflix-scifi'    => 'Mind-bending science fiction and fantasy series from Netflix\'s genre catalogue.',
            'tv-hbo-drama'        => 'Prestige drama with complex characters and Emmy-worthy performances from HBO.',
            'tv-hbo-crime'        => 'Gritty crime series, detective shows, and dark procedurals from HBO.',
            'tv-disney-family'    => 'Family-friendly adventures, animated classics, and Disney originals for all ages.',
            'tv-apple-drama'      => 'Intimate, character-driven dramas from Apple TV+\'s celebrated originals catalogue.',
        ];

        $roulettes = [];

        // ── TV Eras (matches all movie decades) ──────────────────────────────
        $eraEntries = [
            ['name' => 'New on TV',         'slug' => 'tv-new-releases',   'description' => 'The hottest new series — shows that premiered in the last two years.',                                        'tags' => ['era' => ['recent']]],
            ['name' => 'TV of the 2020s',   'slug' => 'tv-2020s',          'description' => 'Contemporary television — from pandemic-era hits to today\'s must-watch originals.',                          'tags' => ['era' => ['2020s']]],
            ['name' => 'TV of the 2010s',   'slug' => 'tv-2010s',          'description' => 'The golden age of prestige TV — Game of Thrones, Breaking Bad, and the streaming revolution.',               'tags' => ['era' => ['2010s']]],
            ['name' => 'TV of the 2000s',   'slug' => 'tv-2000s',          'description' => 'The era of Lost, The Wire, and The Sopranos — the decade that proved TV could rival cinema.',                'tags' => ['era' => ['2000s']]],
            ['name' => 'TV of the 1990s',   'slug' => 'tv-1990s',          'description' => 'Seinfeld, Friends, The X-Files, Buffy — defining shows from a golden decade of television.',                 'tags' => ['era' => ['1990s']]],
            ['name' => 'TV of the 1980s',   'slug' => 'tv-1980s',          'description' => 'Dallas, Miami Vice, Cheers, ALF — iconic series from the decade that built modern television.',              'tags' => ['era' => ['1980s']]],
            ['name' => 'TV of the 1970s',   'slug' => 'tv-1970s',          'description' => 'All in the Family, MASH, Columbo — groundbreaking television from the 1970s.',                              'tags' => ['era' => ['1970s']]],
            ['name' => 'TV of the 1960s',   'slug' => 'tv-1960s',          'description' => 'Star Trek, The Twilight Zone, Batman — the early classics that defined what TV could be.',                   'tags' => ['era' => ['1960s']]],
            ['name' => 'TV of the 1950s',   'slug' => 'tv-1950s',          'description' => 'I Love Lucy, Dragnet, The Honeymooners — the earliest era of episodic television.',                         'tags' => ['era' => ['1950s']]],
            ['name' => 'Classic TV',        'slug' => 'tv-classic',        'description' => 'Television\'s earliest broadcasts — rare and historic shows from the dawn of the medium.',                   'tags' => ['era' => ['pre-1950']]],
        ];
        foreach ($eraEntries as $so => $entry) {
            $roulettes[] = array_merge($entry, ['sort_order' => $so]);
        }

        // ── Platform × Genre (all 17 genres each) ────────────────────────────
        foreach ($platforms as $platformKey => $config) {
            $so = ($platformKey === 'apple') ? 1 : 0;
            foreach ($config['genres'] as $genre) {
                $generatedSlug = 'tv-' . $platformKey . '-' . $slugGenre($genre);
                $slug = $slugOverrides[$generatedSlug] ?? $generatedSlug;
                $roulettes[] = [
                    'name'        => $customNames[$slug]  ?? ($config['label'] . ' ' . $genreLabel[$genre]),
                    'slug'        => $slug,
                    'description' => $customDescs[$slug]  ?? $genreDesc[$genre],
                    'tags'        => ['platform' => [$platformKey], 'genre' => [$genre]],
                    'sort_order'  => $so++,
                ];
            }
        }

        // ── Special platform entries ──────────────────────────────────────────
        $roulettes[] = [
            'name'        => 'Apple TV+ Originals',
            'slug'        => 'tv-apple-originals',
            'description' => 'Award-winning originals — from intimate dramas to gripping sci-fi, Apple TV+ at its best.',
            'tags'        => ['platform' => ['apple']],
            'sort_order'  => 0,
        ];
        $roulettes[] = [
            'name'        => 'Netflix Anime Series',
            'slug'        => 'tv-netflix-anime',
            'description' => 'Action, fantasy, isekai, and drama — a rich selection of anime series from Netflix.',
            'tags'        => ['platform' => ['netflix'], 'genre' => ['animation'], 'language' => ['ja']],
            'sort_order'  => 18,
        ];

        // Remove any World TV entries that are no longer in the list (e.g. Scandinavian was replaced)
        Roulette::whereIn('slug', ['tv-scandi-noir'])->delete();

        // ── World TV (matches World Cinema order: ko, ja, fr, es, it, zh, hi, de, tr, pt, lt) ───
        $worldTv = [
            ['name' => 'Korean Dramas',     'slug' => 'tv-korean',     'lang' => 'ko', 'desc' => 'Romance, thrillers, and slice-of-life — the best K-dramas captivating the world.'],
            ['name' => 'Japanese Series',   'slug' => 'tv-japanese',   'lang' => 'ja', 'desc' => 'J-dramas, anime, and live-action adaptations — diverse and compelling Japanese TV.'],
            ['name' => 'French Series',     'slug' => 'tv-french',     'lang' => 'fr', 'desc' => 'Stylish crime thrillers, comedies, and prestige drama from French television.'],
            ['name' => 'Spanish Series',    'slug' => 'tv-spanish',    'lang' => 'es', 'desc' => 'Gripping crime dramas, telenovelas, and originals from the Spanish-speaking world.'],
            ['name' => 'Italian Series',    'slug' => 'tv-italian',    'lang' => 'it', 'desc' => 'Crime dramas, comedies, and sharp social satire from Italian television.'],
            ['name' => 'Chinese Series',    'slug' => 'tv-chinese',    'lang' => 'zh', 'desc' => 'Historical epics, wuxia, and contemporary dramas from Chinese television.'],
            ['name' => 'Hindi Series',      'slug' => 'tv-hindi',      'lang' => 'hi', 'desc' => 'Vibrant drama, romance, and spectacle — the best of Hindi television.'],
            ['name' => 'German Series',     'slug' => 'tv-german',     'lang' => 'de', 'desc' => 'Dark, atmospheric, and gripping — from Dark to crime procedurals, German TV at its best.'],
            ['name' => 'Turkish Series',    'slug' => 'tv-turkish',    'lang' => 'tr', 'desc' => 'Epic historical dramas, romance, and gripping thrillers from Turkey.'],
            ['name' => 'Portuguese Series', 'slug' => 'tv-portuguese', 'lang' => 'pt', 'desc' => 'Brazilian and Portuguese TV — from vibrant dramas to compelling originals.'],
            ['name' => 'Lithuanian Series', 'slug' => 'tv-lithuanian', 'lang' => 'lt', 'desc' => 'Local dramas, crime series, and quietly powerful stories from Lithuanian television.'],
        ];
        foreach ($worldTv as $so => $entry) {
            $roulettes[] = [
                'name'        => $entry['name'],
                'slug'        => $entry['slug'],
                'description' => $entry['desc'],
                'tags'        => ['language' => [$entry['lang']]],
                'sort_order'  => $so,
                'row'         => 'World TV',
            ];
        }

        // ── Anime Series (all genres, mirrors movie seeder) ───────────────────
        $animeGenres = ['action', 'fantasy', 'adventure', 'drama', 'sci-fi', 'comedy', 'romance', 'horror', 'thriller', 'mystery', 'crime', 'family', 'history', 'war', 'western', 'documentary'];
        $animeDescs  = [
            'action'      => 'Epic shonen battles, tournament arcs, and unstoppable heroes — anime action series.',
            'fantasy'     => 'Magic, dragons, isekai, and grand adventures — anime fantasy at its finest.',
            'adventure'   => 'Epic quests, perilous journeys, and unforgettable anime adventures.',
            'drama'       => 'Emotionally powerful stories and complex characters — anime drama that stays with you.',
            'sci-fi'      => 'Mechs, dystopias, and mind-bending futures — the best sci-fi anime series.',
            'comedy'      => 'Absurd, heartwarming, and hilarious — the funniest anime series.',
            'romance'     => 'Tender love stories and bittersweet emotions — anime romance series.',
            'horror'      => 'Supernatural dread, psychological scares, and dark atmospheres — anime horror series.',
            'thriller'    => 'Suspense, twists, and high-stakes tension — gripping anime thriller series.',
            'mystery'     => 'Hidden truths, clever detectives, and satisfying reveals — anime mystery series.',
            'crime'       => 'Heists, gang wars, and criminal underworlds — anime crime series.',
            'family'      => 'Heartwarming adventures and wholesome stories — anime for all ages.',
            'history'     => 'Samurai, feudal Japan, and historical epics — anime history series.',
            'war'         => 'Conflict, sacrifice, and the human cost of battle — anime war series.',
            'western'     => 'Gunslingers and outlaws reimagined through anime — a rare and striking genre.',
            'documentary' => 'Behind-the-scenes, nature, and real-world stories told through animation.',
        ];

        $roulettes[] = [
            'name'        => 'Anime Series',
            'slug'        => 'tv-anime',
            'description' => 'The best of Japanese animated series — from beloved classics to modern seasonal hits.',
            'tags'        => ['genre' => ['animation'], 'language' => ['ja']],
            'sort_order'  => 0,
        ];
        foreach ($animeGenres as $so => $genre) {
            $roulettes[] = [
                'name'        => 'Anime ' . $genreLabel[$genre],
                'slug'        => 'tv-anime-' . $slugGenre($genre),
                'description' => $animeDescs[$genre],
                'tags'        => ['genre' => ['animation', $genre], 'language' => ['ja']],
                'sort_order'  => $so + 1,
            ];
        }

        // ── Standalone TV Genres (all 17, mirrors movie seeder) ───────────────
        $standaloneGenres = ['action', 'adventure', 'animation', 'comedy', 'crime', 'documentary', 'drama', 'family', 'fantasy', 'history', 'horror', 'mystery', 'romance', 'sci-fi', 'thriller', 'war', 'western'];
        $standaloneNames  = [
            'crime'   => 'Crime Series',
            'romance' => 'Feel-Good Romance',
            'sci-fi'  => 'Sci-Fi & Fantasy Series',
            'action'  => 'Action & Adventure Series',
            'war'     => 'War & Politics Series',
        ];
        $standaloneSlugs  = [
            'crime'   => 'tv-genre-crime',
            'romance' => 'tv-genre-romance',
            'sci-fi'  => 'tv-genre-scifi',
            'action'  => 'tv-genre-action',
            'war'     => 'tv-genre-war',
        ];
        foreach ($standaloneGenres as $so => $genre) {
            $roulettes[] = [
                'name'        => $standaloneNames[$genre] ?? $genreLabel[$genre] . ' Series',
                'slug'        => $standaloneSlugs[$genre] ?? 'tv-genre-' . $slugGenre($genre),
                'description' => $genreDesc[$genre],
                'tags'        => ['genre' => [$genre]],
                'sort_order'  => $so,
            ];
        }

        // ── Persist ───────────────────────────────────────────────────────────
        foreach ($roulettes as $data) {
            $data['tag_fingerprint'] = 'tv:' . Roulette::fingerprintFromTags($data['tags']);
            $data['is_system']       = true;
            $data['is_public']       = true;
            $data['media_type']      = 'tv';

            Roulette::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
