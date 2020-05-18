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
</head>
<body class="{{ Cookie::get('theme') == 'dark' ? 'dark-theme' : 'light-theme' }}">
    <nav class="nav-nav">
        <div class="container">
            <div class="logo">
                <a href="/">{{ config('app.name', 'Laravel') }}</a>
            </div>
            <div id="mainListDiv" class="main_list">
                <ul class="navlinks">
                    <a href="/criteria" class="text-decoration-none"><li>Movie Criteria</li></a>
                    <a href="/movie?i=new" class="text-decoration-none"><li>Random Movie</li></a>
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
        </div>
    </nav>
    @yield('content')
    <footer class="page-footer font-small my-3">
        <div class="footer-copyright text-center">© Martynas Jankauskas 2020</div>
    </footer>
</body>
</html>
