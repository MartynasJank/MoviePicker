@extends('layouts.app')
@section('page_title', 'Privacy Policy — MoviePickr')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    <h1 class="text-3xl font-bold text-white mb-2">Privacy Policy</h1>
    <p class="text-gray-500 text-sm mb-10">Last updated: {{ date('F j, Y') }}</p>

    <div class="prose prose-invert max-w-none space-y-8 text-gray-400 leading-relaxed">

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">What MoviePickr is</h2>
            <p>MoviePickr is a free tool that helps you pick a random movie or TV show to watch. It uses data from TMDB, OMDb, and YouTube to show you information about titles. No account is required to use the core features.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">What data we collect</h2>
            <p class="mb-3"><strong class="text-white">Without an account:</strong> We don't collect any personal data. If you accept analytics cookies, Google Analytics collects anonymised usage data (pages visited, features used). You can decline this.</p>
            <p class="mb-3"><strong class="text-white">With a Google account:</strong> When you sign in, we store your name, email address, and profile picture from Google so we can show your watchlist and roulettes. We don't have access to your Google password.</p>
            <p><strong class="text-white">Watchlist and roulettes:</strong> If you're signed in, the movies and shows you save are stored in our database so you can access them across devices.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">Cookies</h2>
            <p class="mb-3">We use three types of cookies:</p>
            <ul class="list-disc list-inside space-y-2 ml-2">
                <li><strong class="text-white">Necessary:</strong> Session cookie (keeps you logged in), CSRF token (security), theme preference (dark/light mode). These can't be turned off.</li>
                <li><strong class="text-white">Analytics:</strong> Google Analytics 4 cookies that help us understand how people use MoviePickr. Only set if you accept them. You can change this at any time via the Cookie settings link in the footer.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">Third-party services</h2>
            <ul class="list-disc list-inside space-y-2 ml-2">
                <li><strong class="text-white">TMDB</strong> — provides movie and TV show data, posters, and cast information</li>
                <li><strong class="text-white">OMDb</strong> — provides Rotten Tomatoes ratings and additional plot information</li>
                <li><strong class="text-white">YouTube</strong> — trailers are embedded from YouTube and subject to Google's privacy policy</li>
                <li><strong class="text-white">JustWatch</strong> — provides streaming availability data</li>
                <li><strong class="text-white">Google Analytics</strong> — usage analytics, only active with your consent</li>
                <li><strong class="text-white">Google OAuth</strong> — sign-in only, we don't access anything beyond your basic profile</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">Your rights (GDPR)</h2>
            <p class="mb-3">If you're in the EU or UK, you have the right to:</p>
            <ul class="list-disc list-inside space-y-2 ml-2">
                <li>Access the personal data we hold about you</li>
                <li>Ask us to delete your account and all associated data</li>
                <li>Withdraw analytics consent at any time via Cookie settings in the footer</li>
            </ul>
            <p class="mt-3">To exercise any of these rights, email us at <a href="mailto:martynasjank@gmail.com" class="text-accent hover:underline">martynasjank@gmail.com</a>.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">Data retention</h2>
            <p>Analytics data is retained for 14 months. Account data is kept as long as your account exists. If you delete your account, all your watchlist and roulette data is deleted within 30 days.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white mb-3">Contact</h2>
            <p>Questions about this policy? Email <a href="mailto:martynasjank@gmail.com" class="text-accent hover:underline">martynasjank@gmail.com</a>.</p>
        </section>

    </div>
</div>
@endsection
