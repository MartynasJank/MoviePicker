@extends('layouts.app')
@section('page_title', 'Admin Dashboard')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Total Roulettes</div>
        </div>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-accent mb-1">{{ $stats['public'] }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Public</div>
        </div>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-blue-400 mb-1">{{ $stats['system'] }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">System</div>
        </div>
        <div class="bg-white/3 border border-white/5 rounded-xl p-5">
            <div class="text-3xl font-bold text-purple-400 mb-1">{{ $stats['community'] }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-widest">Community</div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('admin.roulettes.index') }}"
           class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
            <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">Manage Roulettes →</div>
            <div class="text-sm text-gray-500">Edit, reorder, publish or hide roulettes by group.</div>
        </a>
        <a href="{{ route('admin.rows.index') }}"
           class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
            <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">Row Order →</div>
            <div class="text-sm text-gray-500">Drag to reorder how rows appear on the /roulettes page.</div>
            @if(count($rowOrder))
            <div class="mt-3 flex flex-wrap gap-1">
                @foreach($rowOrder as $row)
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/5 text-gray-400 border border-white/10">{{ $row }}</span>
                @endforeach
            </div>
            @endif
        </a>
        <a href="{{ route('admin.roulettes.create') }}"
           class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
            <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">+ New Roulette →</div>
            <div class="text-sm text-gray-500">Create a new curated collection.</div>
        </a>
        <a href="/roulettes" target="_blank"
           class="group bg-white/3 border border-white/5 hover:border-white/10 rounded-xl p-6 transition-colors">
            <div class="text-lg font-semibold text-white mb-1 group-hover:text-accent transition-colors">View /roulettes ↗</div>
            <div class="text-sm text-gray-500">See the public-facing roulettes page.</div>
        </a>
    </div>

</div>
@endsection
