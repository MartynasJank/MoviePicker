@php
    $imdbId = $tmdbInfo->imdb_id ?? $tmdbInfo->external_ids->imdb_id ?? $omdbInfo->imdbID ?? null;
    $ratings = collect($omdbInfo->Ratings ?? [])
        ->filter(fn ($rating) => !empty($rating->Value) && $rating->Value !== 'N/A');
    $hasImdbRating = $ratings->contains('Source', 'Internet Movie Database');
    $voteCount = $tmdbInfo->vote_count ?? 0;
@endphp
<div>
    <h3 class="text-xs sm:text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2 md:mb-3">Ratings</h3>
    <a class="rating-pill" href="https://www.themoviedb.org/{{ $mediaType }}/{{ $tmdbInfo->id }}" target="_blank" rel="noopener noreferrer">
        <span class="text-gray-400 text-xs sm:text-sm truncate">TMDB</span>
        <span class="ml-auto font-semibold text-accent text-xs sm:text-sm shrink-0">{{ $voteCount > 0 ? number_format($tmdbInfo->vote_average ?? 0, 1).'/10' : 'Not yet rated' }}</span>
    </a>
    <p class="text-xs text-gray-500 mt-1 mb-2">{{ number_format($voteCount) }} {{ $voteCount === 1 ? 'vote' : 'votes' }} on TMDB</p>
    @foreach ($ratings as $rating)
        @php $url = $urls[$rating->Source] ?? '#'; @endphp
        <a class="rating-pill" href="{{ $url }}" @if($url !== '#') target="_blank" rel="noopener noreferrer" @endif>
            <span class="text-gray-400 text-xs sm:text-sm truncate">{{ $rating->Source }}</span>
            <span class="ml-auto font-semibold text-accent text-xs sm:text-sm shrink-0">{{ $rating->Value }}</span>
        </a>
    @endforeach
    @if($imdbId && !$hasImdbRating)
        <a class="rating-pill" href="https://www.imdb.com/title/{{ $imdbId }}" target="_blank" rel="noopener noreferrer">
            <span class="text-gray-400 text-xs sm:text-sm">IMDb</span>
            <span class="ml-auto text-accent text-xs sm:text-sm shrink-0">View on IMDb ↗</span>
        </a>
    @endif
</div>
