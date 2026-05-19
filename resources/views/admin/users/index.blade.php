@extends('layouts.app')
@section('page_title', 'Admin — Users')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8 flex items-center gap-3">
        <h1 class="text-2xl font-bold text-white">Admin</h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Admin</span>
    </div>

    @include('admin._nav')

    <h2 class="text-lg font-semibold text-white mb-6">Users with Roulettes</h2>

    @if($users->isEmpty())
        <p class="text-gray-500 text-sm">No users have created roulettes yet.</p>
    @else
    <div class="bg-white/3 rounded-xl overflow-hidden border border-white/5">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-left">
                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500">User</th>
                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-center">Roulettes</th>
                    <th class="py-2.5 px-4 text-xs font-medium text-gray-500 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" class="w-7 h-7 rounded-full ring-1 ring-white/10" alt="">
                                @endif
                                <div>
                                    <div class="text-white font-medium">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center text-gray-400">{{ $user->roulettes_count }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="text-xs text-gray-400 hover:text-accent transition-colors">View →</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
