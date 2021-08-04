<!DOCTYPE html>
<html translate="no">
<head>
    <title>@yield('page_title', 'MoviePicker')</title>
    <link rel="icon" href="{{ URL::asset('/images/icon.png') }}"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="RANDOM MOVIE PICKER">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/app.js') }}"></script>
    @yield('scripts', '')
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-176903858-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-176903858-1');
    </script>
</head>
<body class="{{ Cookie::get('theme') == 'dark' ? 'dark-theme' : 'light-theme' }}">
    <div class="overlay"></div>
    <div class='loader'>
        <h1 class="loading-text">asdasdasdasd</h1>
        <div style="width: 250px; margin: auto;">
            <div class='loader--dot'></div>
            <div class='loader--dot'></div>
            <div class='loader--dot'></div>
            <div class='loader--dot'></div>
            <div class='loader--dot'></div>
            <div class='loader--dot'></div>
        </div>
    </div>
    <nav class="nav-nav navbar-expand-lg">
        <div class="container text-left d-flex custom-nav-container">
            <div class="logo">
                <a href="/">{{ config('app.name', 'Laravel') }}</a>
            </div>
            <div id="mainListDiv" class="main_list flex-grow-1 ml-3">
                <ul class="navlinks">
                    <li class="flex-grow-1 align-self-center custom-input">
                        <form action="/movie" method="POST" class="submit-search my-auto d-inline w-100" id="movie-search">
                            @csrf
                            <input
                                type="text"
                                class="movie-search bg-input movie-input border form-control align-self-center"
                                style="height: 35px;"
                                id="movie_search"
                                name="movie_search"
                                placeholder="Search movies by title"
                            >
                        </form>
                    </li>
                    <a href="/criteria" class="text-decoration-none"><li>Movie Criteria</li></a>
                    <a href="/movie?i=new" class="text-decoration-none long-single"><li>Random Movie</li></a>
                    <a href="/multiple?i=new" class="text-decoration-none"><li>Random Batch</li></a>
                    <a class="text-decoration-none">
                        <li>
                            <input
                                type="checkbox"
                                data-toggle="toggle"
                                data-on="Dark"
                                data-onstyle="outline-dark"
                                data-off="Light"
                                data-offstyle="outline-light"
                                id="theme-switcher"
                                data-height="16"
                                {{ Cookie::get('theme') == 'dark' ? 'checked' : '' }}
                            >
                        </li>
                    </a>
                </ul>
            </div>
            <span class="navTrigger">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>
    </nav>
    @yield('content')
    <footer class="page-footer font-small my-3">
        <div class="footer-copyright text-center">© Martynas Jankauskas 2020</div>
    </footer>
</body>
</html>
