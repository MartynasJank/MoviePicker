@extends('layouts.app')
@section('scripts')
<script src="/js/customOwlCarousel.js"></script>
@endsection
@section('content')
    <header class="masthead">
        <div class="container h-100">
            <div class="row h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-10 align-self-end">
                    <h1 class="text-uppercase font-weight-bold">Random Movie Picker</h1>
                    <hr class="divider my-4" />
                </div>
                <div class="col-lg-8 align-self-baseline">
                    <p class="font-weight-light mb-5">For evenings when you can't decide what to watch!</p>
                    <a class="btn btn-xl mb-4 main" href="/movie?i=new">Get a random movie</a>
                    <a class="btn btn-xl mb-4 main" href="/criteria">Enter details for a random movie</a>
                </div>
            </div>
        </div>
    </header>
    <section class="page-section py-5" id="about">
            <div class="row justify-content-center">
                <div class="container">
                <div class="col-lg-12">
                    <h2 class="text-center mt-0">Movies Trending Today</h2>
                    <hr class="divider my-4"/>
                    @include('includes.carousel', ['allMovies' => $trending, 'name' => 'owl-trending'])
                </div>
                </div>
        </div>
    </section>
    <section class="page-section custom-bg" id="about">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mt-0">About Us</h2>
                    <hr class="divider my-4" />
                    <p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                    <a class="btn btn-xl mb-3" href="/movie">Random Movie</a>
                    <a class="btn btn-xl mb-3" href="/criteria">Enter your preference</a>
                </div>
            </div>
        </div>
    </section>
    <section class="page-section" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mt-0">Contact Me</h2>
                    <hr class="divider my-4" />
                    <p class="mb-5"></p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 ml-auto text-center mb-5 mb-lg-0">
                    <i class="fas fa-phone fa-3x mb-3"></i>
                    <div>ENTER NUMBER HERE</div>
                </div>
                <div class="col-lg-4 mr-auto text-center mb-5">
                    <i class="fas fa-envelope fa-3x mb-3"></i>
                    <div>EMAIL@EMAIL.EMAIL HERE</div>
                </div>
                <div class="col-lg-4 mr-auto text-center">
                    <i class="fas fa-envelope fa-3x mb-3"></i>
                    <div>Github</div>
                </div>
            </div>
        </div>
    </section>
@endsection
