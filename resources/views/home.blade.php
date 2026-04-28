@extends('layouts.app')
@section('scripts')
<script src="/js/customOwlCarousel.js"></script>
<script src="/js/customForm.js"></script>
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-176903858-1"></script>
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
                    <p class="font-weight-bold mb-5">For evenings when you can't decide what to watch!</p>
                    <a class="btn btn-xl mb-4 main long-single" href="/movie?i=new">Get a random movie</a>
                    <a class="btn btn-xl mb-4 main" href="/multiple?i=new">Get a random movie batch</a>
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
                    @include('includes.carousel', ['allMovies' => $trending, 'name' => 'owl-trending', 'genres' => []])
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
                    <p class="mb-4">MoviePickr.com helps people to find the perfect movie for everyone. You can select movie dates, streaming platforms, genres, actors and we will find what you're looking for!</p>
                    <a class="btn btn-xl mb-3 long-single" href="/movie">Random Movie</a>
                    <a class="btn btn-xl mb-3" href="/criteria">Enter your preference</a>
                </div>
            </div>
        </div>
    </section>
    <section class="page-section py-5" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mt-0">Contact Me</h2>
                    <hr class="divider my-4" />
                    <p class="mb-5"></p>
                </div>
            </div>
            <form method="post" action="/">
                @csrf
                <div class="form-row">
                    <div class="form-group col-lg-6">
                        <label>Name</label>
                        <input type="text" name="name" placeholder="Name" class="form-control bg-input movie-input border" value="" required/>
                    </div>
                    <div class="form-group col-lg-6">
                        <label>Email</label>
                        <input type="text" name="email" placeholder="Email" class="form-control bg-input movie-input border" value="" required/>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control bg-input movie-input border" placeholder="Subject" value="" required/>
                </div>
                <div class="form-groug mb-3">
                    <label>Message</label>
                    <textarea style="padding: 10px 20px;" name="message" class="form-control bg-input movie-input border" placeholder="Message..." rows="5" required></textarea>
                </div>
                <div class="form-group text-right">
                    <input type="submit" name="send" class="btn btn-secondary" value="Send" />
                </div>
            </form>
        </div>
    </section>
@endsection
