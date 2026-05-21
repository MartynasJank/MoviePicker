@extends('layouts.app')
@section('scripts')
    @vite(['resources/js/custom/carousel.js', 'resources/js/custom/watchlist.js'])
@endsection
@section('content')

    {{-- Hero --}}
    <section class="relative min-h-[88vh] flex items-center justify-center overflow-hidden">

        {{-- Poster collage background --}}
        @php $bgPosters = array_values(array_filter(array_slice($trending['results'] ?? [], 0, 12), fn($m) => !empty($m['poster_path']))); @endphp
        @if(count($bgPosters) >= 3)
            <div class="absolute inset-0 grid grid-cols-4 md:grid-cols-6 opacity-[0.18] pointer-events-none overflow-hidden" aria-hidden="true">
                @foreach($bgPosters as $m)
                    <img src="https://image.tmdb.org/t/p/w342{{ $m['poster_path'] }}"
                         alt=""
                         class="w-full h-full object-cover"
                         loading="lazy">
                @endforeach
            </div>
        @endif

        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(192,57,58,0.12)_0%,transparent_65%)]"></div>
        <div class="hero-fade absolute inset-0"></div>
        <div class="relative z-10 text-center px-4 py-20 max-w-3xl mx-auto">
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-5 leading-tight">
                <span class="text-white">Random</span><br>
                <span class="text-accent">Movie Picker</span>
            </h1>
            <p class="text-gray-400 text-lg mb-10">For evenings when you can't decide what to watch.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center">
                <a href="/movie?i=new" class="btn-accent long-single text-center">Get a random movie</a>
                <a href="/multiple?i=new" class="btn-secondary text-center">Random batch</a>
                <a href="/criteria" class="btn-secondary text-center">Enter criteria</a>
            </div>
        </div>
    </section>

    {{-- Mood shortcuts --}}
    <section class="max-w-7xl mx-auto px-4 py-12 border-b border-white/5">
        <div class="section-header">
            <h2 class="text-2xl font-bold text-white mb-3">I'm in the mood for…</h2>
            <div class="section-divider"></div>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-6">

            {{-- Funny --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to laugh at!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 13s1.5 3 4 3 4-3 4-3"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                    <span>Funny</span>
                </button>
            </form>

            {{-- Intense --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="without_genres[]" value="10749">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to keep you on edge!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    <span>Intense</span>
                </button>
            </form>

            {{-- Feel-good --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="18">
                <input type="hidden" name="vote_average_gte" value="7.5">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="80">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to warm your heart!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span>Feel-good</span>
                </button>
            </form>

            {{-- Dark --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="35">
                <input type="hidden" name="without_genres[]" value="16">
                <input type="hidden" name="without_genres[]" value="10751">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to haunt you!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <span>Dark</span>
                </button>
            </form>

            {{-- Romantic --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="10749">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="53">
                <input type="hidden" name="without_genres[]" value="28">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to fall in love with!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                    </svg>
                    <span>Romantic</span>
                </button>
            </form>

            {{-- Mindless --}}
            <form method="POST" action="/movie?a=1">
                @csrf
                <input type="hidden" name="with_genres[]" value="28">
                <input type="hidden" name="without_genres[]" value="27">
                <input type="hidden" name="without_genres[]" value="18">
                <button type="submit" class="mood-tile long-single" data-loading="Finding something to just enjoy!">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    <span>Mindless</span>
                </button>
            </form>

        </div>
    </section>

    {{-- Trending --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="section-header">
            <h2 class="text-2xl font-bold text-white mb-3">Trending Today</h2>
            <div class="section-divider"></div>
        </div>
        @include('includes.carousel', ['allMovies' => $trending, 'name' => 'swiper-trending', 'genres' => [], 'clearCriteria' => true, 'showScore' => true, 'showSave' => true, 'savedIds' => $savedIds])
    </section>

    {{-- About --}}
    <section class="bg-white/[0.02] border-y border-white/5 py-16">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <h2 class="text-2xl font-bold text-white mb-3">About MoviePickr</h2>
            <div class="section-divider mb-6"></div>
            <p class="text-gray-400 leading-relaxed mb-8">
                Sometimes the best movie is one you never would have chosen yourself.
                Tell MoviePickr what you're in the mood for and it'll find something worth watching.
                Filter by genre, decade, streaming service, or cast, or skip the filters entirely and let it surprise you.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center">
                <a href="/movie?i=new" class="btn-accent long-single text-center">Random Movie</a>
                <a href="/criteria" class="btn-secondary text-center">Set Preferences</a>
            </div>
        </div>
    </section>

    {{-- Contact --}}
    <section class="max-w-2xl mx-auto px-4 pt-16 pb-8">
        <div class="section-header text-center">
            <h2 class="text-2xl font-bold text-white mb-3">Contact</h2>
            <div class="section-divider mb-8"></div>
        </div>
        <form method="POST" action="/" class="flex flex-col gap-4">
            @csrf
            @include('errors.error')
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" class="input-dark" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" class="input-dark" required>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Subject" class="input-dark" required>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Message</label>
                <textarea name="message" placeholder="Your message…" rows="5" class="input-dark resize-none" required>{{ old('message') }}</textarea>
            </div>
            <div class="flex items-center justify-end gap-3">
                <p class="text-xs text-gray-600">Contact is currently under maintenance</p>
                <button type="submit" name="send" class="btn-accent opacity-40 cursor-not-allowed" disabled>Send Message</button>
            </div>
        </form>
    </section>

@endsection
