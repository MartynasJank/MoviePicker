<!DOCTYPE html>
<html lang="en" translate="no" data-theme="dark">
<head>
    <title>@yield('page_title', 'MoviePickr')</title>
    <link rel="icon" href="{{ URL::asset('/images/icon.png') }}"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>!function(){var m=document.cookie.match(/(?:^|; )theme=([^;]+)/);if(m)document.documentElement.dataset.theme=m[1]}()</script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/custom/criteriaForm.js'])
    @yield('scripts', '')
</head>
<body class="bg-[#0f0f0f] text-white">
    @yield('content')
</body>
</html>
