@extends('layouts.app')
@section('page_title', 'Swipe v2 — MoviePickr Lab')
@section('hide_footer', 'true')
@section('navigation_class', 'hidden sm:block')
@section('scripts')
    @vite('resources/js/custom/swipeV2.js')
@endsection

@section('content')
<div id="swipe-v2" class="fixed inset-x-0 bottom-0 top-0 z-40 flex flex-col bg-[#090909] text-white sm:top-16"
     data-feed-url="{{ route('admin.swipe-v2.feed') }}"
     data-detail-url="{{ route('admin.swipe-v2.movie', ['id' => 0]) }}"
     data-save-url="{{ route('watchlist.add') }}"
     data-saved="{{ json_encode($watchlistIds) }}">
    <header class="pointer-events-none absolute inset-x-0 top-0 z-30 flex shrink-0 items-center justify-between gap-3 bg-linear-to-b from-black/70 to-transparent px-4 pb-8 pt-[max(1rem,env(safe-area-inset-top))] sm:pointer-events-auto sm:static sm:border-b sm:border-white/10 sm:bg-none sm:px-8 sm:py-3">
        <div class="flex items-center gap-3">
            <h1 class="text-base font-bold tracking-tight">Swipe <span class="text-white/60 sm:text-accent">v2</span></h1>
            <span class="rounded-full border border-white/20 bg-black/20 px-2 py-1 text-[9px] font-semibold uppercase tracking-widest text-white/70 sm:border-accent/30 sm:bg-accent/10 sm:text-accent">Admin lab</span>
        </div>
        <a href="{{ route('admin.dashboard') }}" aria-label="Close Swipe v2 and return to admin" class="pointer-events-auto flex h-11 w-11 items-center justify-center rounded-full text-white/80 hover:bg-white/10 sm:h-auto sm:w-auto sm:text-xs sm:text-gray-400">
            <svg class="h-5 w-5 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
            <span class="hidden sm:inline">Back to admin ↗</span>
        </a>
    </header>

    <div class="relative flex min-h-0 flex-1 justify-center">
        <aside class="hidden w-64 shrink-0 flex-col justify-center gap-5 px-8 xl:flex">
            <span class="text-xs uppercase tracking-[0.25em] text-accent">Find your next film</span>
            <p class="text-3xl font-bold leading-tight">Let the trailer<br>make the pitch.</p>
            <p class="text-sm leading-relaxed text-gray-500">A little preview. A new favourite. Keep scrolling until something clicks.</p>
            <div class="h-px bg-white/10"></div>
            <p class="text-xs leading-relaxed text-gray-500">Scroll or swipe outside the video.<br>Use ↑ ↓ to move between films.<br>Unmute with the player controls.</p>
        </aside>

        <div id="trailer-feed" tabindex="0" aria-label="Movie trailer feed" class="h-full min-h-0 w-full max-w-3xl snap-y snap-mandatory overflow-y-auto overscroll-y-contain [overflow-anchor:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden focus-visible:outline-accent sm:border-x sm:border-white/5">
            <div id="feed-status" class="flex h-full snap-start flex-col items-center justify-center gap-4 px-6 text-center">
                <p id="feed-message" role="status" class="text-sm text-gray-400">Finding something worth watching…</p>
                <button id="feed-retry" class="btn-secondary hidden">Try again</button>
            </div>
        </div>

        <aside class="hidden w-24 shrink-0 flex-col items-center justify-center gap-3 sm:flex">
            <button data-direction="-1" aria-label="Previous movie" class="h-11 w-11 rounded-full border border-white/10 text-xl text-gray-300 hover:bg-white/10">↑</button>
            <button data-direction="1" aria-label="Next movie" class="h-11 w-11 rounded-full border border-white/10 text-xl text-gray-300 hover:bg-white/10">↓</button>
        </aside>
    </div>
    <nav aria-label="Swipe navigation" class="flex shrink-0 items-center justify-around border-t border-white/10 bg-[#090909] pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2 sm:hidden">
        <span aria-current="page" class="flex min-w-20 flex-col items-center gap-1 text-[10px] font-semibold text-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="m13 2-3 8H4l7 12 3-8h6L13 2Z"/></svg>
            Discover
        </span>
        <a href="{{ route('watchlist') }}" class="flex min-h-11 min-w-20 flex-col items-center justify-center gap-1 text-[10px] text-white/50 hover:text-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17l-6-4-6 4V4Z"/></svg>
            Watchlist
        </a>
        <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 min-w-20 flex-col items-center justify-center gap-1 text-[10px] text-white/50 hover:text-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            Admin
        </a>
    </nav>
    <p id="swipe-notice" role="status" class="pointer-events-none absolute inset-x-4 bottom-20 z-20 rounded-xl bg-black/90 p-3 text-center text-sm text-white empty:hidden"></p>
</div>

