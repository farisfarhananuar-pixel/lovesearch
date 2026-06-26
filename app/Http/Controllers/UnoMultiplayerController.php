<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\Notification;
use App\Support\UnoEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnoMultiplayerController extends Controller
{
    // Cipta bilik baru & jemput kawan-kawan terpilih (maksimum 5 org + kita = 6).
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'friend_ids' => ['required', 'array', 'min:1', 'max:5'],
            'friend_ids.*' => ['integer'],
        ]);

        $friendIds = $user->friendSessions()->get()->map(fn ($m) => $m->partnerOf($user)->id)->values()->all();
        $invitedIds = array_values(array_intersect($data['friend_ids'], $friendIds));

        abort_if(empty($invitedIds), 422, 'Sila pilih sekurang-kurangnya seorang kawan.');

        $room = DB::transaction(function () use ($user, $invitedIds) {
            $room = GameRoom::create([
                'game' => 'uno',
                'status' => 'waiting',
                'created_by' => $user->id,
                'min_players' => 2,
                'max_players' => 6,
            ]);

            GameRoomPlayer::create(['game_room_id' => $room->id, 'user_id' => $user->id, 'seat' => 0, 'status' => 'joined']);

            foreach ($invitedIds as $fid) {
                GameRoomPlayer::create(['game_room_id' => $room->id, 'user_id' => $fid, 'status' => 'invited', 'invited_by' => $user->id]);
            }

            return $room;
        });

        foreach ($invitedIds as $fid) {
            Notification::send(
                $fid,
                'uno_invite',
                $user->displayName().' menjemput anda main UNO 🎴',
                'Tekan untuk sertai bilik permainan.',
                route('game.uno.room', $room->id)
            );
        }

        return redirect()->route('game.uno.room', $room->id);
    }

    public function join(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->findRow($room, $user->id);
        abort_if($player === null || $player->status !== 'invited', 422);
        abort_unless($room->status === 'waiting', 422);
        abort_unless($room->players->where('status', 'joined')->count() < $room->max_players, 422, 'Bilik sudah penuh.');

        $nextSeat = $room->players->where('status', 'joined')->count();
        $player->update(['status' => 'joined', 'seat' => $nextSeat]);

        return redirect()->route('game.uno.room', $room->id);
    }

    public function decline(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $player = $this->findRow($room, $user->id);
        abort_if($player === null || $player->status !== 'invited', 422);

        $player->update(['status' => 'declined']);

        return redirect()->route('game.uno.menu')->with('status', 'Jemputan UNO ditolak.');
    }

    public function leave(Request $request, GameRoom $room)
    {
        $user = $request->user();
        abort_unless($room->status === 'waiting', 422);
        abort_if($room->created_by === $user->id, 422, 'Pengasas tak boleh keluar - biar bilik ni dan cipta yang baru kalau perlu.');

        $player = $this->findRow($room, $user->id);
        abort_if($player === null, 403);

        $player->update(['status' => 'left']);

        return redirect()->route('game.uno.menu')->with('status', 'Anda keluar dari bilik UNO.');
    }

    public function start(Request $request, GameRoom $room)
    {
        $user = $request->user();
        abort_unless($room->created_by === $user->id, 403);
        abort_unless($room->status === 'waiting', 422);

        $joined = $room->players()->where('status', 'joined')->orderBy('seat')->get();
        abort_unless($joined->count() >= 2, 422, 'Perlukan sekurang-kurangnya 2 pemain utk mula.');

        GameRoomPlayer::where('game_room_id', $room->id)->where('status', 'invited')->update(['status' => 'declined']);

        $state = UnoEngine::deal($joined->pluck('user_id')->all());
        $room->update(['status' => 'active', 'state' => $state, 'started_at' => now()]);

        foreach ($joined as $p) {
            if ($p->user_id !== $user->id) {
                Notification::send($p->user_id, 'uno_start', 'Permainan UNO bermula! 🎴', 'Giliran anda mungkin sudah tiba.', route('game.uno.room', $room->id));
            }
        }

        return redirect()->route('game.uno.room', $room->id);
    }

    public function show(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizePlayer($room, $user);

        return view('game.uno-room', [
            'room' => $room,
            'isCreator' => $room->created_by === $user->id,
        ]);
    }

    public function poll(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizePlayer($room, $user);

        if ($room->status === 'waiting') {
            return response()->json(['status' => 'waiting', 'lobby' => $this->lobbyPayload($room)]);
        }

        if ($room->state === null) {
            return response()->json(['status' => $room->status, 'game' => null]);
        }

        return response()->json(['status' => $room->status, 'game' => $this->gameView($room, $user->id)]);
    }

    public function play(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizeActive($room, $user);

        $data = $request->validate([
            'card_index' => ['required', 'integer', 'min:0'],
            'chosen_color' => ['nullable', 'in:red,yellow,green,blue'],
        ]);

        try {
            $state = UnoEngine::play($room->state, $user->id, $data['card_index'], $data['chosen_color'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $this->saveState($room, $state);

        return response()->json(['status' => $room->status, 'game' => $this->gameView($room, $user->id)]);
    }

    public function draw(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizeActive($room, $user);

        try {
            $state = UnoEngine::draw($room->state, $user->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $this->saveState($room, $state);

        return response()->json(['status' => $room->status, 'game' => $this->gameView($room, $user->id)]);
    }

    public function pass(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizeActive($room, $user);

        try {
            $state = UnoEngine::pass($room->state, $user->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $this->saveState($room, $state);

        return response()->json(['status' => $room->status, 'game' => $this->gameView($room, $user->id)]);
    }

    public function callUno(Request $request, GameRoom $room)
    {
        $user = $request->user();
        $this->authorizeActive($room, $user);

        $state = UnoEngine::callUno($room->state, $user->id);
        $this->saveState($room, $state);

        return response()->json(['status' => $room->status, 'game' => $this->gameView($room, $user->id)]);
    }

    // ---------------------------------------------------------------------

    private function findRow(GameRoom $room, int $userId): ?GameRoomPlayer
    {
        $room->loadMissing('players.user');

        return $room->players->first(fn ($p) => $p->user_id === $userId);
    }

    private function authorizePlayer(GameRoom $room, $user): GameRoomPlayer
    {
        $player = $this->findRow($room, $user->id);
        abort_if($player === null, 403);

        return $player;
    }

    private function authorizeActive(GameRoom $room, $user): GameRoomPlayer
    {
        abort_unless($room->status === 'active', 422);
        $player = $this->authorizePlayer($room, $user);
        abort_unless($player->status === 'joined', 403);

        return $player;
    }

    private function saveState(GameRoom $room, array $state): void
    {
        $room->state = $state;
        if ($state['status'] === 'finished') {
            $room->status = 'finished';
            $room->ended_at = now();
            $room->winner_user_id = $state['winnerId'];
        }
        $room->save();
    }

    private function gameView(GameRoom $room, int $userId): array
    {
        $view = UnoEngine::viewFor($room->state, $userId);
        $view['log'] = $this->namedLog($room, $view['log']);
        $view['players'] = $this->playersPayload($room);

        return $view;
    }

    private function playersPayload(GameRoom $room): array
    {
        return $room->players()->where('status', 'joined')->orderBy('seat')->get()->map(function ($p) {
            return [
                'user_id' => $p->user_id,
                'name' => $p->user->displayName(),
                'has_photo' => (bool) $p->user->profile_photo,
                'photo' => $p->user->profile_photo,
                'avatar' => $p->user->avatarFallback(),
                'seat' => $p->seat,
            ];
        })->values()->all();
    }

    private function namedLog(GameRoom $room, array $lines): array
    {
        $names = $room->players()->where('status', 'joined')->get()
            ->mapWithKeys(fn ($p) => [$p->user_id => $p->user->displayName()]);

        return array_map(function ($line) use ($names) {
            return preg_replace_callback('/Pemain #(\d+)/', function ($m) use ($names) {
                return $names[(int) $m[1]] ?? $m[0];
            }, $line);
        }, $lines);
    }

    private function lobbyPayload(GameRoom $room): array
    {
        $room->loadMissing('players.user');

        return [
            'creator_id' => $room->created_by,
            'min_players' => $room->min_players,
            'max_players' => $room->max_players,
            'players' => $room->players->map(function ($p) use ($room) {
                return [
                    'user_id' => $p->user_id,
                    'name' => $p->user?->displayName(),
                    'status' => $p->status,
                    'is_creator' => $p->user_id === $room->created_by,
                ];
            })->values()->all(),
        ];
    }
}
