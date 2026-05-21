<div class="swiper {{ $name }} w-full relative">
    <div class="swiper-button-prev !hidden sm:!flex !w-9 !h-9 !bg-white/10 hover:!bg-white/20 !rounded-full !text-white after:!text-sm !transition-colors !-left-1"></div>
    <div class="swiper-button-next !hidden sm:!flex !w-9 !h-9 !bg-white/10 hover:!bg-white/20 !rounded-full !text-white after:!text-sm !transition-colors !-right-1"></div>
    <div class="swiper-wrapper">
        @foreach ($allMovies['results'] as $result)
            @if(isset($result['release_date']))
            <div class="swiper-slide h-auto">
                <div class="relative">
                    <a href="{{ url('movie/'.$result['id']) }}{{ !empty($clearCriteria) ? '?i=new' : (!empty($linkSuffix ?? '') ? $linkSuffix : '') }}" class="block group long-movie" data-name="{{ $result['title'] }}">
                        <div class="card card-hover h-full flex flex-col overflow-hidden">
                            {{-- Poster --}}
                            <div class="carousel-poster aspect-[2/3] bg-white/[0.03] overflow-hidden">
                                @if($result['poster_path'])
                                    <img
                                        src="https://image.tmdb.org/t/p/w500{{ $result['poster_path'] }}"
                                        alt="{{ $result['title'] }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs text-center px-3">
                                        No poster
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="p-3 flex flex-col gap-1 flex-1">
                                <h4 class="text-sm font-medium text-white leading-snug line-clamp-2 group-hover:text-accent transition-colors duration-200">
                                    {{ $result['title'] }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-auto">
                                    {{ date('Y', strtotime($result['release_date'])) }}
                                    @if($genres !== [] && isset($genres[$result['id']]))
                                        · <span class="text-gray-600">{{ $genres[$result['id']] }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </a>

                    {{-- TMDB score: top-left --}}
                    @if(($showScore ?? false) && !empty($result['vote_average']) && $result['vote_average'] > 0)
                        <div class="absolute top-2 left-2 bg-black/70 text-accent text-xs font-semibold px-1.5 py-0.5 rounded pointer-events-none">
                            ★ {{ number_format($result['vote_average'], 1) }}
                        </div>
                    @endif

                    {{-- Save button: top-right --}}
                    @if($showSave ?? false)
                        @auth
                            @php $isSaved = in_array($result['id'], $savedIds ?? []); @endphp
                            <button type="button"
                                class="absolute top-2 right-2 watchlist-toggle bg-black/70 text-white text-xs px-2 py-1 rounded hover:bg-black/90 transition-all z-10"
                                data-format="star"
                                data-tmdb-id="{{ $result['id'] }}"
                                data-title="{{ $result['title'] }}"
                                data-poster="{{ $result['poster_path'] }}"
                                data-year="{{ date('Y', strtotime($result['release_date'])) }}"
                                data-genres="{{ $genres[$result['id']] ?? '' }}"
                                data-saved="{{ $isSaved ? '1' : '0' }}">
                                {{ $isSaved ? '★' : '☆' }}
                            </button>
                        @endauth
                    @endif
                </div>
            </div>
            @endif
        @endforeach
    </div>
</div>
