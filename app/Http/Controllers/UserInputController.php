<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class UserInputController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(session('userInput'));
    }
}
