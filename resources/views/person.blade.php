@extends('layouts.app')
@section('page_title', ($person->name ?? 'Person').' — MoviePickr')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex gap-6 mb-8">
        <div class="flex-shrink-0 w-32 sm:w-44">
            @if(!empty($person->profile_path))
                <img src="https://image.tmdb.org/t/p/w300{{ $person->profile_path }}"
                     alt="{{ $person->name }}"
                     class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] rounded-xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-gray-600 text-3xl font-bold">
                    {{ strtoupper(substr($person->name ?? '?', 0, 1)) }}
                </div>
            @endif
        </div>
        <div class="min-w-0 flex flex-col justify-center">
            @if(!empty($person->known_for_department))
                <span class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20 mb-2 w-fit">{{ $person->known_for_department }}</span>
            @endif
            <h1 class="text-2xl sm:text-3xl font-bold text-white leading-tight">{{ $person->name }}</h1>
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-500">
                @if(!empty($person->birthday))
                    <span>Born {{ \Carbon\Carbon::parse($person->birthday)->format('M j, Y') }}
                        @if(empty($person->deathday))
                            ({{ \Carbon\Carbon::parse($person->birthday)->age }} years old)
                        @endif
                    </span>
                @endif
                @if(!empty($person->deathday))
                    <span>Died {{ \Carbon\Carbon::parse($person->deathday)->format('M j, Y') }}</span>
                @endif
                @if(!empty($person->place_of_birth))
                    <span>{{ $person->place_of_birth }}</span>
                @endif
            </div>
            {{-- External links --}}
            @if(!empty($person->imdb_id) || !empty($person->homepage))
            <div class="flex flex-wrap gap-2 mt-3">
                @if(!empty($person->imdb_id))
                    <a href="https://www.imdb.com/name/{{ $person->imdb_id }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 hover:bg-yellow-500/20 transition-colors">
                        IMDb
                    </a>
                @endif
                @if(!empty($person->homepage))
                    <a href="{{ $person->homepage }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                        Website
                    </a>
                @endif
            </div>
            @endif

            {{-- Discover buttons --}}
            <div class="flex flex-wrap gap-2 mt-3">
                @if($movies->isNotEmpty())
                <a href="{{ route('person.roll.movie', $person->id) }}"
                   class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                    Roll a movie with {{ $person->name }}
                </a>
                @endif
                @if($tvShows->isNotEmpty())
                <a href="{{ route('person.roll.tv', $person->id) }}"
                   class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                    Roll a TV show with {{ $person->name }}
                </a>
                @endif
            </div>

            @if(!empty($person->biography))
                <p class="text-gray-400 text-sm mt-3 leading-relaxed line-clamp-4">{{ $person->biography }}</p>
                @if(strlen($person->biography) > 300)
                    <button onclick="this.previousElementSibling.classList.toggle('line-clamp-4'); this.textContent = this.textContent === 'Show more' ? 'Show less' : 'Show more'"
                            class="text-xs text-accent mt-1 hover:underline">Show more</button>
                @endif
            @endif
        </div>
    </div>

    {{-- Photo strip --}}
    @php $photos = collect($person->images->profiles ?? [])->sortByDesc('vote_average')->skip(1)->take(6)->values(); @endphp
    @if($photos->isNotEmpty())
    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide mb-8">
        @foreach($photos as $photo)
        <div class="flex-shrink-0 w-20 aspect-[2/3] rounded-lg overflow-hidden border border-white/5">
            <img src="https://image.tmdb.org/t/p/w185{{ $photo->file_path }}"
                 alt="{{ $person->name }}"
                 class="w-full h-full object-cover" loading="lazy">
        </div>
        @endforeach
    </div>
    @endif

    {{-- Known for --}}
    @if($knownFor->isNotEmpty())
    <div class="mb-10">
        <div class="section-header mb-4">
            <h2 class="text-xl font-bold text-white mb-3">Known For</h2>
            <div class="section-divider"></div>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach($knownFor as $item)
            @php
                $link  = ($item->media_type ?? '') === 'tv' ? '/tv/'.$item->id : '/movie/'.$item->id;
                $title = $item->title ?? $item->name ?? '';
                $year  = substr($item->release_date ?? $item->first_air_date ?? '', 0, 4);
            @endphp
            <a href="{{ $link }}" class="flex-shrink-0 w-28 group">
                <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] group-hover:ring-1 group-hover:ring-accent/50 transition-all">
                    @if(!empty($item->poster_path))
                        <img src="https://image.tmdb.org/t/p/w185{{ $item->poster_path }}"
                             alt="{{ $title }}"
                             class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-2">{{ $title }}</div>
                    @endif
                </div>
                <p class="text-xs text-gray-300 mt-1.5 font-medium leading-snug group-hover:text-white transition-colors truncate">{{ $title }}</p>
                @if($year)<p class="text-xs text-gray-600">{{ $year }}</p>@endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filmography --}}
    @php $showMovies = $movies->isNotEmpty(); $showTv = $tvShows->isNotEmpty(); @endphp
    @if($showMovies || $showTv)
    <div>
        <div class="section-header mb-4">
            <h2 class="text-xl font-bold text-white mb-3">Filmography</h2>
            <div class="section-divider"></div>
        </div>

        {{-- Tabs --}}
        @if($showMovies && $showTv)
        <div class="flex gap-1 mb-4">
            <button id="tab-movies" onclick="switchTab('movies')"
                class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors bg-white/10 text-white">Movies ({{ $movies->count() }})</button>
            <button id="tab-tv" onclick="switchTab('tv')"
                class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors text-gray-400 hover:text-white">TV Shows ({{ $tvShows->count() }})</button>
        </div>
        @endif

        {{-- Movies list --}}
        @if($showMovies)
        <div id="list-movies">
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($movies as $item)
                @php $year = substr($item->release_date ?? '', 0, 4); @endphp
                <a href="/movie/{{ $item->id }}" class="flex items-center gap-4 py-3 hover:bg-white/[0.03] -mx-2 px-2 rounded-lg transition-colors group">
                    <span class="text-sm text-gray-600 w-10 flex-shrink-0 text-right">{{ $year ?: '—' }}</span>
                    <div class="w-8 h-12 flex-shrink-0 rounded overflow-hidden bg-white/[0.03]">
                        @if(!empty($item->poster_path))
                            <img src="https://image.tmdb.org/t/p/w92{{ $item->poster_path }}" class="w-full h-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-300 group-hover:text-white transition-colors truncate">{{ $item->title ?? '' }}</p>
                        @if(!empty($item->character))
                            <p class="text-xs text-gray-600 truncate">as {{ $item->character }}</p>
                        @elseif(!empty($item->job))
                            <p class="text-xs text-gray-600 truncate">{{ $item->job }}</p>
                        @endif
                    </div>
                    @if(!empty($item->vote_average) && $item->vote_average > 0)
                        <span class="ml-auto text-xs text-gray-600 flex-shrink-0">★ {{ number_format($item->vote_average, 1) }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- TV list --}}
        @if($showTv)
        <div id="list-tv" @if($showMovies && $showTv) style="display:none" @endif>
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($tvShows as $item)
                @php $year = substr($item->first_air_date ?? '', 0, 4); @endphp
                <a href="/tv/{{ $item->id }}" class="flex items-center gap-4 py-3 hover:bg-white/[0.03] -mx-2 px-2 rounded-lg transition-colors group">
                    <span class="text-sm text-gray-600 w-10 flex-shrink-0 text-right">{{ $year ?: '—' }}</span>
                    <div class="w-8 h-12 flex-shrink-0 rounded overflow-hidden bg-white/[0.03]">
                        @if(!empty($item->poster_path))
                            <img src="https://image.tmdb.org/t/p/w92{{ $item->poster_path }}" class="w-full h-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-300 group-hover:text-white transition-colors truncate">{{ $item->name ?? '' }}</p>
                        @if(!empty($item->character))
                            <p class="text-xs text-gray-600 truncate">as {{ $item->character }}</p>
                        @elseif(!empty($item->job))
                            <p class="text-xs text-gray-600 truncate">{{ $item->job }}</p>
                        @endif
                    </div>
                    @if(!empty($item->vote_average) && $item->vote_average > 0)
                        <span class="ml-auto text-xs text-gray-600 flex-shrink-0">★ {{ number_format($item->vote_average, 1) }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

</div>

@if($showMovies && $showTv)
<script>
function switchTab(tab) {
    document.getElementById('list-movies').style.display = tab === 'movies' ? '' : 'none';
    document.getElementById('list-tv').style.display     = tab === 'tv'     ? '' : 'none';
    document.getElementById('tab-movies').className = 'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ' +
        (tab === 'movies' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white');
    document.getElementById('tab-tv').className = 'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors ' +
        (tab === 'tv' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white');
}
</script>
@endif

@endsection
