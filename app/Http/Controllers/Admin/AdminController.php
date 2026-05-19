<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total'     => Roulette::count(),
            'public'    => Roulette::where('is_public', true)->count(),
            'system'    => Roulette::where('is_system', true)->count(),
            'community' => Roulette::where('is_system', false)->count(),
        ];

        $rowOrder = Setting::get('roulette_row_order', []);

        return view('admin.dashboard', compact('stats', 'rowOrder'));
    }
}
