<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    // Page pilihan: main solo (vs bot) atau main dengan kawan (akan ditambah lain hari).
    public function unoMenu(Request $request)
    {
        return view('game.uno-menu');
    }

    // Game UNO solo vs bot - semua logik di client-side (JS), tak perlukan backend
    // sebab cuma 1 player + bot dalam sesi browser sendiri.
    public function unoSolo(Request $request)
    {
        return view('game.uno-solo');
    }
}
