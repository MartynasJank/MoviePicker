<?php

namespace App\Http\Controllers;

use App\TMDB;
use Illuminate\Http\Request;

class CriteriaController extends Controller
{
    public function index(Tmdb $tmdb)
    {
        if (session('userInput') !== null) {
            session()->forget('userInput');
        }
        $genres = $tmdb->genres();
        return view('criteria', compact('genres'));
    }
}
