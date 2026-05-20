<div class="swiper {{ $name }} w-full">
    <div class="swiper-wrapper">
        @foreach ($allMovies['results'] as $result)
            @if(isset($result['release_date']))
            <div class="swiper-slide h-auto">
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
            </div>
            @endif
        @endforeach
    </div>
</div>
