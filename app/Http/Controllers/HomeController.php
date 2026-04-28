<?php

namespace App\Http\Controllers;

use App\TMDB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class HomeController extends Controller
{
    public function index(TMDB $tmdb, Request $request)
    {
        $trending = $tmdb->trending();
        return view('home', compact('trending'));
    }
}
