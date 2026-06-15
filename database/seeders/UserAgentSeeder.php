<?php

namespace Database\Seeders;

use App\Models\PageView;
use App\Models\TmdbRequestLog;
use Illuminate\Database\Seeder;

class UserAgentSeeder extends Seeder
{
    private array $humanUAs = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.113 Mobile Safari/537.36',
        'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 OPR/111.0.0.0',
    ];

    private array $botUAs = [
        'Googlebot'              => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Bingbot'                => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'AhrefsBot'              => 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        'SemrushBot'             => 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)',
        'DotBot'                 => 'Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot)',
        'MJ12bot'                => 'Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)',
        'no-accept-language'     => 'python-requests/2.31.0',
        'no-accept-language'     => 'Go-http-client/1.1',
        'no-accept-language'     => 'curl/8.4.0',
        'no-accept-language'     => 'axios/1.6.8',
    ];

    public function run(): void
    {
        // Update page_views: assign a UA per visitor_hash so each visitor is consistent
        $hashes = PageView::whereNull('user_agent')
            ->select('visitor_hash', 'bot')
            ->distinct()
            ->get();

        foreach ($hashes as $row) {
            $ua = $this->uaForBot($row->bot);
            PageView::where('visitor_hash', $row->visitor_hash)
                ->whereNull('user_agent')
                ->update(['user_agent' => $ua]);
        }

        // Also patch any null-hash rows
        PageView::whereNull('visitor_hash')->whereNull('user_agent')->update([
            'user_agent' => $this->randomHuman(),
        ]);

        // Update tmdb_request_logs the same way
        $hashes = TmdbRequestLog::whereNull('user_agent')
            ->select('visitor_hash', 'bot')
            ->distinct()
            ->get();

        foreach ($hashes as $row) {
            $ua = $this->uaForBot($row->bot);
            TmdbRequestLog::where('visitor_hash', $row->visitor_hash)
                ->whereNull('user_agent')
                ->update(['user_agent' => $ua]);
        }

        TmdbRequestLog::whereNull('visitor_hash')->whereNull('user_agent')->update([
            'user_agent' => $this->randomHuman(),
        ]);

        $pv  = PageView::whereNotNull('user_agent')->count();
        $trl = TmdbRequestLog::whereNotNull('user_agent')->count();
        $this->command->info("Seeded UAs: {$pv} page_views, {$trl} tmdb_request_logs");
    }

    private function uaForBot(?string $bot): string
    {
        if (!$bot) {
            return $this->randomHuman();
        }

        foreach ($this->botUAs as $name => $ua) {
            if (str_contains($bot, $name) || str_contains($name, $bot)) {
                return $ua;
            }
        }

        // Generic no-accept-language / unknown bot
        return collect([
            'python-requests/2.31.0',
            'Go-http-client/1.1',
            'curl/8.4.0',
            'axios/1.6.8',
            'node-fetch/3.3.2',
        ])->random();
    }

    private function randomHuman(): string
    {
        return $this->humanUAs[array_rand($this->humanUAs)];
    }
}
