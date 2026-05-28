<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class NoResultsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $type = $request->query('type', 'movie');
        $isTv = $type === 'tv';

        return view('no-results', [
            'rollAgainUrl' => $isTv ? url('/tv/pick') : url('/movie'),
            'criteriaUrl'  => $isTv ? url('/tv/criteria') : url('/criteria'),
            'randomUrl'    => $isTv ? url('/tv/pick?i=new') : url('/movie?i=new'),
        ]);
    }
}
