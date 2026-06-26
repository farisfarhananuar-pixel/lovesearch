<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use Illuminate\Http\Request;

class GameController extends Controller
{
    // Hub kecil - pilih nak main UNO atau Chess.
    public function hub(Request $request)
    {
        return view('game.hub');
    }

    // Page pilihan: main solo (vs bot) atau main dengan kawan (2-6 orang).
    public function unoMenu(Request $request)
    {
        $user = $request->user();

        $friends = $user->friendSessions()->get()->map(fn ($m) => $m->partnerOf($user))->values();

        $pendingReceived = GameRoomPlayer::with(['room.creator'])
            ->where('user_id', $user->id)
            ->where('status', 'invited')
            ->whereHas('room', fn ($q) => $q->where('game', 'uno')->where('status', 'waiting'))
            ->latest()
            ->get();

        $myRooms = GameRoom::with(['players.user'])
            ->where('game', 'uno')
            ->whereIn('status', ['waiting', 'active'])
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id)->whereIn('status', ['joined']))
            ->latest()
            ->get();

        return view('game.uno-menu', compact('friends', 'pendingReceived', 'myRooms'));
    }

    // Game UNO solo vs bot - semua logik di client-side (JS), tak perlukan backend
    // sebab cuma 1 player + bot dalam sesi browser sendiri.
    public function unoSolo(Request $request)
    {
        return view('game.uno-solo');
    }
}
