<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;
use Illuminate\Http\Request;

class RowOrderController extends Controller
{
    private const MOVIE_DEFAULT = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'Community', 'By Genre'];
    private const TV_DEFAULT    = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World TV', 'Anime', 'By Genre'];

    public function index(Request $request)
    {
        $mediaType = $request->input('type', 'movie');
        $settingKey = $mediaType === 'tv' ? 'roulette_tv_row_order' : 'roulette_row_order';
        $default    = $mediaType === 'tv' ? self::TV_DEFAULT : self::MOVIE_DEFAULT;

        $rowOrder = Setting::get($settingKey, $default);

        $counts = Roulette::where('media_type', $mediaType)
            ->get()
            ->groupBy(fn(Roulette $r) => $r->groupName())
            ->map(fn($items) => $items->count());

        return view('admin.rows.index', compact('rowOrder', 'counts', 'mediaType'));
    }

    public function reorder(Request $request)
    {
        $data       = $request->validate(['rows' => 'required|array', 'rows.*' => 'string', 'type' => 'nullable|string']);
        $rows       = array_values($data['rows']);
        $settingKey = ($data['type'] ?? 'movie') === 'tv' ? 'roulette_tv_row_order' : 'roulette_row_order';

        Setting::set($settingKey, $rows);

        return response()->json(['ok' => true]);
    }
}
