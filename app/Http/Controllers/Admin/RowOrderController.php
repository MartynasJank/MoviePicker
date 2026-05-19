<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;
use Illuminate\Http\Request;

class RowOrderController extends Controller
{
    public function index()
    {
        $rowOrder = Setting::get('roulette_row_order', []);

        $counts = Roulette::all()
            ->groupBy(fn(Roulette $r) => $r->groupName())
            ->map(fn($items) => $items->count());

        return view('admin.rows.index', compact('rowOrder', 'counts'));
    }

    public function reorder(Request $request)
    {
        $rows = $request->validate(['rows' => 'required|array', 'rows.*' => 'string'])['rows'];
        Setting::set('roulette_row_order', array_values($rows));

        return response()->json(['ok' => true]);
    }
}
