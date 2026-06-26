<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\Notification;
use App\Models\User;
use App\Support\ChessEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChessController extends Controller
{
    // Menu: pilih main solo (bot) atau jemput kawan, + senarai jemputan & permainan aktif.
    public function menu(Request $request)
    {
        $user = $request->user();

        $friends = $user->friendSessions()->get()->map(fn ($m) => $m->partnerOf($user))->values();

        $pendingReceived = GameRoomPlayer::with(['room.creator'])
            ->where('user_id', $user->id)
            ->where('status', 'invited')
            ->whereHas('room', fn ($q) => $q->where('game', 'chess')->where('status', 'waiting'))
            ->latest()
            ->get();

        $pendingSent = GameRoom::with(['players.user'])
            ->where('game', 'chess')
            ->where('status', 'waiting')
            ->where('created_by', $user->id)
            ->latest()
            ->get();

        $activeRooms = GameRoom::with(['players.user'])
            ->where('game', 'chess')
            ->where('status', 'active')
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        return view('game.chess-menu', compact('friends', 'pendingReceived', 'pendingSent', 'activeRooms'));
    }

    // Solo lawan bot - terus mula, tak perlu jemputan/tunggu.
    public function startSolo(Request $request)
    {
        $user = $request->user();

        $room = DB::transaction(function () use ($user) {
            $room = GameRoom::create([
                'game' => 'chess',
                'status' => 'active',
                'created_by' => $user->id,
                'min_players' => 2,
                'max_players' => 2,
                'state' => ChessEngine::initialState(),
                'started_at' => now(),
            ]);

            GameRoomPlayer::create(['game_room_id' => $room->id, 'user_id' => $user->id, 'seat' => 0, 'status' => 'joined']);
            GameRoomPlayer::create(['game_room_id' => $room->id, 'is_bot' => true, 'seat' => 1, 'status' => 'joined']);

            return $room;
        });

        return redirect()->route('game.chess.show', $room->id);
    }

    // Jemput kawan untuk main (kawan jadi hitam, kita putih).
    public function invite(Request $request, User $targetUser)
    {
        $user = $request->user();
        abort_if($targetUser->id === $user->id, 403);

        $isFriend = $user->friendSessions()
            ->where(function ($q) use ($targetUser) {
                $q->where('user_a_id', $targetUser->id)->orWhere('user_b_id', $targetUser->id);
            })->exists();
        abort_unless($isFriend, 403, 'Hanya boleh jemput kawan.');

        $existing = GameRoom::where('game', 'chess')
            ->whereIn('status', ['waiting', 'active'])
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('players', fn ($q) => $q->where('user_id', $targetUser->id))
            ->first();

        if ($existing) {
            return redirect()->route('game.chess.show', $existing->id)
                ->with('status', 'Anda sudah ada permainan chess dengan '.$targetUser->displayName().'.');
        }

        $room = DB::transaction(function () use ($user, $targetUser) {
            $room = GameRoom::create([
                'game' => 'chess',
                'status' => 'waiting',
                'created_by' => $user->id,
                'min_players' => 2,
                'max_players' => 2,
            ]);

            GameRoomPlayer::create(['game_room_id' => $room->id, 'user_id' => $user->id, 'seat' => 0, 'status' => 'joined']);
            GameRoomPlayer::create(['game_room_id' => $room->id, 'user_id' => $targetUser->id, 'seat' => 1, 'status' => 'invited', 'invited_by' => $user->id]);

            return $room;
        });

        Notification::send(
            $targetUser->id,
            'chess_invite',
            $user->displayName().' menjemput anda main Chess ♟️',
            'Tekan untuk terima atau tolak jemputan.',
            route('game.chess.show', $room->id)
        );

        return redirect()->route('game.chess.menu')->with('status', 'Jemputan chess dihantar kepada '.$targetUser->displayName().' 🎉');
    }

    public function accept(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        abort_unless($room->status === 'waiting' && $player->status === 'invited', 422);

        $room->update([
            'status' => 'active',
            'state' => ChessEngine::initialState(),
            'started_at' => now(),
        ]);
        $player->update(['status' => 'joined']);

        $creator = $room->creator;
        Notification::send(
            $creator->id,
            'chess_accept',
            $user->displayName().' terima jemputan Chess anda!',
            'Permainan sudah boleh dimulakan.',
            route('game.chess.show', $room->id)
        );

        return redirect()->route('game.chess.show', $room->id);
    }

    public function decline(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        abort_unless($room->status === 'waiting' && $player->status === 'invited', 422);

        $player->update(['status' => 'declined']);
        $room->update(['status' => 'finished', 'ended_at' => now()]);

        return redirect()->route('game.chess.menu')->with('status', 'Jemputan chess ditolak.');
    }

    public function show(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);

        $opponent = $room->players->first(fn ($p) => $p->id !== $player->id);
        $myColor = $player->seat === 0 ? 'w' : 'b';

        return view('game.chess-room', [
            'room' => $room,
            'player' => $player,
            'opponent' => $opponent,
            'myColor' => $myColor,
            'vsBot' => $opponent?->is_bot ?? false,
        ]);
    }

    public function poll(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        $opponent = $room->players->first(fn ($p) => $p->id !== $player->id);

        return response()->json([
            'status' => $room->status,
            'state' => $room->state,
            'my_color' => $player->seat === 0 ? 'w' : 'b',
            'opponent_name' => $opponent?->is_bot ? 'Bot' : $opponent?->user?->displayName(),
            'winner_user_id' => $room->winner_user_id,
        ]);
    }

    public function legalMoves(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        abort_unless($room->status === 'active', 422);

        $data = $request->validate(['row' => ['required', 'integer', 'min:0', 'max:7'], 'col' => ['required', 'integer', 'min:0', 'max:7']]);

        $myColor = $player->seat === 0 ? 'w' : 'b';
        $state = $room->state;

        if ($state['turn'] !== $myColor) {
            return response()->json(['moves' => []]);
        }

        $moves = collect(ChessEngine::legalMovesFrom($state, $data['row'], $data['col']))
            ->map(fn ($m) => ['row' => $m['to'][0], 'col' => $m['to'][1], 'promotion' => $m['promotion']])
            ->values();

        return response()->json(['moves' => $moves]);
    }

    public function move(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        abort_unless($room->status === 'active', 422);

        $data = $request->validate([
            'from' => ['required', 'array', 'size:2'],
            'to' => ['required', 'array', 'size:2'],
            'promotion' => ['nullable', 'in:Q,R,B,N'],
        ]);

        $myColor = $player->seat === 0 ? 'w' : 'b';
        $state = $room->state;

        if ($state['turn'] !== $myColor) {
            return response()->json(['error' => 'Bukan giliran anda.'], 422);
        }

        try {
            $state = ChessEngine::applyMove($state, $data['from'], $data['to'], $data['promotion'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Langkah tidak sah.'], 422);
        }

        // Solo vs bot - terus balas dengan langkah bot (sinkron, tak perlu poll).
        $opponent = $room->players->first(fn ($p) => $p->id !== $player->id);
        if ($opponent?->is_bot && $state['status'] === 'active' && $state['turn'] !== $myColor) {
            $botMove = ChessEngine::pickBotMove($state);
            $state = ChessEngine::applyMove($state, $botMove['from'], $botMove['to'], $botMove['promotion']);
        }

        $room->state = $state;

        if (in_array($state['status'], ['checkmate', 'stalemate', 'draw'], true)) {
            $room->status = 'finished';
            $room->ended_at = now();
            if ($state['status'] === 'checkmate') {
                $winnerSeat = $state['winner'] === 'w' ? 0 : 1;
                $winnerPlayer = $room->players->first(fn ($p) => $p->seat === $winnerSeat);
                $room->winner_user_id = $winnerPlayer?->user_id;
            }
        }

        $room->save();

        return response()->json([
            'status' => $room->status,
            'state' => $room->state,
            'winner_user_id' => $room->winner_user_id,
        ]);
    }

    public function resign(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->authorizePlayer($room, $user);
        abort_unless($room->status === 'active', 422);

        $opponent = $room->players->first(fn ($p) => $p->id !== $player->id);

        $room->update([
            'status' => 'finished',
            'ended_at' => now(),
            'winner_user_id' => $opponent?->is_bot ? null : $opponent?->user_id,
        ]);

        return redirect()->route('game.chess.show', $room->id);
    }

    private function authorizePlayer(GameRoom $room, $user): GameRoomPlayer
    {
        $room->loadMissing('players.user');
        $player = $room->players->first(fn ($p) => $p->user_id === $user->id);
        abort_if($player === null, 403);

        return $player;
    }
}
