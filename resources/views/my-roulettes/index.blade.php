@extends('layouts.app')
@section('page_title', 'My Roulettes — MoviePickr')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-white">My Roulettes</h1>
                </div>
                <p class="text-gray-500 text-sm mt-1">Your personal collection — just hit Roll.</p>
            </div>
            <a href="{{ route('my-roulettes.manage') }}" class="btn-secondary text-sm px-4 py-2">Manage →</a>
        </div>
        <div class="section-divider mt-3"></div>
    </div>

    @if($roulettes->isEmpty())
        <div class="text-center py-20">
            <p class="text-gray-500 mb-4">You haven't created any roulettes yet.</p>
            <a href="{{ route('my-roulettes.create') }}" class="btn-accent px-6 py-2.5 text-sm">Create your first roulette</a>
        </div>
    @else

    @php
        $platformLogos = [
            'netflix' => 'https://image.tmdb.org/t/p/w92/pbpMk2JmcoNnQwx5JGpXngfoWtp.jpg',
            'prime'   => 'https://image.tmdb.org/t/p/w92/pvske1MyAoymrs5bguRfVqYiM9a.jpg',
            'hbo'     => 'https://image.tmdb.org/t/p/w92/jbe4gVSfRlbPTdESXhEKpornsfu.jpg',
            'disney'  => 'https://image.tmdb.org/t/p/w92/97yvRBw1GzX7fXprcF80er19ot.jpg',
            'apple'   => 'https://image.tmdb.org/t/p/w92/mcbz1LgtErU9p4UdbZ0rG6RTWHX.jpg',
        ];
        $tagLabels = [
            'netflix' => 'Netflix', 'prime' => 'Prime', 'hbo' => 'HBO', 'disney' => 'Disney+', 'apple' => 'Apple TV+',
            'action' => 'Action', 'adventure' => 'Adventure', 'animation' => 'Animation', 'comedy' => 'Comedy',
            'crime' => 'Crime', 'documentary' => 'Documentary', 'drama' => 'Drama', 'family' => 'Family',
            'fantasy' => 'Fantasy', 'history' => 'History', 'horror' => 'Horror', 'mystery' => 'Mystery',
            'romance' => 'Romance', 'sci-fi' => 'Sci-Fi', 'thriller' => 'Thriller', 'war' => 'War', 'western' => 'Western',
            'pre-1950' => 'Classic', '1950s' => '50s', '1960s' => '60s', '1970s' => '70s',
            '1980s' => '80s', '1990s' => '90s', '2000s' => '2000s', '2010s' => '2010s', '2020s' => '2020s', 'recent' => 'Recent',
            'ko' => 'Korean', 'ja' => 'Japanese', 'fr' => 'French', 'es' => 'Spanish',
            'de' => 'German', 'it' => 'Italian', 'zh' => 'Chinese', 'hi' => 'Hindi',
            'tr' => 'Turkish', 'pt' => 'Portuguese', 'lt' => 'Lithuanian',
        ];
    @endphp

    @foreach($ordered as $groupName => $roulettes)
        @if($roulettes->isNotEmpty())
        <div class="mb-10">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">{{ $groupName }}</h2>

            <div class="flex gap-3 overflow-x-auto pb-2 roulette-row">
                @foreach($roulettes as $roulette)
                    @php
                        $tags     = $roulette->tags;
                        $platform = $tags['platform'][0] ?? null;
                        $logo     = $platform ? ($platformLogos[$platform] ?? null) : null;
                        $allTags  = collect($tags)
                                        ->except(['platform', 'without_genre'])
                                        ->flatten()
                                        ->map(fn($v) => $tagLabels[$v] ?? $v);
                        $poster   = $roulette->poster_paths[0] ?? null;
                    @endphp

                    <div class="flex-shrink-0 w-36 md:w-44">
                    <a href="/roulettes/{{ $roulette->slug }}"
                       class="group relative rounded-xl overflow-hidden block bg-slate-900">
                        <div class="aspect-[2/3] relative overflow-hidden">

                            @if($poster)
                                <img src="https://image.tmdb.org/t/p/w342{{ $poster }}"
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     loading="lazy">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"></div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></div>

                            @if($logo)
                                <img src="{{ $logo }}" class="absolute top-2 right-2 h-5 drop-shadow-lg" loading="lazy">
                            @endif

                            <div class="absolute top-2 left-2 flex gap-1">
                                @if(!$roulette->is_public)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-black/60 text-gray-400 border border-white/10">Private</span>
                                @endif
                                @if(($roulette->media_type ?? 'movie') === 'tv')
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-accent/80 text-white">TV</span>
                                @endif
                            </div>

                            <div class="absolute bottom-0 left-0 right-0 p-3">
                                @if($allTags->isNotEmpty())
                                    <div class="flex gap-1 mb-1.5 flex-wrap">
                                        @foreach($allTags as $tag)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/10 text-gray-400 border border-white/10">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <h3 class="text-sm font-semibold text-white leading-snug">{{ $roulette->name }}</h3>
                                <span class="text-xs text-accent font-medium mt-1 block group-hover:underline">Batch →</span>
                            </div>

                        </div>
                    </a>
                    <button class="w-full btn-accent text-xs py-1.5 mt-2" data-roulette-roll data-slug="{{ $roulette->slug }}">Roll</button>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    @endif

</div>
@endsection