<template id="trailer-card-template">
    <article class="relative isolate flex h-full min-h-full snap-start snap-always flex-col overflow-hidden bg-[#111111]">
        <img data-backdrop alt="" class="pointer-events-none absolute inset-0 -z-20 hidden h-full w-full scale-110 object-cover opacity-40 blur-xl sm:invisible">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-linear-to-t from-black via-black/25 to-black/20 sm:hidden"></div>
        <div class="hidden shrink-0 items-center justify-between px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500 sm:flex">
            <span>Movie discovery</span><span data-position></span>
        </div>
        <div class="absolute inset-x-0 bottom-[38%] top-16 flex items-center justify-center sm:relative sm:inset-auto sm:min-h-[200px] sm:flex-1 sm:bg-black">
            <div class="relative aspect-video max-h-full min-h-[200px] w-full">
                <div data-player class="absolute inset-0"></div>
                <button data-play type="button" aria-label="Play trailer" class="absolute inset-0 z-10 flex hidden touch-pan-y items-center justify-center text-white focus-visible:outline-2 focus-visible:outline-white sm:hidden">
                    <span data-play-icon class="flex h-16 w-16 items-center justify-center rounded-full bg-black/40 backdrop-blur-sm">
                        <svg aria-hidden="true" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="m8 5 11 7-11 7V5Z"/></svg>
                    </span>
                </button>
                <button data-mute type="button" aria-label="Unmute trailer" aria-pressed="false" class="absolute bottom-2 right-3 z-20 flex hidden h-11 w-11 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-sm focus-visible:outline-2 focus-visible:outline-white sm:hidden">
                    <svg data-sound-off aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m11 5-6 4H2v6h3l6 4V5Zm6 4 5 6m0-6-5 6"/></svg>
                    <svg data-sound-on aria-hidden="true" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m11 5-6 4H2v6h3l6 4V5Zm5 3a5 5 0 0 1 0 8m3-11a9 9 0 0 1 0 14"/></svg>
                </button>
            </div>
            <div data-trailer-status class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center">
                <p data-trailer-message class="text-sm text-gray-400">Loading trailer…</p>
                <button data-retry class="btn-secondary hidden text-xs">Retry trailer</button>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 flex min-h-0 flex-col gap-2 bg-linear-to-t from-black via-black/80 to-transparent pb-5 pl-4 pr-20 pt-10 sm:static sm:shrink-0 sm:gap-3 sm:bg-none sm:px-7 sm:py-5">
            <div class="flex items-center gap-2 text-[10px] font-semibold tracking-wide sm:hidden">
                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span><span class="text-white/70">YOUR NEXT WATCH</span>
            </div>
            <div class="flex flex-col items-start gap-2 sm:flex-row sm:justify-between sm:gap-3">
                <h2 data-title class="line-clamp-2 text-2xl font-bold leading-tight tracking-tight text-white"></h2>
                <span data-rating class="shrink-0 rounded-md bg-white/10 px-2 py-1 text-[10px] font-semibold text-yellow-300 backdrop-blur-sm sm:bg-yellow-400/10 sm:text-xs"></span>
            </div>
            <p data-meta class="line-clamp-2 text-[11px] leading-relaxed text-white/60 sm:truncate sm:text-xs sm:text-gray-400"></p>
            <p data-overview class="line-clamp-2 text-xs leading-relaxed text-white/80 sm:text-sm sm:text-gray-400"></p>
            <div class="absolute bottom-8 right-2 flex w-14 flex-col items-center gap-5 sm:static sm:w-auto sm:flex-row sm:gap-2">
                <button data-save disabled aria-label="Save to watchlist" class="group flex min-h-11 w-14 flex-col items-center gap-1 text-white disabled:opacity-40 data-[saved=true]:text-accent sm:w-auto sm:flex-1 sm:flex-row sm:justify-center sm:gap-2 sm:rounded-xl sm:bg-accent sm:px-3 sm:py-2.5 sm:text-white sm:data-[saved=true]:text-white">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 backdrop-blur-md transition-transform active:scale-90 sm:h-5 sm:w-5 sm:bg-transparent">
                        <svg class="h-7 w-7 group-data-[saved=true]:fill-current sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                    </span>
                    <span data-save-label class="text-[10px] font-semibold sm:text-xs">Save</span>
                </button>
                <a data-details aria-label="View movie details" class="flex min-h-11 w-14 flex-col items-center gap-1 text-white sm:w-auto sm:flex-row sm:gap-2 sm:rounded-xl sm:bg-white/10 sm:px-3 sm:py-2.5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 backdrop-blur-md active:scale-90 sm:hidden"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></svg></span>
                    <span class="text-[10px] font-semibold sm:text-xs">Details</span>
                </a>
                <button data-next aria-label="Skip to next movie" class="flex min-h-11 w-14 flex-col items-center gap-1 text-white sm:w-auto sm:flex-row sm:gap-2 sm:rounded-xl sm:bg-white/10 sm:px-3 sm:py-2.5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 backdrop-blur-md active:scale-90 sm:hidden"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4v16m-6-6 6 6 6-6"/></svg></span>
                    <span class="text-[10px] font-semibold sm:text-xs">Skip</span>
                </button>
            </div>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[10px] text-white/40 sm:mt-0 sm:text-gray-600">
                <span>↑ Swipe for the next film</span>
                <a data-youtube target="_blank" rel="noopener noreferrer" class="hidden text-white/60 hover:text-white sm:text-gray-400">YouTube ↗</a>
            </div>
        </div>
    </article>
</template>
@endsection
