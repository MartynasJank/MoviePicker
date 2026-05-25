<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class UserInputController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $type = request('type');
        return response()->json($type === 'tv' ? session('tvInput') : session('userInput'));
    }
}
