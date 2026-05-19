<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::whereHas('roulettes')
            ->withCount('roulettes')
            ->orderByDesc('roulettes_count')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $roulettes = Roulette::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $rowOrder = Setting::get('user_row_order.' . $user->id, []);

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->row ?? 'Uncategorised');

        $ordered = collect($rowOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        foreach ($grouped as $name => $items) {
            if (!in_array($name, $rowOrder)) {
                $ungrouped = $ordered->get('Uncategorised', collect())->merge($items);
                $ordered->put('Uncategorised', $ungrouped);
            }
        }

        return view('admin.users.show', compact('user', 'ordered', 'roulettes'));
    }

    public function destroyRoulette(User $user, Roulette $roulette)
    {
        abort_if($roulette->user_id !== $user->id, 404);
        $roulette->delete();
        return redirect()->route('admin.users.show', $user)->with('success', "Deleted \"{$roulette->name}\".");
    }
}
