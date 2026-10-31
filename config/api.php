<?php

return [
    'tmdb_token'    => env('TMDB_ACCESS_TOKEN'),
    'TMDB'          => env('TMDB_API_KEY', 'No Key'),
    'OMDB'          => env('OMDB_API_KEY'),
    'omdb_enabled'  => env('OMDB_ENABLED', true),
    'YOUTUBE'       => env('YOUTUBE_API_KEY', 'No Key'),
    'contact_email' => env('CONTACT_EMAIL', 'info@moviepickr.com'),
    'admin_email'          => env('ADMIN_EMAIL', ''),
    'amazon_affiliate_tag' => env('AMAZON_AFFILIATE_TAG', ''),
];
