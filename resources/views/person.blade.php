@extends('layouts.app')
@section('page_title', ($person->name ?? 'Person').' — MoviePickr')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex gap-4 sm:gap-6 mb-4 sm:mb-8">

        {{-- Left: photo + roll buttons --}}
        <div class="flex-shrink-0 w-28 sm:w-44 flex flex-col gap-3">
            {{-- Photo --}}
            @if(!empty($person->profile_path))
                <img src="https://image.tmdb.org/t/p/w300{{ $person->profile_path }}"
                     alt="{{ $person->name }}"
                     class="w-full rounded-xl border border-white/10 object-cover">
            @else
                <div class="w-full aspect-[2/3] rounded-xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-gray-600 text-3xl font-bold">
                    {{ strtoupper(substr($person->name ?? '?', 0, 1)) }}
                </div>
            @endif

            {{-- Roll buttons --}}
            @if($hasMovieCast || $hasMovieCrew)
            <div>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Movies</p>
                <div class="flex flex-col gap-1">
                    @if($hasMovieCast)
                    <a href="{{ route('person.roll.movie', ['id' => $person->id, 'type' => 'cast']) }}"
                       data-roll="person-movie" data-back-label="← {{ $person->name ?? 'Person' }}"
                       data-json-url="{{ url('/person/'.$person->id.'/roll/movie/json?type=cast') }}"
                       class="block text-center text-xs px-2 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                        Roll as actor
                    </a>
                    @endif
                    @if($hasMovieCrew)
                    <a href="{{ route('person.roll.movie', ['id' => $person->id, 'type' => 'crew']) }}"
                       data-roll="person-movie" data-back-label="← {{ $person->name ?? 'Person' }}"
                       data-json-url="{{ url('/person/'.$person->id.'/roll/movie/json?type=crew') }}"
                       class="block text-center text-xs px-2 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                        Roll as crew
                    </a>
                    @endif
                </div>
            </div>
            @endif
            @if($hasTvCast || $hasTvCrew)
            <div>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">TV Shows</p>
                <div class="flex flex-col gap-1">
                    @if($hasTvCast)
                    <a href="{{ route('person.roll.tv', ['id' => $person->id, 'type' => 'cast']) }}"
                       data-roll="person-tv" data-back-label="← {{ $person->name ?? 'Person' }}"
                       data-json-url="{{ url('/person/'.$person->id.'/roll/tv/json?type=cast') }}"
                       class="block text-center text-xs px-2 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                        Roll as actor
                    </a>
                    @endif
                    @if($hasTvCrew)
                    <a href="{{ route('person.roll.tv', ['id' => $person->id, 'type' => 'crew']) }}"
                       data-roll="person-tv" data-back-label="← {{ $person->name ?? 'Person' }}"
                       data-json-url="{{ url('/person/'.$person->id.'/roll/tv/json?type=crew') }}"
                       class="block text-center text-xs px-2 py-1.5 rounded-lg bg-accent/10 border border-accent/20 text-accent hover:bg-accent/20 transition-colors">
                        Roll as crew
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Right: text info --}}
        <div class="min-w-0 flex flex-col">
            @if(!empty($person->known_for_department))
                <span class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20 mb-2 w-fit">{{ $person->known_for_department }}</span>
            @endif
            <h1 class="text-xl sm:text-3xl font-bold text-white leading-tight">{{ $person->name }}</h1>
            <div class="flex flex-col gap-0.5 mt-2 text-sm text-gray-500">
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
            @if(!empty($person->imdb_id) || !empty($person->homepage))
            <div class="flex flex-wrap gap-2 mt-3">
                @if(!empty($person->imdb_id))
                    <a href="https://www.imdb.com/name/{{ $person->imdb_id }}" target="_blank"
                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-lg bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 hover:bg-yellow-500/20 transition-colors">
                        IMDb
                    </a>
                @endif
                @if(!empty($person->homepage))
                    <a href="{{ $person->homepage }}" target="_blank"
                       class="inline-flex items-center text-xs px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                        Website
                    </a>
                @endif
            </div>
            @endif
            @if(!empty($person->biography))
                <p class="text-gray-400 text-sm mt-3 leading-relaxed line-clamp-4">{{ $person->biography }}</p>
                @if(strlen($person->biography) > 300)
                    <button onclick="this.previousElementSibling.classList.toggle('line-clamp-4'); this.textContent = this.textContent === 'Show more' ? 'Show less' : 'Show more'"
                            class="text-xs text-accent mt-1 hover:underline">Show more</button>
                @endif
            @endif
        </div>
    </div>

    {{-- Photo strip: hidden on mobile --}}
    @php $photoStrip = collect($person->images->profiles ?? [])->sortByDesc('vote_average')->skip(1)->take(6)->values(); @endphp
    @if($photoStrip->isNotEmpty())
    <div class="hidden sm:flex gap-2 overflow-x-auto pb-2 scrollbar-hide mb-6">
        @foreach($photoStrip as $photo)
        <div class="flex-shrink-0 w-20 aspect-[2/3] rounded-lg overflow-hidden border border-white/5">
            <img src="https://image.tmdb.org/t/p/w185{{ $photo->file_path }}"
                 alt="{{ $person->name }}"
                 class="w-full h-full object-cover" loading="lazy">
        </div>
        @endforeach
    </div>
    @endif

    {{-- Filmography — 4 tabs --}}
    @php
        $tabs = array_filter([
            $hasMovieCast ? ['id' => 'movie-cast', 'label' => 'Movies', 'sub' => 'Acting',    'count' => $movieCast->count()] : null,
            $hasTvCast    ? ['id' => 'tv-cast',    'label' => 'TV',     'sub' => 'Acting',    'count' => $tvCast->count()]    : null,
            $hasMovieCrew ? ['id' => 'movie-crew', 'label' => 'Movies', 'sub' => 'Crew',      'count' => $movieCrew->count()] : null,
            $hasTvCrew    ? ['id' => 'tv-crew',    'label' => 'TV',     'sub' => 'Crew',      'count' => $tvCrew->count()]    : null,
        ]);
        $firstTab = array_key_first($tabs) !== null ? $tabs[array_key_first($tabs)]['id'] : null;
    @endphp
    @if($firstTab)
    <div>
        <div class="section-header mb-3">
            <h2 class="text-xl font-bold text-white mb-3">Filmography</h2>
            <div class="section-divider"></div>
        </div>

        {{-- Tab buttons --}}
        @if(count($tabs) > 1)
        <div class="flex flex-wrap gap-1 mb-4">
            @foreach($tabs as $tab)
            <button id="tab-{{ $tab['id'] }}" onclick="switchFilmTab('{{ $tab['id'] }}')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $tab['id'] === $firstTab ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}">
                {{ $tab['label'] }}
                <span class="text-xs font-normal opacity-60 ml-0.5">{{ $tab['sub'] }}</span>
                <span class="text-xs text-gray-600 ml-1">({{ $tab['count'] }})</span>
            </button>
            @endforeach
        </div>
        @endif

        {{-- Movie acting --}}
        @if($hasMovieCast)
        <div id="list-movie-cast" @if($firstTab !== 'movie-cast') style="display:none" @endif>
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($movieCast as $item)
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

        {{-- TV acting --}}
        @if($hasTvCast)
        <div id="list-tv-cast" @if($firstTab !== 'tv-cast') style="display:none" @endif>
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($tvCast as $item)
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

        {{-- Movie crew --}}
        @if($hasMovieCrew)
        <div id="list-movie-crew" @if($firstTab !== 'movie-crew') style="display:none" @endif>
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($movieCrew as $item)
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
                        @if(!empty($item->job))
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

        {{-- TV crew --}}
        @if($hasTvCrew)
        <div id="list-tv-crew" @if($firstTab !== 'tv-crew') style="display:none" @endif>
            <div class="flex flex-col divide-y divide-white/5">
                @foreach($tvCrew as $item)
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
                        @if(!empty($item->job))
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

@if(isset($firstTab) && $firstTab && count($tabs ?? []) > 1)
<script>
const FILM_TABS = ['movie-cast','tv-cast','movie-crew','tv-crew'];
function switchFilmTab(tab) {
    FILM_TABS.forEach(id => {
        const el = document.getElementById('list-' + id);
        const btn = document.getElementById('tab-' + id);
        if (el)  el.style.display = id === tab ? '' : 'none';
        if (btn) btn.className = btn.className.replace(/bg-white\/10 text-white|text-gray-400 hover:text-white/g, '')
            .trim() + ' ' + (id === tab ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white');
    });
}
</script>
@endif

@endsection
