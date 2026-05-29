<div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide carousel-row {{ $name ?? '' }}">
    @foreach ($allMovies['results'] as $result)
        @php
            $date      = $result['release_date'] ?? $result['first_air_date'] ?? null;
            $title     = $result['title'] ?? $result['name'] ?? '';
            $itemBase  = $result['media_type'] ?? $linkBase ?? 'movie';
        @endphp
        @if($date)
        <div class="relative flex-shrink-0 w-44 sm:w-52 lg:w-56"
             data-batch-card
             data-title="{{ $title }}"
             data-rating="{{ $result['vote_average'] ?? 0 }}"
             data-poster="{{ $result['poster_path'] ?? '' }}"
             data-media-type="{{ $itemBase === 'tv' ? 'tv' : 'movie' }}"
             data-url="{{ url($itemBase.'/'.$result['id']) }}{{ !empty($clearCriteria) ? '?i=new' : ($linkSuffix ?? '') }}">
            <a href="{{ url($itemBase.'/'.$result['id']) }}{{ !empty($clearCriteria) ? '?i=new' : (!empty($linkSuffix ?? '') ? $linkSuffix : '') }}"
               class="block h-full group long-movie" data-name="{{ $title }}">
                <div class="card card-hover h-full flex flex-col overflow-hidden">
                    <div class="aspect-[2/3] bg-white/[0.03] overflow-hidden">
                        @if($result['poster_path'] ?? null)
                            <img
                                src="https://image.tmdb.org/t/p/w342{{ $result['poster_path'] }}"
                                alt="{{ $title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            >
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-3">
                                No poster
                            </div>
                        @endif
                    </div>
                    <div class="p-3 flex flex-col gap-1 flex-1">
                        <h4 class="text-sm font-medium text-white leading-snug line-clamp-2 group-hover:text-accent transition-colors duration-200">
                            {{ $title }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-auto line-clamp-1">
                            {{ date('Y', strtotime($date)) }}
                            @if($genres !== [] && isset($genres[$result['id']]))
                                · <span class="text-gray-600">{{ $genres[$result['id']] }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </a>

            @if($showScore ?? false)
                <div class="absolute top-2 left-2 bg-black/70 text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none {{ !empty($result['vote_average']) && $result['vote_average'] > 0 ? 'text-accent' : 'text-gray-500' }}">
                    ★ {{ !empty($result['vote_average']) && $result['vote_average'] > 0 ? number_format($result['vote_average'], 1) : '—' }}
                </div>
            @endif

            @if($showSave ?? false)
                @auth
                    @php $isSaved = in_array($result['id'], $savedIds ?? []); @endphp
                    <button type="button"
                        class="absolute top-2 right-2 watchlist-toggle text-xs px-2 py-1 rounded transition-all z-10 {{ $isSaved ? 'bg-accent text-white' : 'bg-black/70 text-white hover:bg-black/90' }}"
                        data-format="star"
                        data-tmdb-id="{{ $result['id'] }}"
                        data-title="{{ $title }}"
                        data-poster="{{ $result['poster_path'] ?? '' }}"
                        data-year="{{ date('Y', strtotime($date)) }}"
                        data-genres="{{ $genres[$result['id']] ?? '' }}"
                        data-rating="{{ $result['vote_average'] ?? '' }}"
                        data-media-type="{{ $mediaType ?? ($linkBase ?? 'movie') }}"
                        data-saved="{{ $isSaved ? '1' : '0' }}">
                        {{ $isSaved ? '★ Saved' : '☆' }}
                    </button>
                @endauth
            @endif
        </div>
        @endif
    @endforeach
</div>
