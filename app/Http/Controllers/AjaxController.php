<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function index(){
        $info = session('userInput');
        return \Response::json($info);
    }
}
