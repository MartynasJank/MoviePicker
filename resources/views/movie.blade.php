@extends('layouts.app')
@section('page_title', ($tmdbInfo->title ?? $omdbInfo->Title ?? 'Movie').' — MoviePickr')
@section('scripts')
    @vite(['resources/js/custom/showmore.js', 'resources/js/custom/customSwiper.js', 'resources/js/custom/customModal.js'])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    @if (isset($trailer))
        @include('includes.modal')
    @endif
    @include('includes.modal-form')

    {{-- Title row --}}
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">
                {{ $tmdbInfo->title ?? $omdbInfo->Title }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ $omdbInfo->Year ?? date('Y', strtotime($tmdbInfo->release_date ?? '')) }}
                @if($genres ?? false)
                    · {{ $genres }}
                @endif
                @if(($omdbInfo->Rated ?? null) && $omdbInfo->Rated !== 'N/A')
                    · <span class="text-gray-600">{{ $omdbInfo->Rated }}</span>
                @endif
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="/movie" class="btn-accent long-single">Pick Another</a>
            <button type="button" class="btn-secondary" data-modal-open="modal-form">Adjust Criteria</button>
        </div>
    </div>

    {{-- Main content grid --}}
    <div class="grid md:grid-cols-[220px_1fr] gap-6 mb-8">

        {{-- Poster --}}
        <div class="flex-shrink-0">
            @if($tmdbInfo->poster_path)
                <img src="https://image.tmdb.org/t/p/original{{ $tmdbInfo->poster_path }}"
                    alt="{{ $tmdbInfo->title }}"
                    class="w-full rounded-xl border border-white/10 object-cover">
            @elseif(isset($omdbInfo->Poster) && $omdbInfo->Poster !== 'N/A' && @getimagesize($omdbInfo->Poster))
                <img src="{{ $omdbInfo->Poster }}" class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] card flex items-center justify-center text-gray-600 text-sm text-center px-4">
                    No poster available
                </div>
            @endif
        </div>

        {{-- Ratings + Plot --}}
        <div class="flex flex-col gap-6">

            {{-- Ratings --}}
            @if(isset($omdbInfo->Ratings) && count($omdbInfo->Ratings) > 0)
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Ratings</h3>
                @foreach ($omdbInfo->Ratings as $rating)
                    <a class="rating-pill" href="{{ $urls[$rating->Source] }}"
                        {{ $urls[$rating->Source] !== '#' ? 'target="_blank"' : '' }}>
                        <span class="text-gray-400 text-sm">{{ $rating->Source }}</span>
                        <span class="ml-auto font-semibold text-accent text-sm">{{ $rating->Value }}</span>
                    </a>
                @endforeach
            </div>
            @endif

            {{-- Trailer + streaming --}}
            <div class="flex flex-wrap gap-3 items-center">
                @if(isset($trailer))
                    <button type="button" class="btn-accent" data-modal-open="trailer-modal">▶ Trailer</button>
                @else
                    <button class="btn-secondary opacity-50 cursor-default" disabled>No Trailer</button>
                @endif

                @if ($watchProviders != null)
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach($watchProviders->flatrate as $stream)
                            <a href="{{ $watchProviders->link }}" target="_blank" title="Watch on streaming">
                                <img src="https://image.tmdb.org/t/p/w45{{ $stream->logo_path }}"
                                    class="h-8 w-8 rounded-md border border-white/10 hover:border-white/30 transition-colors">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Plot --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Plot</h3>
                <p class="text-gray-300 leading-relaxed text-sm">
                    {{ ($omdbInfo->Plot ?? $tmdbInfo->overview) ?? 'No description available.' }}
                </p>
            </div>

        </div>
    </div>

    @if ($watchProviders != null)
        <p class="text-xs text-gray-600 mb-6">Streaming info from TMDB & JustWatch. Logos link to TMDB which redirects to your streaming service.</p>
    @endif

    {{-- Info cards --}}
    <div class="grid md:grid-cols-3 gap-4 mb-8">

        {{-- Cast --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Cast</h3>
            @if(!empty($tmdbInfo->credits->cast))
                <ul class="cast-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->credits->cast as $member)
                        <li class="text-sm text-gray-300">
                            {{ $member->name }}
                            @if($member->character)
                                <span class="text-gray-500"> as {{ $member->character }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Crew --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Crew</h3>
            @if(!empty($tmdbInfo->credits->crew))
                <ul class="crew-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->credits->crew as $member)
                        <li class="text-sm text-gray-300">
                            <span class="text-gray-500">{{ $member->job }}:</span> {{ $member->name }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Production --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Production</h3>
            @if(!empty($tmdbInfo->production_companies))
                <ul class="production-list flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->production_companies as $company)
                        <li class="text-sm text-gray-300">{{ $company->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Details row --}}
    <div class="grid md:grid-cols-3 gap-4 mb-10">

        {{-- General info --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Details</h3>
            <ul class="flex flex-col gap-1.5 text-sm">
                <li class="flex justify-between"><span class="text-gray-500">Budget</span><span class="text-gray-300">{{ $tmdbInfo->budget == 0 ? '—' : '$'.number_format($tmdbInfo->budget) }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Revenue</span><span class="text-gray-300">{{ $tmdbInfo->revenue == 0 ? '—' : '$'.number_format($tmdbInfo->revenue) }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Runtime</span><span class="text-gray-300">{{ $omdbInfo->Runtime ?? ($tmdbInfo->runtime == 0 ? '—' : $tmdbInfo->runtime.' min') }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">IMDB Votes</span><span class="text-gray-300">{{ $omdbInfo->imdbVotes ?? '—' }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">TMDB Score</span><span class="text-accent font-semibold">{{ $tmdbInfo->vote_average ?? '—' }}</span></li>
                <li class="flex justify-between"><span class="text-gray-500">Awards</span><span class="text-gray-300 text-right max-w-[55%]">{{ $omdbInfo->Awards ?? '—' }}</span></li>
            </ul>
        </div>

        {{-- Languages --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Languages</h3>
            @if(!empty($tmdbInfo->spoken_languages))
                <ul class="flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->spoken_languages as $language)
                        <li class="text-sm text-gray-300">{{ $language->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>

        {{-- Countries --}}
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Countries</h3>
            @if(!empty($tmdbInfo->production_countries))
                <ul class="flex flex-col gap-1.5">
                    @foreach ($tmdbInfo->production_countries as $country)
                        <li class="text-sm text-gray-300">{{ $country->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-600">No info</p>
            @endif
        </div>
    </div>

    {{-- Pick another --}}
    <div class="mb-10">
        <a href="/movie" class="btn-accent long-single w-full md:w-auto text-center">Pick Another Movie</a>
    </div>

    {{-- Similar movies --}}
    @if ($similarMovies != null)
    <div>
        <div class="section-header">
            <h2 class="text-xl font-bold text-white mb-3">Similar Movies</h2>
            <div class="section-divider"></div>
        </div>
        @include('includes.carousel', ['allMovies' => $similarMovies, 'name' => 'swiper-similar', 'genres' => []])
    </div>
    @endif

</div>
@endsection
