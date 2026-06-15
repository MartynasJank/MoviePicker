<?php

namespace App\Support;

use Illuminate\Http\Request;

class BotDetector
{
    public static function detect(Request $request): ?string
    {
        try {
            $ua = $request->userAgent() ?? '';
            if ($ua === '') return 'no-ua';

            $patterns = [
                '/Googlebot/i'           => 'Googlebot',
                '/Bingbot/i'             => 'Bingbot',
                '/DuckDuckBot/i'         => 'DuckDuckBot',
                '/YandexBot/i'           => 'YandexBot',
                '/Baiduspider/i'         => 'Baiduspider',
                '/Slurp/i'               => 'Yahoo-Slurp',
                '/facebookexternalhit/i' => 'FacebookBot',
                '/Twitterbot/i'          => 'Twitterbot',
                '/LinkedInBot/i'         => 'LinkedInBot',
                '/Slackbot/i'            => 'Slackbot',
                '/Discordbot/i'          => 'Discordbot',
                '/python-requests/i'     => 'python-requests',
                '/curl\//i'              => 'curl',
                '/wget\//i'              => 'wget',
                '/Go-http-client/i'      => 'Go-http-client',
                '/Java\//i'              => 'Java-client',
                '/Scrapy/i'              => 'Scrapy',
                '/PostmanRuntime/i'      => 'Postman',
                '/HeadlessChrome/i'      => 'HeadlessChrome',
                '/PhantomJS/i'           => 'PhantomJS',
                '/bot|crawler|spider|scraper/i' => 'crawler',
            ];

            foreach ($patterns as $pattern => $name) {
                if (preg_match($pattern, $ua)) return $name;
            }

            if (($request->header('Accept-Language') ?? '') === '') return 'no-accept-language';

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function visitorHash(Request $request): string
    {
        try {
            return substr(hash('sha256', ($request->ip() ?? '') . ($request->userAgent() ?? '')), 0, 16);
        } catch (\Throwable) {
            return '';
        }
    }
}
