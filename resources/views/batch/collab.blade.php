@extends('layouts.app')
@section('page_title', 'Pick Together — MoviePickr')
@section('og_title', 'Pick a Movie Together — MoviePickr')
@section('og_description', 'Vote out movies until one is left. Everyone votes in real time.')
@section('footer_pb', 'pb-36')
@section('scripts')
    @vite(['resources/js/custom/collabBatch.js'])
@endsection
@section('content')

<div class="max-w-7xl mx-auto px-4 py-6" id="collab-root"
     data-token="{{ $batch->token }}"
     data-media-type="{{ $batch->media_type }}"
     data-has-criteria="{{ $batch->criteria ? '1' : '0' }}">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Pick Together</h1>
            <p class="text-xs text-gray-500 mt-0.5">Vote out movies — last one standing wins</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button class="btn-secondary text-sm flex items-center gap-1.5"
                data-share
                data-share-url="{{ url()->current() }}"
                data-share-title="Pick a movie with me! — MoviePickr">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                Invite
            </button>
        </div>
    </div>

    {{-- Identity + Participants bar --}}
    <div class="flex items-center gap-3 mb-4 bg-white/5 rounded-xl px-3 py-2">
        <span class="text-xs text-gray-500 flex-shrink-0">You:</span>
        <input id="identity-input" type="text" maxlength="24"
               class="bg-transparent text-sm text-white flex-1 outline-none placeholder-gray-600 min-w-0"
               style="font-size:16px"
               placeholder="Enter your name…">
        <span class="text-xs text-green-400 flex-shrink-0 transition-opacity" id="identity-saved"></span>
        <div class="w-px h-4 bg-white/10 flex-shrink-0"></div>
        <div id="participant-list" class="flex items-center gap-1.5 flex-shrink-0"></div>
    </div>

    {{-- Rules card --}}
    <div class="bg-white/5 rounded-xl px-4 py-3 mb-5 grid grid-cols-3 gap-3 text-center">
        <div>
            <div class="text-lg font-bold text-white" id="rule-veto">—</div>
            <div class="text-xs text-gray-500 mt-0.5">votes to skip</div>
        </div>
        <div>
            <div class="text-lg font-bold text-white" id="rule-roll">—</div>
            <div class="text-xs text-gray-500 mt-0.5">to roll</div>
        </div>
        <div>
            <div class="text-lg font-bold text-white" id="rule-remaining"><span id="remaining-num">{{ count($batch->movies) }}</span>/<span id="total-num">{{ count($batch->movies) }}</span></div>
            <div class="text-xs text-gray-500 mt-0.5">movies left</div>
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="h-1 bg-white/10 rounded-full overflow-hidden mb-6">
        <div id="veto-progress" class="h-full bg-accent transition-all duration-500 rounded-full" style="width:100%"></div>
    </div>

    {{-- Toast area --}}
    <div id="veto-toasts" class="fixed top-20 left-1/2 z-50 flex flex-col items-center gap-2 pointer-events-none" style="transform:translateX(-50%)"></div>

    {{-- Movie grid --}}
    <div id="collab-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-8">
        @foreach($batch->movies as $movie)
        @php
            $isTv    = ($batch->media_type === 'tv') || ($movie['media_type'] ?? '') === 'tv';
            $itemUrl = ($isTv ? '/tv/' : '/movie/') . $movie['id'];
            $title   = $movie['title'] ?? $movie['name'] ?? '';
        @endphp
        <div class="collab-card relative select-none" data-id="{{ $movie['id'] }}">
            <div class="card overflow-hidden">
                {{-- Poster — tap to vote --}}
                <div class="vote-target aspect-[2/3] bg-white/[0.03] overflow-hidden relative cursor-pointer">
                    @if(!empty($movie['poster_path']))
                        <img src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}"
                             alt="{{ $title }}"
                             class="w-full h-full object-cover transition-transform duration-300"
                             loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs px-2 text-center">{{ $title }}</div>
                    @endif

                    {{-- Ambient vote heat (visible to all) --}}
                    <div class="vote-heat absolute inset-0 bg-red-900/0 transition-all duration-500 pointer-events-none"></div>

                    {{-- My vote indicator (border glow) --}}
                    <div class="voted-overlay absolute inset-0 border-2 border-red-500/70 hidden pointer-events-none"></div>

                    {{-- Vote progress bar --}}
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                        <div class="vote-bar h-full bg-red-500 transition-all duration-300" style="width:0%"></div>
                    </div>

                    {{-- Vote pip dots --}}
                    <div class="vote-pips absolute top-2 left-0 right-0 flex justify-center gap-1 hidden"></div>
                </div>

                {{-- Title — tap to open details --}}
                <div class="p-2">
                    <div class="text-xs font-medium text-white truncate">{{ $title }}</div>
                    @if(!empty($movie['vote_average']) && $movie['vote_average'] > 0)
                        <div class="text-xs text-gray-500 mt-0.5">★ {{ number_format($movie['vote_average'], 1) }}</div>
                    @endif
                    @if(!empty($movie['genres']))
                        <div class="text-xs text-gray-600 mt-0.5 truncate">{{ $movie['genres'] }}</div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Graveyard --}}
    <div id="graveyard-section" class="{{ count($batch->graveyard ?? []) > 0 ? '' : 'hidden' }} mb-24">
        <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold text-gray-500">Graveyard</h2>
            <div class="flex-1 h-px bg-white/10"></div>
        </div>
        <div class="bg-white/5 rounded-xl px-4 py-3 mb-4 grid grid-cols-3 gap-3 text-center">
            <div>
                <div class="text-base font-bold text-white" id="graveyard-restore-rule">—</div>
                <div class="text-xs text-gray-500 mt-0.5">votes to restore</div>
            </div>
            <div>
                <div class="text-base font-bold text-white" id="graveyard-count">{{ count($batch->graveyard ?? []) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">vetoed movies</div>
            </div>
            <div>
                <div class="text-base font-bold text-white">Tap</div>
                <div class="text-xs text-gray-500 mt-0.5">to vote restore</div>
            </div>
        </div>
        <div id="graveyard-grid" class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach($batch->graveyard ?? [] as $movie)
            @php
                $isTv    = ($batch->media_type === 'tv') || ($movie['media_type'] ?? '') === 'tv';
                $title   = $movie['title'] ?? $movie['name'] ?? '';
            @endphp
            <div class="graveyard-card relative flex-shrink-0 w-28 sm:w-32 cursor-pointer opacity-50 hover:opacity-80 transition-opacity group"
                 data-id="{{ $movie['id'] }}">
                <div class="aspect-[2/3] rounded-lg overflow-hidden bg-white/[0.03] relative">
                    @if(!empty($movie['poster_path']))
                        <img src="https://image.tmdb.org/t/p/w185{{ $movie['poster_path'] }}"
                             alt="{{ $title }}" class="w-full h-full object-cover grayscale">
                    @endif
                    <div class="restore-overlay absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-lg">↩</span>
                        <span class="text-xs text-gray-300">Restore</span>
                    </div>
                    <div class="restore-vote-bar absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                        <div class="restore-bar h-full bg-green-500 transition-all duration-300" style="width:0%"></div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 truncate mt-1 text-center">{{ $title }}</div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Sticky bottom bar --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f]/95 backdrop-blur-lg border-t border-white/10 px-4 z-40 sticky-bar-safe">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            @if($batch->criteria)
            <button id="refresh-btn" class="btn-secondary text-sm min-h-[44px]" title="Vote to roll a new batch">
                New Batch
                <span id="refresh-votes-count" class="text-xs text-gray-500 ml-1"></span>
            </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button id="ready-btn" class="btn-accent px-6 min-h-[44px]">
                Ready to Roll
                <span id="ready-count" class="text-xs opacity-70 ml-1"></span>
            </button>
        </div>
    </div>
</div>

{{-- Winner overlay --}}
<div id="winner-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm px-4">
    <div class="text-center max-w-sm w-full">
        <p class="text-gray-500 text-sm mb-5">Last one standing 🎉</p>
        <div id="winner-poster" class="mx-auto w-32 sm:w-40 rounded-xl overflow-hidden mb-5 shadow-2xl ring-2 ring-accent/50"></div>
        <h2 class="text-2xl font-bold text-white mb-1" id="winner-title"></h2>
        <p class="text-gray-500 text-sm mb-6" id="winner-rating"></p>
        <div class="flex gap-3 justify-center">
            <a id="winner-details-link" href="#" class="btn-accent px-6 py-3">View Details</a>
            <button id="winner-dismiss" class="btn-secondary px-6 py-3">Stay Here</button>
        </div>
    </div>
</div>

<script>
window.collabToken     = @json($batch->token);
window.collabMovies    = @json($batch->movies);
window.collabGraveyard = @json($batch->graveyard ?? []);
window.collabVotes     = @json($batch->votes ?? (object)[]);
window.collabRestore   = @json($batch->restore_votes ?? (object)[]);
window.collabReady     = @json($batch->ready ?? []);
window.collabRefresh   = @json($batch->refresh_votes ?? []);
window.collabParticipants = @json($batch->participants ?? []);
window.collabMediaType = @json($batch->media_type);
window.collabHasCriteria = {{ $batch->criteria ? 'true' : 'false' }};
</script>

@endsection
