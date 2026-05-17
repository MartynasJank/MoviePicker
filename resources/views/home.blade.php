@extends('layouts.app')
@section('scripts')
    @vite(['resources/js/custom/carousel.js'])
@endsection
@section('content')

    {{-- Hero --}}
    <section class="relative min-h-[88vh] flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(192,57,58,0.12)_0%,transparent_65%)]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#0f0f0f]/40 to-[#0f0f0f]"></div>
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

    {{-- Trending --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="section-header">
            <h2 class="text-2xl font-bold text-white mb-3">Trending Today</h2>
            <div class="section-divider"></div>
        </div>
        @include('includes.carousel', ['allMovies' => $trending, 'name' => 'swiper-trending', 'genres' => []])
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
    <section class="max-w-2xl mx-auto px-4 py-16">
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
            <div class="flex justify-end">
                <button type="submit" name="send" class="btn-accent">Send Message</button>
            </div>
        </form>
    </section>

@endsection
