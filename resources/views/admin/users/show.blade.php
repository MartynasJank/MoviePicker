@extends('layouts.app')
@section('page_title', 'Admin — ' . $user->name)
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-white transition-colors text-sm">← Users</a>
        <div class="flex items-center gap-3">
            @if($user->avatar)
                <img src="{{ $user->avatar }}" class="w-8 h-8 rounded-full ring-1 ring-white/10" alt="">
            @endif
            <div>
                <h2 class="text-lg font-semibold text-white">{{ $user->name }}</h2>
                <p class="text-xs text-gray-500">{{ $user->email }} · {{ $roulettes->count() }} roulettes</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($ordered->flatten()->isEmpty())
        <p class="text-gray-500 text-sm">This user has no roulettes.</p>
    @else
    <div class="flex flex-col md:flex-row gap-6">

        {{-- Sidebar --}}
        <div class="md:w-48 flex-shrink-0">
            <nav class="flex md:flex-col gap-1 overflow-x-auto pb-1 md:pb-0 md:sticky md:top-20" id="group-nav">
                @foreach($ordered as $groupName => $roulettes)
                    <button type="button"
                            class="group-btn flex-shrink-0 flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors text-gray-500 hover:text-white hover:bg-white/5 text-left whitespace-nowrap"
                            data-panel="{{ $loop->index }}">
                        <span>{{ $groupName }}</span>
                        <span class="ml-2 text-xs text-gray-600">{{ $roulettes->count() }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Panels --}}
        <div class="flex-1 min-w-0">
            @foreach($ordered as $groupName => $roulettes)
                <div class="group-panel" data-panel="{{ $loop->index }}" style="display:none">
                    <h3 class="text-sm font-semibold text-white mb-3">{{ $groupName }}
                        <span class="text-gray-600 font-normal ml-1">{{ $roulettes->count() }}</span>
                    </h3>

                    @if($roulettes->isEmpty())
                        <p class="text-sm text-gray-600">No roulettes in this row.</p>
                    @else
                    <div class="bg-white/3 rounded-xl overflow-hidden border border-white/5">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/5 text-left">
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500">Name</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 hidden sm:table-cell">Tags</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-center">Public</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roulettes as $roulette)
                                    <tr class="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors">
                                        <td class="py-3 px-4 text-white font-medium">{{ $roulette->name }}</td>
                                        <td class="py-3 px-4 hidden sm:table-cell">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(collect($roulette->tags)->flatten() as $tag)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="text-xs {{ $roulette->is_public ? 'text-accent' : 'text-gray-600' }}">
                                                {{ $roulette->is_public ? 'Public' : 'Private' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="/roulettes/{{ $roulette->slug }}" target="_blank"
                                                   class="text-xs text-gray-500 hover:text-white transition-colors">Roll</a>
                                                <form method="POST"
                                                      action="{{ route('admin.users.roulettes.destroy', [$user, $roulette]) }}"
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
                    @endif
                </div>
            @endforeach
        </div>

    </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btns   = document.querySelectorAll('.group-btn');
    const panels = document.querySelectorAll('.group-panel');

    function activate(index) {
        btns.forEach((b, i) => {
            b.classList.toggle('bg-white/10', i === index);
            b.classList.toggle('text-white',  i === index);
            b.classList.toggle('text-gray-500', i !== index);
        });
        panels.forEach((p, i) => p.style.display = i === index ? '' : 'none');
    }

    btns.forEach((btn, i) => btn.addEventListener('click', () => activate(i)));
    if (btns.length) activate(0);
});
</script>
@endsection
