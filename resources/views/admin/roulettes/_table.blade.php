<div class="overflow-x-auto rounded-xl">
<div class="bg-white/3 overflow-hidden border border-white/5 min-w-[480px] rounded-xl">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-left">
                <th class="w-8 py-2.5 px-3"></th>
                <th class="w-12 py-2.5 px-2"></th>
                <th class="py-2.5 px-3 text-xs font-medium text-gray-500">Name</th>
                <th class="py-2.5 px-3 text-xs font-medium text-gray-500 hidden md:table-cell">Slug</th>
                <th class="py-2.5 px-3 text-xs font-medium text-gray-500 hidden lg:table-cell">Tags</th>
                <th class="py-2.5 px-3 text-xs font-medium text-gray-500 text-center">Public</th>
                <th class="py-2.5 px-3 text-xs font-medium text-gray-500 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="{{ $sortable ? 'sortable-tbody' : '' }}">
            @foreach($roulettes as $roulette)
                <tr class="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors"
                    data-id="{{ $roulette->id }}">
                    <td class="py-3 px-3 text-gray-600 {{ $sortable ? 'cursor-grab drag-handle' : '' }} select-none">
                        @if($sortable)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                        @endif
                    </td>
                    {{-- Poster thumbnail --}}
                    <td class="py-2 px-2">
                        @php $poster = ($roulette->poster_paths ?? [])[0] ?? null; @endphp
                        <div class="relative group w-9 h-[52px] flex-shrink-0">
                            @if($poster)
                                <img src="https://image.tmdb.org/t/p/w92{{ $poster }}"
                                     alt="{{ $roulette->name }}"
                                     class="roulette-poster w-full h-full object-cover rounded"
                                     data-id="{{ $roulette->id }}">
                            @else
                                <div class="w-full h-full bg-white/5 rounded roulette-poster-placeholder" data-id="{{ $roulette->id }}"></div>
                            @endif
                            <button type="button"
                                    class="roll-poster-btn absolute inset-0 flex items-center justify-center bg-black/60 {{ $poster ? 'opacity-0 group-hover:opacity-100' : 'opacity-100' }} rounded transition-opacity text-white"
                                    data-id="{{ $roulette->id }}"
                                    title="Roll poster">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex items-center gap-2">
                            <span class="text-white font-medium">{{ $roulette->name }}</span>
                            @if(($roulette->media_type ?? 'movie') === 'tv')
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20">TV</span>
                            @else
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-500 border border-white/10">Film</span>
                            @endif
                            @if($roulette->is_system)
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-500/15 text-purple-400 border border-purple-500/20">system</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 px-3 text-gray-500 font-mono text-xs hidden md:table-cell">{{ $roulette->slug }}</td>
                    <td class="py-3 px-3 hidden lg:table-cell">
                        <div class="flex flex-wrap gap-1">
                            @foreach(collect($roulette->tags)->except(['without_genre'])->flatten() as $tag)
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="py-3 px-3 text-center">
                        <form method="POST" action="{{ route('admin.roulettes.toggle', $roulette) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-8 h-4 rounded-full transition-colors {{ $roulette->is_public ? 'bg-accent' : 'bg-white/10' }} relative inline-block"
                                    title="{{ $roulette->is_public ? 'Public — click to hide' : 'Hidden — click to publish' }}">
                                <span class="absolute top-0.5 {{ $roulette->is_public ? 'right-0.5' : 'left-0.5' }} w-3 h-3 rounded-full bg-white shadow transition-all"></span>
                            </button>
                        </form>
                    </td>
                    <td class="py-3 px-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="/roulettes/{{ $roulette->slug }}" target="_blank"
                               class="hidden sm:inline text-xs text-gray-500 hover:text-white transition-colors">Roll</a>
                            <a href="{{ route('admin.roulettes.edit', $roulette) }}"
                               class="text-xs text-gray-400 hover:text-accent transition-colors">Edit</a>
                            <form method="POST" action="{{ route('admin.roulettes.destroy', $roulette) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($roulette->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-gray-600 hover:text-red-400 transition-colors">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>
