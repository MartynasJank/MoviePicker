@extends('layouts.app')
@section('page_title', 'Roulettes — MoviePickr')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-white">Movie Roulettes</h1>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/20">Beta</span>
        </div>
        <p class="text-gray-500 text-sm mt-1">Curated collections — just hit Roll. More coming soon.</p>
        <div class="section-divider mt-3"></div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 md:gap-5">

        {{-- Netflix Horror --}}
        <div class="card card-hover overflow-hidden flex flex-row md:flex-col">
            <div class="relative w-28 flex-shrink-0 md:w-auto md:h-44 self-stretch overflow-hidden">
                <img src="https://static1.srcdn.com/wordpress/wp-content/uploads/2019/10/the-grudge-banner.jpg?q=50&fit=crop&w=740&h=370&dpr=1.5"
                    class="absolute inset-0 w-full h-full object-cover opacity-70"
                    alt="Horror">
                <div class="absolute inset-0 bg-gradient-to-r md:bg-gradient-to-t from-[#111]/90 to-transparent"></div>
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png"
                    class="absolute bottom-2 right-2 h-5 md:h-8 drop-shadow-lg">
            </div>
            <div class="p-4 md:p-5 flex flex-col flex-1">
                <h2 class="text-base font-semibold text-white mb-1.5 md:mb-2">Netflix Horror</h2>
                <p class="text-sm text-gray-400 leading-relaxed flex-1">
                    Supernatural encounters, psychological thrillers, and international scares from Netflix's horror selection.
                </p>
                <a href="/roulettes/netflix/horror" class="btn-accent mt-3 md:mt-4 self-start text-sm">Roll</a>
            </div>
        </div>

        {{-- Netflix Documentaries --}}
        <div class="card card-hover overflow-hidden flex flex-row md:flex-col">
            <div class="relative w-28 flex-shrink-0 md:w-auto md:h-44 self-stretch overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"></div>
                <div class="absolute inset-0 bg-gradient-to-r md:bg-gradient-to-t from-[#111]/90 to-transparent"></div>
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png"
                    class="absolute bottom-2 right-2 h-5 md:h-8 drop-shadow-lg">
            </div>
            <div class="p-4 md:p-5 flex flex-col flex-1">
                <h2 class="text-base font-semibold text-white mb-1.5 md:mb-2">Netflix Documentaries</h2>
                <p class="text-sm text-gray-400 leading-relaxed flex-1">
                    True crime, environmental issues, social justice, and historical events — told with compelling narratives.
                </p>
                <a href="/roulettes/netflix/doc" class="btn-accent mt-3 md:mt-4 self-start text-sm">Roll</a>
            </div>
        </div>

        {{-- Netflix Anime --}}
        <div class="card card-hover overflow-hidden flex flex-row md:flex-col">
            <div class="relative w-28 flex-shrink-0 md:w-auto md:h-44 self-stretch overflow-hidden">
                <img src="https://i.pinimg.com/originals/4c/8e/26/4c8e267ee4446e733bb17564337083f7.jpg"
                    class="absolute inset-0 w-full h-full object-cover opacity-70"
                    alt="Anime">
                <div class="absolute inset-0 bg-gradient-to-r md:bg-gradient-to-t from-[#111]/90 to-transparent"></div>
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png"
                    class="absolute bottom-2 right-2 h-5 md:h-8 drop-shadow-lg">
            </div>
            <div class="p-4 md:p-5 flex flex-col flex-1">
                <h2 class="text-base font-semibold text-white mb-1.5 md:mb-2">Netflix Anime Movies</h2>
                <p class="text-sm text-gray-400 leading-relaxed flex-1">
                    Action, fantasy, romance, sci-fi — a rich selection of anime films spanning classics and Netflix originals.
                </p>
                <a href="/roulettes/netflix/animovies" class="btn-accent mt-3 md:mt-4 self-start text-sm">Roll</a>
            </div>
        </div>

    </div>
</div>
@endsection
