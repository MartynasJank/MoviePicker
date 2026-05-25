@foreach ($grouped as $groupName => $roulettes)
    <div class="mb-10">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">{{ $groupName }}</h2>
        <div class="flex gap-3 overflow-x-auto pb-2 roulette-row">
            @foreach ($roulettes as $roulette)
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

                <a href="/roulettes/{{ $roulette->slug }}"
                   class="group relative flex-shrink-0 w-36 md:w-44 rounded-xl overflow-hidden block bg-slate-900">
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
                            <img src="{{ $logo }}"
                                 class="absolute top-2 right-2 h-5 drop-shadow-lg"
                                 loading="lazy">
                        @endif

                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            @if($allTags->isNotEmpty())
                                <div class="flex gap-1 mb-1.5 flex-wrap">
                                    @foreach($allTags as $tag)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/10 text-gray-400 border border-white/10">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <h3 class="text-sm font-semibold text-white leading-snug">{{ $roulette->name }}</h3>
                            <span class="text-xs text-accent font-medium mt-1 block group-hover:underline">Roll →</span>
                        </div>

                    </div>
                </a>

            @endforeach
        </div>
    </div>
@endforeach
