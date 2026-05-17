<?php

namespace App\Http\Controllers;

class AjaxController extends Controller
{
    public function index()
    {
        return response()->json(session('userInput'));
    }
}
