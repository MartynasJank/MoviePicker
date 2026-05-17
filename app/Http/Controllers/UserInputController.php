<?php

namespace App\Http\Controllers;

class UserInputController extends Controller
{
    public function index()
    {
        return response()->json(session('userInput'));
    }
}
